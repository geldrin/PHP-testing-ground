<?php
namespace Videosquare\Job;

class runExt {
///////////////////////////////////////////////////////////////////////////////////////////////////
//
// RunExternal v5 ( runExtV ):
// Ugly, mutated version of the previous runExt4() function.
//
// Additions:
//   - configurable timeout with a default value of 10s
//   - kills running process and it's subprocesses on timeout
//   - callbacks can be defined
//   - support for retrieving regular bash command's return values
//
///////////////////////////////////////////////////////////////////////////////////////////////////
	var $command     = null;
	var $envvars     = null; // not implemented yet!!
	var $timeoutsec  = null;
	var $close_stdin = false;

	private $start        = 0;
	private $duration     = 0;
	private $code         = -1;
	private $output       = array();
	private $pid          = null;
	private $masterpid    = null;
	private $groupid      = null;
	private $msg          = null;
	private $callback     = null;
	private $polling_usec = 50000;
	private $process      = null;
	private $pipes        = array();

///////////////////////////////////////////////////////////////////////////////////////////////////
	function __construct($command = null, $timeoutsec = null, $callback = null, $envvar = null) {
///////////////////////////////////////////////////////////////////////////////////////////////////
		$this->masterpid  = intval(posix_getpid());
		$this->command    = $command;
		$this->timeoutsec = 10.0;
		
		if ($timeoutsec !== null && is_numeric($timeoutsec)) $this->timeoutsec = floatval($timeoutsec);
		if ($callback !== null && is_callable($callback)) $this->callback = $callback;
		if (is_array($envvar)) $this->env = $envvar;
	}

///////////////////////////////////////////////////////////////////////////////////////////////////
	function run($command = null, $timeoutsec = null, $callback = null) {
///////////////////////////////////////////////////////////////////////////////////////////////////
		$write      = null;
		$excl       = null;
		$ready      = null;
		$EOF        = false;
		$timeout    = false;
		$lastactive = 0;
		
		$this->clearVariables();
		$this->pipes    = array();
		$this->proceess = null;
		
		if ($command !== null) $this->command = $command;
		if ($timeoutsec !== null && is_numeric($timeoutsec)) $this->timeoutsec = floatval($timeoutsec);
		if ($callback !== null && is_callable($callback)) $this->callback = $callback;
		
		if (empty($this->command)) {
			$this->msg = "[ERROR] no command to be executed!";
			return false;
		}
		
		$desc = array(
			0 => array('pipe', 'r'),
			1 => array('pipe', 'w'),
			2 => array('pipe', 'w'),
		);
		
		$this->start = microtime(true);
		$lastactive = $this->start;
		$this->process = proc_open($this->command, $desc, $this->pipes);
		
		if ($this->process === false || !is_resource($this->process)) {
			$this->msg = "[ERROR] Failed to open process!";
			return false;
		}
		
		if ($this->close_stdin) {
			fclose($this->pipes[0]);
			unset($this->pipes[0]);
		}
		
		foreach($this->pipes as $p) { stream_set_blocking($p, 0); }
		
		$proc_status = proc_get_status($this->process);
		$this->pid = $proc_status['pid'];
		$this->groupid = posix_getpgid($this->pid);
		
		do {
			$read = $this->pipes;
			$tmp  = null;
			
			$ready = stream_select($read, $write, $excl, 0, $this->polling_usec);
			$proc_status = proc_get_status($this->process);
			
			if ($ready === false ) { // error
				$err = error_get_last();
				$this->msg = $err['message'];
				restore_error_handler();
				break;
			} elseif ($ready > 0) {
				foreach($read as $r) {
					$tmp .= stream_get_contents($r);
					if (feof($r)) $EOF = true;
				}
				
				if (!empty($tmp)) {
					$lastactive = microtime(true);
					$this->output[] = $tmp;
					if ($this->callback) call_user_func($this->callback, $tmp);
					continue;
				}
			} else {
				$timeout = ((microtime(true) - $lastactive) > $this->timeoutsec);
				usleep($this->polling_usec);
			}
		} while($proc_status['running'] && !$timeout && !$EOF);
		
		if ($timeout) {
			$proc_status = proc_get_status($this->process);
			$this->msg = "[WARN] Timeout Exceeded, sending SIGKILL to process (pid=". $this->pid .")";
			$this->duration = (microtime(true) - $this->start);
			
			if (is_resource($this->process) && $proc_status['running']) {
				$this->killProc();
			}
			
			return false;
		}
		
		if ($proc_status['running']) {
			$this->killProc();
			$proc_status = proc_get_status($this->process);
			$this->code = $proc_status['exitcode'];
		}
		
		$this->code = $proc_status['exitcode'];
		$this->duration = (microtime(true) - $this->start);
		
		if ($proc_status['signaled']) {
			$this->msg = "[WARN] Process has been terminated by an uncaught signal(". $proc_status['termsig'] .").";
			return false;
		} elseif ($proc_status['stopped']) {
			$this->msg = "[WARN] Process stopped after recieving signal(". $proc_status['stopsig'] .").";
		} else {
			if ($this->code === 0) {
				$this->msg = "[OK]";
			} else {
				$this->msg = "[WARN] Process failed (exitcode = ". $this->code .").";
				return false;
			}
		}
		
		return true;
	}

///////////////////////////////////////////////////////////////////////////////////////////////////
	private function killProc($PID = null) {
///////////////////////////////////////////////////////////////////////////////////////////////////
		$status       = null;
		$subprocesses = null;
		$proc2kill    = null;

		$proc2kill = $PID === null ? $this->pid : $PID;
		$subprocesses = array_reverse($this->getSubprocesses($proc2kill));

		foreach ($this->pipes as $p) { fclose($p); }

		if (!is_resource($this->process)) return true;

		$status = proc_get_status($this->process);

		if ($status['running']) {
			foreach ($subprocesses as $subp) {
				if (!posix_kill($subp, 0)) {
					continue;
				}

				posix_kill($subp, SIGKILL);
			}
		}

		return true;
	}

///////////////////////////////////////////////////////////////////////////////////////////////////
	private function getSubprocesses() {
///////////////////////////////////////////////////////////////////////////////////////////////////
		$tmp = [];
		exec("pstree -p ". $this->pid ." | grep -o '([0-9]\+)' | grep -o '[0-9]\+' | grep -v ". $this->pid, $tmp);

		return($tmp);
	}

///////////////////////////////////////////////////////////////////////////////////////////////////
	private function clearVariables() {
///////////////////////////////////////////////////////////////////////////////////////////////////
		$this->start        = 0;
		$this->duration     = 0;
		$this->code         = -1;
		$this->output       = array();
		$this->pid          = null;
		$this->msg          = null;
		$this->callback     = null;
		$this->polling_usec = 50000;
	}

///////////////////////////////////////////////////////////////////////////////////////////////////

	function getCode()      { return (int) $this->code; }

	function getDuration()  { return (double) $this->duration; }

	function getMessage()   { return $this->msg; }

	function getOutput()    { return implode(PHP_EOL, $this->output); }

	function getOutputArr() { return $this->output; }

	function getPID()       { return $this->pid; }

	function getGroupID()   { return $this->groupid; }

	function getMasterPID() { return $this->masterpid; }

} // end of RunExtV class
