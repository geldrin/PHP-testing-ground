<?php

ProgressDemo::Main($argv);
exit(0);

////////////////////////

class ProgressDemo {
	public static function Main($args) {
		$filename = null;
		
		if (count($args) <= 1) {
			print_r("No video file was supported!");
		} elseif (!empty($args[1]) && file_exists($args[1])) {
			$filename = $args[1];
		} else {
			print_r("File does not exists! ({$args[1]})");
			return false;
		}
		
		//$cmd = "/home/conv/ffmpeg/ffmpeg-customVSQ-git20160531-static/ffmpeg -y -i {$filename} -filter_complex \"scale=w=640:h=-2\" -c:v h264 -preset:v veryslow -c:a aac /home/gergo/temp/out123.mp4";
		$cmd = "/home/conv/ffmpeg/ffmpeg-customVSQ-git20160531-static/ffmpeg -y -i {$filename} -filter_complex \"scale=w=640:h=-2\" -c:v h264 -preset:v veryslow -c:a aac -f NULL -";
		
		$job = new runExt($cmd);
		$job->setPollingRate(0, 1.2);
		$job->addCallback('\FFutils::ffmpegProgress');
		
		if (!$job->run()) {
			print_r(var_export($job, 1));
			exit($job->getCode());
		} else {
			print_r(PHP_EOL . $job->getMessage() . PHP_EOL);
		}
	}
}

class FFutils {
	static function ffmpegProgress($data) {
		static $duration = 1;

		$position = 0;
		$timestr = null;
		$timeval = null;
		$matches = null;

		$mode = 0; // none = 0, position = 1, duration = 2

		//----------

		if (!$data) return;

		if (preg_match('/time=(.*?) bitrate=/', $data, $matches)) {
			$mode = 1;
		} elseif (preg_match('/Duration: (.*?), start/', $data, $matches)) {
			$mode = 2;
		} else {
			return false;
		}

		$timestr = $matches[1];
		$tmp = explode(':', $timestr);
		$timeval = $tmp[0] * 3600.0 + $tmp[1] * 60.0 + $tmp[2];

		switch ($mode) {
			case (1):
				$position = $timeval;
				break;

			case (2):
				$duration = $timeval;
				break;

			default:
				return false;
		}

		if ($duration !== 0) {
			$percentage = (float) (($position / $duration) * 100);
			printf("[ Duration = %1\$04.2f | current pos = %2\$03.1fs / %3\$03.1f%% ]     \r",
				$duration,
				$position,
				$percentage
			);
		}
	}
}

///////////////////////////////////////////////////////////////////////////////////////////////////
class runExt {
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
	private $msg          = array();
	private $callbacks    = null;
	private $polling_sec  = 0;
	private $polling_usec = 50000;
	private $process      = null;
	private $pipes        = array();

///////////////////////////////////////////////////////////////////////////////////////////////////
	function __construct($command = null, $timeoutsec = null, $envvar = null) {
///////////////////////////////////////////////////////////////////////////////////////////////////
		$this->masterpid  = intval(posix_getpid());
		$this->command    = $command;
		$this->timeoutsec = 10.0;
		
		if ($timeoutsec !== null && is_numeric($timeoutsec)) $this->timeoutsec = floatval($timeoutsec);
		if (is_array($envvar)) $this->env = $envvar;
	}

///////////////////////////////////////////////////////////////////////////////////////////////////
	function setPollingRate($pollrate_usec = 50000, $pollrate_sec = 0) {
///////////////////////////////////////////////////////////////////////////////////////////////////
		if (isset($pollrate_sec) && is_numeric($pollrate_sec)) $this->polling_sec = (int) $pollrate_sec;

		if (isset($pollrate_usec) && is_numeric($pollrate_usec)) {
			$this->polling_sec += (int) ($pollrate_usec / 1000000);
			$pollrate_usec = $pollrate_usec % 1000000;
			if ($pollrate_sec == 0 && $pollrate_usec < 1000) {
					$pollrate_usec = 1000;
			}
			$this->polling_usec = (int) $pollrate_usec;
		}
	}
	
///////////////////////////////////////////////////////////////////////////////////////////////////
	function addCallback($aCallback, $param = null) {
///////////////////////////////////////////////////////////////////////////////////////////////////
		if (empty($aCallback) || !is_callable($aCallback)) return false;
		
		$tmp = array('callback' => null, 'param' => null);
		$tmp['callback'] = $aCallback;
		
		if (isset($param)) $tmp['param'] = $param;
		
		$this->callbacks[] = $tmp;
		unset($tmp);
		
		return true;
	}
	
///////////////////////////////////////////////////////////////////////////////////////////////////
	function run($command = null, $timeoutsec = null) {
///////////////////////////////////////////////////////////////////////////////////////////////////
		$write      = null;
		$excl       = null;
		$ready      = null;
		$EOF        = false;
		$timeout    = false;
		$lastactive = 0;
		
		clearstatcache();
		$this->clearVariables();
		$this->pipes    = array();
		$this->proceess = null;
		
		if ($command !== null) $this->command = $command;
		if ($timeoutsec !== null && is_numeric($timeoutsec)) $this->timeoutsec = floatval($timeoutsec);
		
		if (empty($this->command)) {
			$this->msg[] = "[ERROR] no command to be executed!";
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
			$this->msg[] = "[ERROR] Failed to open process!";
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
			$ready = 0;
			
			$ready = stream_select($read, $write, $excl, $this->polling_sec, $this->polling_usec);
			$proc_status = proc_get_status($this->process);
			
			if ($ready === false ) { // error
				$err = error_get_last();
				$this->msg[] = $err['message'];
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
				}
			} else {	
				$timeout = ((microtime(true) - $lastactive) > $this->timeoutsec);
			}
			
			if (isset($this->callbacks)) $this->doCallbacks($tmp);
			
		} while($proc_status['running'] && !$timeout && !$EOF);
		
		if ($timeout) {
			$proc_status = proc_get_status($this->process);
			$this->msg[] = "[WARN] Timeout Exceeded, sending SIGKILL to process (pid=". $this->pid .")";
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
			$this->msg[] = "[WARN] Process has been terminated by an uncaught signal(". $proc_status['termsig'] .").";
			return false;
		} elseif ($proc_status['stopped']) {
			$this->msg[] = "[WARN] Process stopped after recieving signal(". $proc_status['stopsig'] .").";
		} else {
			if ($this->code === 0) {
				$this->msg[] = "[OK]";
			} else {
				$this->msg[] = "[WARN] Process failed (exitcode = ". $this->code .").";
				return false;
			}
		}
		
		return true;
	}
	
///////////////////////////////////////////////////////////////////////////////////////////////////
	private function doCallbacks($data = null) {
///////////////////////////////////////////////////////////////////////////////////////////////////
		foreach ($this->callbacks as $c) {
			try {
				call_user_func($c['callback'], $data, $c['param']);
			} catch (Exception $ex) {
				$this->msg[] = "[WARN] Caught exception during callback: {$c['callback']}()\nMessage:". $ex->getMessage();
			}
		}
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
		$this->msg          = array();
	}

///////////////////////////////////////////////////////////////////////////////////////////////////

	function getCode()        { return (int) $this->code; }

	function getDuration()    { return (double) $this->duration; }

	function getMessage()     { return implode(PHP_EOL, $this->msg); }

	function getOutput()      { return implode(PHP_EOL, $this->output); }

	function getOutputArr()   { return $this->output; }

	function getPID()         { return $this->pid; }

	function getGroupID()     { return $this->groupid; }

	function getMasterPID()   { return $this->masterpid; }
	
	function clearCallbacks() { $this->callback = null; }

} // end of RunExtV class