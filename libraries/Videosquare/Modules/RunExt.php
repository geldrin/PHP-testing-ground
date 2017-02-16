<?php
namespace Videosquare\Job;

/**
 * RunExternal v5 ( runExtV ):
 * Ugly, mutated version of the previous runExt4() function.
 * 
 * Additions:
 * - configurable timeout with a default value of 10s
 * - kills running process and it's subprocesses on timeout
 * - callbacks can be defined and executed parallel
 * - support for retrieving regular bash command's return values
 */
class RunExt {
  var $command     = null;
  var $envvars     = null; // not implemented yet!!
  var $timeoutsec  = null;
  var $close_stdin = false;
  var $filedescriptors = null;

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
  private $descriptors  = null;

  /**
   * Constructor of the RunExt class.
   * 
   * @param string $command Shell command to be executed
   * @param float $timeoutsec Timeout in fraction of seconds
   * @param array $envvar NOT IMPLEMENTED YET
   * @return \Videosquare\Job\RunExt
   */
  function __construct($command = null, $timeoutsec = null, $envvar = null) {
    $this->masterpid  = intval(posix_getpid());
    $this->command    = $command;
    $this->timeoutsec = 10.0;
    
    if ($timeoutsec !== null && is_numeric($timeoutsec)) { $this->timeoutsec = floatval($timeoutsec); }
    if (is_array($envvar)) { $this->env = $envvar; }
    
    return $this;
  }
  
  /**
   * Sets polling rate. The lower the rate, the faster it polls the running process' ouput.
   * Minimum value is 1 ms.
   * 
   * Warning: smaller values will increase processor load.
   * 
   * @see http://php.net/manual/en/function.stream-select.php
   * @param float $pollrate_sec
   */
  function setPollingRate($pollrate_sec = .0) {
    if (isset($pollrate_sec) && is_numeric($pollrate_sec)) {
      if ($pollrate_sec < 0.001) {
        $this->polling_sec  = 0;
        $this->polling_usec = 1000; // max. polling rate is 1ms
      } else {
        $this->polling_sec  = (int) $pollrate_sec;
        $this->polling_usec = ($pollrate_sec - $this->polling_sec) * 1000000;
      }
    }
  }
  
  /**
   * Set user-defined descriptor object.
   * Using a custom filedescriptor structure, it is possible to redirect the inputs and outputs
   * of the invoked process. This includes standard pipes, files, or even stream handlers.
   * 
   * @param type \Videosquare\Job\FD
   * @return boolean
   */
  function setFileDescriptors($descriptorobject) {
    if ($this->filedescriptors instanceof FD) {
      $this->filedescriptors = $descriptorobject;
      return true;
    }
    
    return false;
  }
  
  /**
   * Method to set filedescriptors.
   * If invalid data was passed, it creates the default stdio structure.
   * 
   * @param \Videosquare\Job\FD $FDobject
   */
  protected function buildFileDescriptors($FDobject = null) {
    if (!($FDobject instanceof FD)) { // use default FDobject
       $this->descriptors = (new FD())
         ->addPipe(0, FDobject::READ)
         ->addPipe(1, FDobject::WRITE)
         ->addPipe(2, FDobject::WRITE)
         ->getFileDescriptorArray();
    } else {
      $this->descriptors = $FDobject->getFileDescriptorArray();
    }
  }
  
  /**
   * Add a callback to the process.
   * 
   * If there's a new row printed by the process, the data is passed as the first argument of the callback.
   * Any user defined parameters must be passed in the $param parameter.
   * 
   * @param callback/string $aCallback Instance or name of a function.
   * @param mixed $param
   * @return boolean
   */
  function addCallback($aCallback, $param = null) {
    if (empty($aCallback) || !is_callable($aCallback)) { return false; }
    
    $tmp = array('callback' => null, 'param' => null);
    $tmp['callback'] = $aCallback;
    
    if (isset($param)) { $tmp['param'] = $param; }
    
    $this->callbacks[] = $tmp;
    unset($tmp);
    
    return true;
  }
  
  /**
   * Executes shell command, and collects any data the process would print to the console.
   * If callbacks were defined, the function calls them for any new line printed by the process.
   * 
   * @param string $command Shell command to be executed
   * @param float $timeoutsec Timeout in seconds.
   * @return boolean TRUE if successful.
   */
  function run($command = null, $timeoutsec = null) {
    $write      = null;
    $excl       = null;
    $ready      = null;
    $EOF        = false;
    $timeout    = false;
    $lastactive = 0;
    
    $this->clearVariables();
    $this->pipes   = array();
    $this->process = null;
    
    if ($command !== null) { $this->command = $command; }
    if ($timeoutsec !== null && is_numeric($timeoutsec)) { $this->timeoutsec = floatval($timeoutsec); }
    
    if (empty($this->command)) {
      $this->msg[] = "[ERROR] no command to be executed!";
      return false;
    }
    
    if ($this->descriptors === null) { $this->buildFileDescriptors(); }
    
    $this->start = microtime(true);
    $lastactive = $this->start;
    $this->process = proc_open($this->command, $this->descriptors, $this->pipes, $this->envvars);
    
    if ($this->process === false || !is_resource($this->process)) {
      $this->msg[] = "[ERROR] Failed to open process!";
      return false;
    }
    
    if ($this->close_stdin && isset($this->pipes[0])) {
      fclose($this->pipes[0]);
      unset($this->pipes[0]);
    }
    
    foreach($this->pipes as $p) { stream_set_blocking($p, 0); }
    
    $proc_status = proc_get_status($this->process);
    $this->pid = $proc_status['pid'];
    $this->groupid = posix_getpgid($this->pid);
    
    do {
      $read  = $this->pipes;
      $tmp   = null;
      $ready = 0;
      
      if ($read && $write && $excl) {
        // If there's no streams to read, don't poll them.
        $ready = stream_select($read, $write, $excl, $this->polling_sec, $this->polling_usec);
      }
      
      if ($proc_status['running']) { $proc_status = proc_get_status($this->process); }
      
      $this->code = $proc_status['exitcode'];
      
      if ($ready === false) { // error
        $err = error_get_last();
        $this->msg[] = "Stream_select() error: {$err['message']}";
        restore_error_handler();
        break;
      } elseif ($ready > 0) {
        foreach($read as $r) {
          $tmp .= stream_get_contents($r);
          if (feof($r)) { $EOF = true; }
        }
        
        if (!empty($tmp)) {
          $lastactive = microtime(true);
          $this->output[] = $tmp;
        }
      } else {  
        $timeout = ((microtime(true) - $lastactive) > $this->timeoutsec);
      }
      
      if (isset($this->callbacks)) { $this->doCallbacks($tmp); }
      
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
      $this->msg[] = "[WARN] Forcing process #{$this->pid} to shut down";
      $this->killProc();
    }
    
    if ($this->code < 0) {
      $proc_status = proc_get_status($this->process);
      $this->code = $proc_status['exitcode'];
    }
    
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
  
  function runDetached($command = null, $timeoutsec = null) {
    $this->msg[] = "[NOTICE] Starting detached process. Exit code cannot be retrieved.";
    
    if ($this->filedescriptors === null) {
      $this->buildFileDescriptors((new FD())
        ->addFile(1, FDobject::WRITE, "/dev/null")
        ->addFile(2, FDobject::WRITE, "/dev/null"));
    }
    
    return $this->run($command, $timeoutsec);
  }
  
  /**
   * Call user-defined function(s) with the data outputted by the process.
   * 
   * @param string $data
   */
  private function doCallbacks($data = null) {
    foreach ($this->callbacks as $c) {
      try {
        call_user_func($c['callback'], $data, $c['param']);
      } catch (Exception $ex) {
        $this->msg[] = "[WARN] Caught exception during callback: {$c['callback']}()\nMessage:". $ex->getMessage();
      }
    }
  }
  
  /**
   * Kill process with given processID.
   * 
   * @param integer $PID
   * @return boolean
   */
  private function killProc($PID = null) {
    $subprocesses = null;
    $proc2kill    = null;

    $proc2kill = $PID === null ? $this->pid : $PID;
    $subprocesses = array_reverse($this->getSubprocesses($proc2kill));

    foreach ($this->pipes as $p) { fclose($p); }

    if (!is_resource($this->process)) { return true; }

    foreach ($subprocesses as $subp) {
      // force process down by killing subprocesses
      if (!posix_kill($subp, 0)) {
        continue;
      }

      posix_kill($subp, SIGKILL);
    }
    
    // kill main process if still running
    if (posix_kill($this->pid, 0)) { posix_kill($this->pid, SIGKILL); }

    return true;
  }
  
  /**
   * Get child processes of the current process.
   * (Yeah, had to use the dirty exec() system call.)
   * 
   * @return array
   */
  private function getSubprocesses() {
    $tmp = [];
    exec("pstree -p ". $this->pid ." | grep -o '([0-9]\+)' | grep -o '[0-9]\+' | grep -v ". $this->pid, $tmp);

    return($tmp);
  }
  
  /**
   * Resets all variables, vital part before performing another run with the same instance.
   */
  private function clearVariables() {
    $this->start        = 0;
    $this->duration     = 0;
    $this->code         = -1;
    $this->output       = array();
    $this->pid          = null;
    $this->msg          = array();
    
    clearstatcache();
  }

  function getCode()            { return (int) $this->code; }

  function getDuration()        { return (double) $this->duration; }

  function getMessage()         { return implode(PHP_EOL, $this->msg); }

  function getOutput()          { return implode(null, $this->output); }

  function getOutputArr()       { return $this->output; }

  function getPID()             { return $this->pid; }

  function getGroupID()         { return $this->groupid; }

  function getMasterPID()       { return $this->masterpid; }
  
  function getFileDescriptors() { return $this->filedescriptors; }
  
  function clearCallbacks()     { $this->callback = null; }

} // end of RunExtV class

final class FD {
  private $descriptors = [];
  
  public function addPipe($pipenumber, $mode) {
    $this->insertDescriptor($pipenumber, new FDpipe($mode));
    return $this;
  }
  
  public function addFile($pipenumber, $mode, $file) {
    $this->insertDescriptor($pipenumber, new FDfile($mode, $file));
    return $this;
  }
  
  public function addStream($pipenumber, $streamresource) {
    $this->insertDescriptor($pipenumber, new FDstream($streamresource));
    return $this;
  }
  
  public function getFileDescriptorArray() {
    $descriptorspec = [];
    
    foreach ($this->descriptors as $n => $d) {
      if ($d instanceof FDobject) { $descriptorspec[$n] = $d->getData(); }
    }
    
    return $descriptorspec;
  }
  
  protected function insertDescriptor($pipenumber, $desc) {
    if ($desc instanceof FDobject) {
      $this->descriptors[$pipenumber] = $desc;
    }
  }
}

abstract class FDobject {
  protected $mode;
  protected $fdobject;
  
  const READ   = 'r';
  const WRITE  = 'w';
  const APPEND = 'a';
  
  abstract function getData();
}

final class FDpipe extends FDobject {
  function __construct($mode) {
    if (array_search($mode, ['r', 'w']) === false) { echo "FAIL\n"; return null; }
    $this->mode = $mode;
    return $this;
  }
  
  function getData() { return ['pipe', $this->mode]; }
}

final class FDstream extends FDobject {
  function __construct($streamresource) {
    if (!is_resource($streamresource)) { return null; }
    $this->fdobject = $streamresource;
    return $this;
  }
  
  function getData() { return $this->fdobject; }
}

final class FDfile extends FDobject {
  function __construct($mode, $file) {
    if (array_search($mode, ['r', 'w', 'a']) === false || !file_exists($file)) { return null; }
    $this->mode = $mode;
    $this->fdobject = $file;
    return $this;
  }
  
  function getData() { return ['file', $this->fdobject, $this->mode]; }
}
