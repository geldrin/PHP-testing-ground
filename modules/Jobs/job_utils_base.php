<?php
// Basic functions

// *************************************************************************
// *						function iswindows()			   			   *
// *************************************************************************
// Description: determine OS to support multiplatform operation
// INPUTS: none
// OUTPUTS:
//	- Boolean:
//	  o FALSE: not a Windows system
//	  o TRUE: Windows system
function iswindows() {

  $php_os = PHP_OS;
  if ( stripos($php_os, "WIN") !== false ) {
	return TRUE;
  }

  return FALSE;
}

// *************************************************************************
// *						function runExternal()			   			   *
// *************************************************************************
// Description: execute shell command. This is required for running ffmpeg in the background.
// INPUTS:
//	- $cmd: shell command
// OUTPUTS:
//	- $return_array:
//		o 'code': error code given by terminating shell command
//		o 'cmd_output': command output
// Author:
//	Written by dk@brightbyte.de
//	Source: http://www.php.net/manual/en/function.shell-exec.php#52826
function runExternal($cmd) {

	$descriptorspec = array(
		0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
		1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
		2 => array("pipe", "w")   // stderr is a file to write to
	);

	$pipes = array();
	$process = proc_open($cmd, $descriptorspec, $pipes);

	$output = "";

	if (!is_resource($process)) return false;

	// close child's input imidiately
	fclose($pipes[0]);

	stream_set_blocking($pipes[1], false);
	stream_set_blocking($pipes[2], false);

	$todo = array($pipes[1], $pipes[2]);

	while( true ) {

		$read = array();
		if( !feof($pipes[1]) ) $read[]= $pipes[1];
		if( !feof($pipes[2]) ) $read[]= $pipes[2];

		if (!$read) break;

		$write = NULL;
		$ex = NULL;
		$ready = stream_select($read, $write, $ex, 2);

		if ( $ready === FALSE ) {
			break; // should never happen - something died
		}

		foreach ($read as $r) {
			$s = fread($r, 1024);
			$output .= $s;
		}
	}

	fclose($pipes[1]);
	fclose($pipes[2]);

	$return_array = array();

	// Get process PID
	$tmp = proc_get_status($process);
	$return_array['pid'] = $tmp['pid'];
	$return_array['code'] = proc_close($process);
	$return_array['cmd_output'] = $output;

	return $return_array;
}

// *************************************************************************
// *						Timestamp conversion						   *
// *************************************************************************

function hms2secs($timestamp) {
  
  $timestamp = explode(':', $timestamp );
  
  if ( count( $timestamp ) != 3 )
    return 0;
  
  $time  = 0;
  $time += $timestamp[0] * 60 * 60;
  $time += $timestamp[1] * 60;
  $time += $timestamp[2];
  
  return $time;
}

function secs2hms($i_secs) {

	$secs = abs($i_secs);
	
	$m = (int)($secs / 60);
	$s = $secs % 60;
	$h = (int)($m / 60);
	$m = $m % 60;

	$hms = sprintf("%02d", $h) . ":" . sprintf("%02d", $m) . ":" . sprintf("%02d", $s);
	return $hms;
}

// -------------------------------------------------------------------------
// |				    File manipulation functions						   |
// -------------------------------------------------------------------------

// *************************************************************************
// *					function remove_file_ifexists()			   		   *
// *************************************************************************
// Description: remove file or remove directory recursively
// INPUTS:
//	- $directory: directory path
// OUTPUTS:
//  - $err array:
//	  o 'code': boolean TRUE/FALSE (operation status)
//	  o 'command': executed command
//	  o 'result': command output
//	  o 'message': textual message offered for logging
function remove_file_ifexists($filename) {
global $recording;

  $err = array();
  
  $err['code'] = TRUE;
  $err['command'] = "-";
  $err['command_output'] = "-";
  $err['result'] = 0;

  if ( !isset($filename) ) return $err;

  if ( !file_exists($filename) ) return $err;

  if ( is_dir($filename) ) {
	$command = "rm -r -f " . $filename . " 2>&1";
	if ( iswindows() ) {
		$command = "rmdir /S /Q " . realpath($filename) . " 2>&1";
	}
	exec($command, $output, $result);
	$output_string = implode("\n", $output);
	$err['result'] = $result;
	$err['command'] = $command;
	$err['command_output'] = $output_string;
	if ( $result != 0 ) {
		$err['code'] = FALSE;
		$err['message'] = "[ERROR] Cannot remove directory: " . $filename;
		return $err;
	}
  } else {
	$err['result'] = unlink($filename);
	$err['command'] = "php: unlink(\"" . $filename . "\")";
	$err['command_output'] = "-";
	if ( !$err['result'] ) {
		$err['code'] = FALSE;
		$err['message'] = "[ERROR] Cannot remove file: " . $filename;
		return $err;
	}
  }

  $err['code'] = TRUE;
  $err['message'] = "[OK] Removed file/directory: " . $filename;
  
  return $err;
}

// *************************************************************************
// *                    function create_directory()                        *
// *************************************************************************
// Description: create directory if does not exist, otherwise do nothing
// INPUTS:
//      - $directory: directory absolute path
// OUTPUTS:
//	- $err array:
//	  o 'code': boolean TRUE/FALSE (operation status)
//	  o 'command': executed command
//	  o 'result': command output status code
//	  o 'message': textual message offered for logging
function create_directory($directory) {

  $err = array();

  // If directory exists, then do nothing
  if ( file_exists($directory) ) {
    $err['code'] = TRUE;
    $err['command'] = "-";
    $err['result'] = 0;
    $err['message'] = "[OK] Directory exists";
    return $err;
  } else {
    // If not exists, then create the directory
    $result = mkdir($directory);
    $err['command'] = "php: mkdir(\"" . $directory . "\")";
    $err['result'] = $result;
    if ( !$result ) {
        $err['code'] = FALSE;
        $err['message'] = "[ERROR] Cannot create directory";
        return $err;
    } else {
        $err['code'] = TRUE;
        $err['message'] = "[OK] Directory created";
    }

  }

  return $err;
}

// *************************************************************************
// *                    function create_remove_directory()                 *
// *************************************************************************
// Description: create directory if it does not exist, otherwise remove its content
// INPUTS:
//	- $directory: directory absolute path
// OUTPUTS:
//	- $err array:
//	  o 'code': boolean TRUE/FALSE (operation status)
//	  o 'command': executed command
//	  o 'result': command output status code
//	  o 'message': textual message offered for logging
function create_remove_directory($directory) {

  $err = array();

  // If directory exists, then remove all content
  if ( file_exists($directory) ) {
    $output = array();
	$command = "rm -r -f " . $directory . "* 2>&1";	// UNIX delete command
	if ( iswindows() ) {
		$command = "del /F /S /Q " . realpath($directory);	// Windows delete command and path conversion
	}
    exec($command, $output, $result);
    $err['command'] = $command;
    $err['result'] = $result;
    if ( $result != 0 ) {
        $err['code'] = FALSE;
        $err['message'] = "[ERROR] Cannot remove directory content";
        return $err;
    } else {
        $err['code'] = TRUE;
        $err['message'] = "[OK] Directory content removed";
    }
  } else {
    // If does not exist, then create the directory
    $err['command'] = "php: mkdir(\"" . $directory . "\")";
    $result = mkdir($directory);
    if ( !$result ) {
        $err['code'] = FALSE;
        $err['message'] = "[ERROR] Cannot create directory";
        $err['result'] = $result;
        return $err;
    } else {
        $err['code'] = TRUE;
        $err['message'] = "[OK] Directory created";
        $err['result'] = $result;
    }
  }

  return $err;
}

// *************************************************************************
// *					function tempdir_cleanup()			   		       *
// *************************************************************************
// Description: clean up temporary directory
// INPUTS:
//	- $directory: temporary directory path
// OUTPUTS:
//  - $err array:
//	  o 'code': boolean TRUE/FALSE (operation status)
//	  o 'command': executed command
//	  o 'result': command output
//	  o 'message': textual message offered for logging
function tempdir_cleanup($directory) {

  $err = array();

  $err2 = create_remove_directory($directory);
  $err['result'] = $err2['result'];
  $err['command'] = $err2['command'];
  if ( !$err2['code'] ) {
    $err['code'] = FALSE;
    $err['message'] = "[ERROR] Temp directory cleanup failed: " . $directory . "\n" . $err2['message'];
    return $err;
  }
  $err['code'] = TRUE;
  $err['message'] = "[OK] Temp directory cleaned up: " . $directory;

  return $err;
}

// *************************************************************************
// *						function dirList()   			   		       *
// *************************************************************************
// Description: directory list with pattern filtering
// INPUTS:
//	- $directory: directory to list
//	- $pattern: pattern to filter (e.g. "jpg", "png", etc.)
// OUTPUTS:
//  - $results: array with matching filenames
function dirList($directory, $pattern) {

    $results = array();
    $handler = opendir($directory);
    while ($file = readdir($handler)) {
        if ($file != '.' && $file != '..') {
            if ( stripos($file, $pattern) !== FALSE ) {
                $results[] = $file;
            }
        }
    }

    closedir($handler);

    return $results;
}

// *************************************************************************
// *						function getFileList()   			   		   *
// *************************************************************************
// Description: get directory file list
// Credit: http://www.the-art-of-web.com/php/dirlist/
// INPUTS:
//	- $directory: directory to list
// OUTPUTS:
//  - $results: array with filenames, type, size and last modification time
function getFileList($dir) {
	
	// array to hold return value
	$retval = array();

    // add trailing slash if missing
	if( substr($dir, -1) != "/" ) $dir .= "/";

    // open pointer to directory and read list of files
	$d = @dir($dir);
	if ( $d === FALSE ) return FALSE;
	while ( false !== ($entry = $d->read()) ) {
      // skip hidden files
		if( $entry[0] == "." ) continue;
		if( is_dir("$dir$entry") ) {
			$retval[] = array(
			  "name" => "$dir$entry/",
			  "type" => filetype("$dir$entry"),
			  "size" => 0,
			  "lastmod" => filemtime("$dir$entry")
			);
      } elseif( is_readable("$dir$entry") ) {
			$retval[] = array(
			  "name" => "$dir$entry",
			  "type" => mime_content_type("$dir$entry"),
			  "size" => filesize("$dir$entry"),
			  "lastmod" => filemtime("$dir$entry")
			);
      }
    }
    $d->close();

    return $retval;
  }

// -------------------------------------------------------------------------
// |				    SSH remote file functions						   |
// -------------------------------------------------------------------------
function ssh_filesize($server, $file) {
global $jconf;

	$err = array();
	$err['value'] = null;

	$ssh_command = "ssh -i " . $jconf['ssh_key'] . " " . $jconf['ssh_user'] . "@" . $server . " ";
	$remote_filename = $jconf['ssh_user'] . "@" . $server . ":" . $file;

	$filesize = 0;
	$command = $ssh_command . "du -b " . $file . " 2>&1";
	exec($command, $output, $result);
	$err['command'] = $command;
    $err['result'] = $result;
	$output_string = implode("\n", $output);
	if ( $result != 0 ) {
		// If file does not exists then error is logged
		if ( strpos($output_string, "No such file or directory") > 0 ) {
			$err['code'] = FALSE;
			$err['message'] = "[ERROR] Input file does not exists at: " . $remote_filename;
			return $err;
		} else {
			// Other error occured, maybe locale, so we set status to "uploaded" to allow other nodes to take over the task
			$err['code'] = FALSE;
			$err['message'] = "[ERROR] SSH command failed";
			return $err;
		}
	} else {
		$tmp = preg_split('/\s+/', $output_string);
		$filesize = $tmp[0];
		if ( ( $filesize == 0) or (!is_numeric($filesize)) ) {
			$err['code'] = FALSE;
			$err['message'] = "[ERROR] Input file zero/invalid length: " . $remote_filename;
			return $err;
		}
	}

	$err['code'] = TRUE;
	$err['value'] = $filesize;

	return $err;
}

function ssh_filecopy_from($server, $file, $destination) {
global $jconf;

	$err = array();

	$command = "scp -B -i " . $jconf['ssh_key'] . " " . $jconf['ssh_user'] . "@" . $server . ":" . $file . " " . $destination . " 2>&1";
	$time_start = time();
	exec($command, $output, $result);
	$duration = time() - $time_start;
	$mins_taken = round( $duration / 60, 2);
	$err['command'] = $command;
    $err['result'] = $result;
	$output_string = implode("\n", $output);
	if ( $result != 0 ) {
		$err['code'] = FALSE;
		$err['message'] = "[ERROR] SCP copy failed from: " . $file;
		return $err;
	}

	$err['code'] = TRUE;
	$err['value'] = $duration;
	$err['message'] = "[OK] SCP copy finished (in " . $mins_taken . " mins)";

	return $err;
}

function ssh_filerename($server, $from, $to) {
global $jconf;

	$err = array();

	$ssh_command = "ssh -i " . $jconf['ssh_key'] . " " . $jconf['ssh_user'] . "@" . $server . " ";

	$command = $ssh_command . "mv -f " . $from . " " . $to . " 2>&1";
	exec($command, $output, $result);
	$err['command'] = $command;
    $err['result'] = $result;
	$output_string = implode("\n", $output);
	if ( $result != 0 ) {
		$err['code'] = FALSE;
		$err['message'] = "[ERROR] SSH file rename failed: " . $from;
		return $err;
	}

	$err['code'] = TRUE;
	$err['message'] = "[OK] SSH file renamed to: " . $to;

	return $err;
}

function ssh_fileremove($server, $file_toremove) {
global $jconf;

	$err = array();

	$ssh_command = "ssh -i " . $jconf['ssh_key'] . " " . $jconf['ssh_user'] . "@" . $server . " ";

	$command = $ssh_command . "rm -f " . $file_toremove . " 2>&1";
	exec($command, $output, $result);
	$err['command'] = $command;
    $err['result'] = $result;
	$output_string = implode("\n", $output);
	if ( $result != 0 ) {
		$err['code'] = FALSE;
		$err['message'] = "[ERROR] SSH file removal failed: " . $file_toremove;
		return $err;
	}

	$err['code'] = TRUE;
	$err['message'] = "[OK] SSH file removed: " . $file_toremove;

	return $err;
}

function ssh_filecopy($server, $file_src, $file_dst) {
global $jconf;

	// SSH check file size before start copying
	$err = ssh_filesize($server, $file_src);
	if ( !$err['code'] ) {
		return $err;
	}
	$filesize = $err['value'];

	$err = array();

	// Check available disk space (input media file size * 5 is the minimum)
	$available_disk = floor(disk_free_space($jconf['media_dir']));
	if ( $available_disk < $filesize * 5 ) {
		$err['command'] = "php: disk_free_space(" . $jconf['media_dir'] . ")";
		$err['result'] = $available_disk;
		$err['code'] = FALSE;
		$err['message'] = "[ERROR] Not enough free space to start conversion (available: " . ceil($available_disk / 1024 / 1024) . "Mb, filesize: " . ceil($filesize / 1024 / 1024) . ")";
		return $err;
	}

	$command = "scp -B -i " . $jconf['ssh_key'] . " " . $jconf['ssh_user'] . "@" . $server . ":" . $file_src . " " . $file_dst . " 2>&1";
	$time_start = time();
	exec($command, $output, $result);
	$duration = time() - $time_start;
	$mins_taken = round( $duration / 60, 2);
	$err['command'] = $command;
    $err['result'] = $result;
	$output_string = implode("\n", $output);
	if ( $result != 0 ) {
		$err['code'] = FALSE;
		$err['message'] = "[ERROR] SCP copy failed from: " . $file_src;
		return $err;
	}

	$err['code'] = TRUE;
	$err['value'] = $duration;
	$err['message'] = "[OK] SCP copy finished (in " . $mins_taken . " mins)";

	return $err;
}

function string_to_file($file, $str) {

	$err['command'] = "php: remove_file_ifexists()";

	$e = remove_file_ifexists($file);
	if ( $e['code'] == FALSE ) {
		$err['code'] == FALSE;
		$err['message'] = $e['message'];
		return $err;
	}

	$err['command'] = "php: fwrite()";

echo $file . "\n";

	$fh = fopen($file, 'w');
	$res = fwrite($fh, $str);
	$err['result'] = $res;
	if ( $res === FALSE ) {
		$err['code'] = FALSE;
		$err['message'] = "[ERROR] Cannot write file " . $file;
		return $err;
	}
	fclose($fh);

	$err['code'] = TRUE;
	return $err;
}


?>
