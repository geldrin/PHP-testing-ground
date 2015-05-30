<?php
// Base functions

function findRemoveFilesOlderThanDays($path, $days, $remove = false) {

	$err['code'] = false;
	$err['command_output'] = "-";
	$err['result'] = 0;
	$err['size'] = 0;
	$err['value'] = 0;

    $remove_cmd = "";
    if ( $remove ) $remove_cmd = "-exec rm -f {} \;";
    $err['command'] = "find " . $path . " -type f -user conv -mtime +" . $days . " -printf '%s %p\n' " . $remove_cmd . " 2>&1 | grep -v 'Permission denied'";
    
   	exec($err['command'], $output, $err['result']);
	$err['command_output'] = implode("\n", $output);
    
    for ( $i = 0; $i < count($output); $i++ ) {
        $tmp = explode(" ", $output[$i], 2);
        if ( is_numeric($tmp[0]) ) {
            $err['size'] += $tmp[0];
            $err['value']++;
        }
    }
    
	if ( $err['result'] == 0 ) $err['code'] = true;

    return $err;
}

function directory_size($path) {

	$err['code'] = FALSE;
	$err['command'] = "/usr/bin/du -sb " . $path;
	$err['command_output'] = "-";
	$err['result'] = 0;
	$err['size'] = 0;
	$err['value'] = 0;

	if ( !is_dir($path) ) return $err;

	exec($err['command'], $output, $result);
	$err['command_output'] = implode("\n", $output);
	$err['result'] = $result;
	if ( $result != 0 ) return $err;

	$tmp = preg_split('/\s+/', $err['command_output'], 2);

	if ( !is_numeric($tmp[0]) ) return $err;
	$err['value'] = $err['size'] = $tmp[0];

	$err['code'] = TRUE;

	return $err;
}

// *************************************************************************
// *						function GCD()			   			   		   *
// *************************************************************************
// Description: find greatest common divisor
// Source: http://blog.ifwebstudio.com/php/calculate-image-aspect-ratio-in-php/
function GCD($a, $b) {  

	while ($b != 0) {
		$remainder = $a % $b;  
		$a = $b;  
		$b = $remainder;  
	}

	return abs($a);
}
// *************************************************************************
// *				function soffice_isrunning()			   			   *
// *************************************************************************
function soffice_isrunning() {
 global $jconf;

	$command = "ps uax | grep \"^" . $jconf['ssh_user'] . "\" | grep \"soffice.bin\" | grep -v \"grep\"";
	exec($command, $output, $result);
	if ( isset($output[0]) ) {
		return TRUE;
	}

	return FALSE;
}


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

	$return_array = array();

	$return_array['pid'] = 0;
	$return_array['code'] = 0;
	$return_array['cmd_output'] = "";

	$descriptorspec = array(
		0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
		1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
		2 => array("pipe", "w")   // stderr is a file to write to
	);

	$pipes = array();
	$process = proc_open($cmd, $descriptorspec, $pipes);

	$output = "";

	if ( !is_resource($process) ) return $return_array;

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

function runExternal_vlc($cmd, $output_file) {

	$descriptorspec = array(
		0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
		1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
		2 => array("pipe", "w")   // stderr is a file to write to
	);

	$pipes = array();
	$process = proc_open($cmd, $descriptorspec, $pipes);

	$output = "VLC started: " . date("Y-m-d H:i:s") . "\n";

	if (!is_resource($process)) return false;

	// close child's input imidiately
	fclose($pipes[0]);

	stream_set_blocking($pipes[1], false);
	stream_set_blocking($pipes[2], false);

	$return_array = array();

	// Get process ID (proc_get_status() gives wrong PID)
	$ps = `ps -C vlc -o pid=`;
	$PID = (int)trim($ps);
	if ( !is_numeric($PID) ) $PID = -1;
	$return_array['pid'] = $PID;

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

		// If vlc closed output file, then we send SIGQUIT to it
		$err = is_process_closedfile($output_file, $PID);
		if ( $err['code'] == TRUE ) {
//			if ( ( $end_str !== FALSE ) and ( $err['code'] == TRUE ) ) {

			if ( !posix_kill($PID, SIGQUIT) ) {
				$output .= "\nERROR: not killed: $PID\n";
			}

			break;
		}

		// Handling process output to terminal
		foreach ($read as $r) {

			$s = fread($r, 1024);
			$tmp = trim($s);
			if ( !empty($tmp) ) {
				$output .= $tmp;
//				$end_str = stripos($tmp, "kb/s:" );
			}

		}

	}

	fclose($pipes[1]);
	fclose($pipes[2]);

	$return_array['code'] = proc_close($process);
	if ( $return_array['code'] != 0 ) {
		$return_array['code'] = FALSE;
	} else {
		$return_array['code'] = TRUE;
	}

	$return_array['command_output'] = $output;
	$return_array['command'] = $cmd;

	return $return_array;
}


// *************************************************************************
// *						Timestamp conversion						   *
// *************************************************************************

function hms2secs($timestamp) {

  $timestamp = explode(':', $timestamp);
  
  if ( count( $timestamp ) != 3 )
    return 0;
  
  $time  = 0;
  $time += $timestamp[0] * 60 * 60;
  $time += $timestamp[1] * 60;
  $time += $timestamp[2];
  
  return $time;
}

function secs2hms($i_secs) {

	$secs = floor(abs($i_secs));
	
	$m = (int)($secs / 60);
	$s = $secs % 60;
	$h = (int)($m / 60);
	$m = $m % 60;

	$hms = sprintf("%02d", $h) . ":" . sprintf("%02d", $m) . ":" . sprintf("%02d", $s);
	return $hms;
}

function seconds2DaysHoursMinsSecs($seconds) {
    $dtF = new DateTime("@0");
    $dtT = new DateTime("@$seconds");
    return $dtF->diff($dtT)->format('%a days, %h hours, %i minutes and %s seconds');
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

	$err['code'] = true;
	$err['command'] = "-";
	$err['command_output'] = "-";
	$err['result'] = 0;

	// Safety check: WE DO NOT DELETE ANYTHING OUTSIDE STORAGE?????

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
    // If not exists, then create the directory (recursive, creates nested directories)
	$oldumask = umask(0); 
    $result = mkdir($directory, 0775, TRUE);
	umask($oldumask);
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
// *				function move_uploaded_file_to_storage()			   *
// *************************************************************************
// Description: move file from upload area to storage
function move_uploaded_file_to_storage($fname, $fname_target, $isoverwrite) {
 global $app, $jconf;

	$err = array();
	$err['code'] = FALSE;
	$err['result'] = 0;
	$err['duration'] = 0;
	$err['message'] = "-";
	$err['command'] = "-";

	// Check source file and its filesize
	if ( !file_exists($fname) ) {
		$err['message'] = "[ERROR] Uploaded file does not exist.";
		$err['code'] = FALSE;
		return $err;
	}

	// Check filesize
	$filesize = filesize($fname);
	if ( $filesize <= 0 ) {
		$err['message'] = "[ERROR] Uploaded file has invalid size (" . $filesize . ").";
		$err['code'] = FALSE;
		return $err;
	}

	// Check available disk space
	$available_disk = floor(disk_free_space($app->config['recordingpath']));
	if ( $available_disk < $filesize * 10 ) {
		$err['message'] = "[ERROR] No space on target device. Only " . ( round($available_disk / 1024 / 1024, 2) ) . " MB left.";
		$err['code'] = FALSE;
		return $err;
	}

	// Check if target file exists
	if ( file_exists($fname_target) ) {
		// Overwrite? NO
		if ( !$isoverwrite ) {
			$err['message'] = "[ERROR] Target file " . $fname_target . " already exists.";
			$err['code'] = FALSE;
			return $err;
		} else {
			// Remove file if exists
			$err_tmp = remove_file_ifexists($fname_target);
			if ( !$err_tmp['code'] ) {
				$err['message'] = $err_tmp['message'];
				$err['command'] = $err_tmp['command'];
				$err['result'] = $err_tmp['result'];
				$err['code'] = FALSE;
				return $err;
			}
		}
	} else {
		// File does not exist. Prepare target directory on storage
		$path_parts = pathinfo($fname_target);
		$targetpath = $path_parts['dirname'] . "/";
		if ( !file_exists($targetpath) ) {
			$err_tmp = create_directory($targetpath);
			if ( !$err_tmp['code'] ) {
				$err['message'] = $err_tmp['message'];
				$err['command'] = $err_tmp['command'];
				$err['result'] = $err_tmp['result'];
				$err['code'] = FALSE;
				return $err;
			}
		}
	}

	// Copy file
	$time_start = time();
	$err_tmp = copy($fname, $fname_target);
	$err['duration'] = time() - $time_start;
	if ( !$err_tmp ) {
		$err['message'] = "[ERROR] Cannot copy file to storage.";
		$err['command'] = "php: move(\"" . $fname . "\",\"" . $fname_target . "\")";
		$err['result'] = $err_tmp;
		$err['code'] = FALSE;
		return $err;
	}

	// File access. Set user/group to "conv:vsq" and file access rights to "664"
	$command = "";
	$command .= "chmod -f " . $jconf['file_access']	. " " . $fname_target . " ; ";
	$command .= "chown -f " . $jconf['file_owner']	. " " . $fname_target . " ; ";
	exec($command, $output, $result);
	$output_string = implode("\n", $output);
	if ( $result != 0 ) {
		$err['message'] = "[ERROR] Cannot stat file on storage. Failed command:\n\n" . $command;
		$err['command'] = $command;
		$err['result'] = $result;
	}

	// Remove original file from front-end location
	$err_tmp = remove_file_ifexists($fname);
	if ( !$err_tmp['code'] ) {
		$err['message'] = $err_tmp['message'];
		$err['command'] = $err_tmp['command'];
		$err['result'] = $err_tmp['result'];
	}

	$err['code'] = TRUE;

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
global $app;

	$err = array();
	$err['value'] = null;
	$err['code'] = false;
	$err['command_output'] = "-";
	$err['result'] = 0;
	$err['size'] = 0;

	$ssh_command = "ssh -i " . $app->config['ssh_key'] . " " . $app->config['ssh_user'] . "@" . $server . " ";
	$remote_filename = $app->config['ssh_user'] . "@" . $server . ":" . $file;

	$filesize = 0;
	$command = $ssh_command . "du -sb " . $file . " 2>&1";
	exec($command, $output, $result);
	$err['command'] = $command;
    $err['result'] = $result;
	$output_string = implode("\n", $output);
	if ( $result != 0 ) {
		// If file does not exists then error is logged
		if ( strpos($output_string, "No such file or directory") > 0 ) {
			$err['code'] = FALSE;
			$err['message'] = "[ERROR] Input file/directory does not exists at: " . $remote_filename;
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
			$err['message'] = "[ERROR] Input file/directory zero/invalid length: " . $remote_filename;
			return $err;
		}
	}

	$err['code'] = TRUE;
	$err['value'] = $filesize;

	return $err;
}

function ssh_filemtime($server, $file) {
 global $app;

	$err = array();
	$err['value'] = null;
	$err['code'] = false;
	$err['command_output'] = "-";
	$err['result'] = 0;
	$err['size'] = 0;

	$ssh_command = "ssh -i " . $app->config['ssh_key'] . " " . $app->config['ssh_user'] . "@" . $server . " ";
	$remote_filename = $app->config['ssh_user'] . "@" . $server . ":" . $file;

	$filesize = 0;
	$command = $ssh_command . "stat -c %Y " . $file . " 2>&1";
	exec($command, $output, $result);
	$err['command'] = $command;
    $err['result'] = $result;
	$output_string = implode("\n", $output);
	if ( $result != 0 ) {
		// If file does not exists then error is logged
		if ( strpos($output_string, "No such file or directory") > 0 ) {
			$err['code'] = FALSE;
			$err['message'] = "[ERROR] Input file/directory does not exists at: " . $remote_filename;
			return $err;
		} else {
			// Other error occured
			$err['code'] = FALSE;
			$err['message'] = "[ERROR] SSH command failed";
			return $err;
		}
	} else {
		$tmp = preg_split('/\s+/', $output_string);
		$filemtime = $tmp[0];
		if ( ( $filemtime == 0) or (!is_numeric($filemtime)) ) {
			$err['code'] = FALSE;
			$err['message'] = "[ERROR] Input file/directory mtime invalid: " . $remote_filename;
			return $err;
		}
	}

	$err['code'] = TRUE;
	$err['value'] = $filemtime;

	return $err;
}

function ssh_file_cmp_isupdated($server, $remote_file, $local_file) {
global $jconf;

	$err = array();
	$err['value'] = false;
	$err['code'] = false;
	$err['message'] = "-";
	$err['command'] = null;
	$err['command_output'] = null;
	$err['result'] = 0;

	// File already exists in temp area
	if ( file_exists($local_file) ) {

		//// Filesize and file mtime check
		// Get local filesize
		$local_filesize = filesize($local_file);
		// Get local file mtime
		$local_filemtime = filemtime($local_file);
		// Get remote filesize
		$err2 = ssh_filesize($server, $remote_file);
		if ( !$err2['code'] ) {
			$err['message'] = $err2['message'];
			$err['command'] = $err2['command'];
			return $err;
		}
		$remote_filesize = $err2['value'];
		// Get remote file mtime
		$err2 = ssh_filemtime($server, $remote_file);
		if ( !$err2['code'] ) {
			$err['message'] = $err2['message'];
			$err['command'] = $err2['command'];
			return $err;
		}
		$remote_filemtime = $err2['value'];

		// File size match and file mtime check: do we need to download?
		if ( ( $local_filesize == $remote_filesize ) and ( $local_filemtime >= $remote_filemtime ) ) {
			// File is up to date
			$err['message']  = "[OK] File is up to date.\n";
			$err['value'] = true;
		} else {
			// File is not up to date
			$err['message']  = "[REDOWNLOAD] File is NOT up to date.\n";
			$err['value'] = false;
		}

		// Log check results
		$err['message'] .= "Local file: " . $local_file . " (size = " . $local_filesize . ", mtime = " . date("Y-m-d H:i:s", $local_filemtime) . ")\n";
		$err['message'] .= "Remote file: " . $remote_file . " (size = " . $remote_filesize . ", mtime = " . date("Y-m-d H:i:s", $remote_filemtime) . ")\n";
		$err['code'] = true;
	} else {
		$err['message'] = "[OK] Local file does not exists. Download will be executed (" . $local_file . "\n";
		$err['code'] = true;
	}

	return $err;
}


function ssh_filecopy_from($server, $file, $destination) {
global $app;

	$err = array();

	$command = "scp -B -i " . $app->config['ssh_key'] . " " . $app->config['ssh_user'] . "@" . $server . ":" . $file . " " . $destination . " 2>&1";
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
global $app;

	$err = array();

	$ssh_command = "ssh -i " . $app->config['ssh_key'] . " " . $app->config['ssh_user'] . "@" . $server . " ";

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
global $app;

	$err = array();

	$ssh_command = "ssh -i " . $app->config['ssh_key'] . " " . $app->config['ssh_user'] . "@" . $server . " ";

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

// download only
function ssh_filecopy($server, $file_src, $file_dst) {
global $app, $jconf;

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

	$command = "scp -B -i " . $app->config['ssh_key'] . " " . $app->config['ssh_user'] . "@" . $server . ":" . $file_src . " " . $file_dst . " 2>&1";
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

// new: download or upload
function ssh_filecopy2($server, $file_src, $file_dst, $isdownload = true) {
global $app, $jconf;

	// SSH check file size before start copying
	if ( $isdownload ) {
		$err = ssh_filesize($server, $file_src);
		if ( !$err['code'] ) {
			return $err;
		}
		$filesize = $err['value'];
	} else {
		$filesize = filesize($file_src);
	}

	$err = array();

	// Check available disk space (input media file size * 5 is the minimum)
	if ( $isdownload ) {
		$available_disk = floor(disk_free_space($jconf['media_dir']));
		if ( $available_disk < $filesize * 5 ) {
			$err['command'] = "php: disk_free_space(" . $jconf['media_dir'] . ")";
			$err['result'] = $available_disk;
			$err['code'] = false;
			$err['message'] = "[ERROR] Not enough free space to start conversion (available: " . ceil($available_disk / 1024 / 1024) . "Mb, filesize: " . ceil($filesize / 1024 / 1024) . ")";
			return $err;
		}
		$command = "scp -B -r -i " . $app->config['ssh_key'] . " " . $app->config['ssh_user'] . "@" . $server . ":" . $file_src . " " . $file_dst . " 2>&1";
	} else {
		$command = "scp -B -r -i " . $app->config['ssh_key'] . " " . $file_src . " " . $app->config['ssh_user'] . "@" . $server . ":" . $file_dst . " 2>&1";
	}

	$time_start = time();
	exec($command, $output, $result);
	$duration = time() - $time_start;
	$mins_taken = round( $duration / 60, 2);
	$err['command'] = $command;
    $err['result'] = $result;
	$output_string = implode("\n", $output);
	if ( $result != 0 ) {
		$err['code'] = false;
		$err['message'] = "[ERROR] SCP " . ($isdownload?"download":"upload") . " failed.";
		return $err;
	}

	$err['code'] = true;
	$err['value'] = $duration;
	$err['message'] = "[OK] SCP " . ($isdownload?"download":"upload") . " finished (in " . $mins_taken . " mins)";

	return $err;
}

// SSH: chmod/chown remote files
function sshMakeChmodChown($server, $file, $isdirectory) {
global $app, $jconf;

	$err = array();
	$err['code'] = false;
	$err['value'] = 0;

	$permissions = $jconf['file_access'];
	if ( $isdirectory ) $permissions = $jconf['directory_access'];

	// SSH command template
	$ssh_command = "ssh -i " . $app->config['ssh_key'] . " " . $app->config['ssh_user'] . "@" . $server . " ";
	// Shell command
	$chmod_command = "chmod -f -R " . $permissions . " " . $file . " 2>&1 ; chown -f -R " . $jconf['file_owner'] . " " . $file . " 2>&1";
	$command = $ssh_command . "\"" . $chmod_command . "\"";
	exec($command, $output, $result);
	$output_string = implode("\n", $output);
	if ( $result != 0 ) {
		$err['message'] = "[WARN] SCP cannot stat " . $app->config['ssh_user'] . "@" . $server . ":" . $file . " file.\n";
		return $err;
	}

	$err['code'] = true;
	$err['message'] = "[OK] SCP stat " . $app->config['ssh_user'] . "@" . $server . ":" . $file . " file.\n";

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

// -------------------------------------------------------------------------
// |				    Process handling related functions				   |
// -------------------------------------------------------------------------

// *************************************************************************
// *					function is_process_running()	  				   *
// *************************************************************************
// Process by PID
function is_process_running($PID) {

	exec("ps $PID", $ProcessState);
	return(count($ProcessState) >= 2);
}

// *************************************************************************
// *					function is_process_closedfile()				   *
// *************************************************************************
// Description: is a specific process closed a file?
// INPUTS:
//	- $file: file
//	- $PID: process ID
// OUTPUTS:
//  - $err array:
//	  o 'code': boolean TRUE/FALSE (operation status)
//	  o 'command': executed command
//	  o 'result': 0
//	  o 'message': textual message offered for logging
function is_process_closedfile($file, $PID) {

	$err['command'] = "-";
	$err['command_output'] = "-";
	$err['result'] = 0;

	if ( !file_exists($file) ) {
		$err['code'] = FALSE;
        $err['message'] = "[ERROR] File does not exist: " . $file;
		return $err;
	}

	$command = "lsof -t " . $file;
	$lsof = `$command`;
	$err['command'] = $command;
	$lsof_output = trim($lsof);
	$err['command_output'] = $lsof_output;
	if ( empty($lsof_output) ) {
		$err['code'] = TRUE;
		return $err;
	} else {
		$err['code'] = FALSE;
        $err['message'] = "[ERROR] Unexpected command output from: " . $command;
		return $err;
	}

	// Check if PID is provided
	$PID_working = (int)$lsof_output;
	if ( is_numeric($PID_working) ) {
		$err['code'] = FALSE;
        $err['message'] = "[MSG] File is opened by process " . $PID_working;
		return $err;
	}

	return $err;
}

// Number of processes by name (regexp)
function checkProcessExists($processName) {
    exec("ps uax | grep -i '$processName' | grep -v grep", $pids);
    return count($pids);
}

// List processes with start time
function checkProcessStartTime($processName) {

    $process_list = array();

    exec("ps -eo etime,pid,command | grep -i '$processName' | grep -v grep", $pids);

    for ( $i = 0; $i < count($pids); $i++ ) {
        $process_info = preg_split("/[\s]+[0-9]+[\s]+/", $pids[$i]);
        $process_stime = trim($process_info[0]);
        $tmp = explode("-", $process_stime, 2);
        if ( count($tmp) > 1 ) {
            $process_days = $tmp[0];
            $process_hhmmss = $tmp[1];
        } else {
            $process_days = 0;
            $process_hhmmss = $tmp[0];
        }
        if ( count(explode(":", $process_hhmmss)) < 3 ) $process_hhmmss = "00:" . $process_hhmmss;
        $time_parsed = date_parse($process_hhmmss);
        $process_time_running = $process_days * 24 * 3600 + $time_parsed['hour'] * 3600 + $time_parsed['minute'] * 60 + $time_parsed['second'];
        
        array_push($process_list, array(0 => $process_time_running, 1 => $process_info[1]));
    }

    return $process_list;
}

function runOverControl($myjobid) {
global $app, $jconf, $debug;

    $goahead = true;

    $processes = checkProcessStartTime("php.*" . $jconf['job_dir'] . $myjobid . ".php");
    if ( count($processes) > 1 ) {
        
        $process_longest = 0;
        $msg = "";
        
        for ( $i = 0; $i < count($processes); $i++ ) {
            if ( $process_longest < $processes[$i][0] ) $process_longest = $processes[$i][0];
            $msg .= floor($processes[$i][0] / ( 24 * 3600 ) ) . "d " . secs2hms($processes[$i][0] % ( 24 * 3600 )) . " " . $processes[$i][1] . "\n";
        }
        
        // Do not send alarm if DB is down
        if ( !file_exists($app->config['dbunavailableflagpath']) ) {
            $debug->log($jconf['log_dir'], $myjobid . ".log", "[WARN] Job " . $myjobid . " runover was detected. Job info (running time, process):\n" . $msg, $sendmail = false);
        }
        
        $goahead = false;
    }

    return $goahead;
}

// *************************************************************************
// *					function db_maintain()							   *
// *************************************************************************

function db_maintain($nonblockingmode = false) {
 global $app, $db, $jconf, $debug;

    // Does resource still exist? - NEM OK, ha elveszik a kapcsolat, attól ez még resource!
/* 	if ( !empty($db) ) {
		if ( is_resource($db->_connectionID) ) {
            echo "dbcheck: ok\n";
            return $db;    
        }
	} */
 
    // Check DBUNAVAILABLE file, sleep until it exists
    if ( !$nonblockingmode ) dbWait4Recovery();
 
	$job = getRunningJobName();

	// Sleep time to start from
	$sleep_time = 30;		// (1: 30 secs, 2: 1 min, 3: 2 mins, 4: 4 mins, 5: 8 mins, 6: 16 mins, 7: 32 mins, 8: 64 mins)

	// Prepare possibly needed mail intro with site information
	$mail_head  = "NODE: " . $app->config['node_sourceip'] . "\n";
	$mail_head .= "SITE: " . $app->config['baseuri'] . "\n";
	$mail_head .= "JOB: " . $job . ".php\n";

    $outage_starttime = time();
    
	$retry = 1;
	while ( 1 ) {

        // Watchdog timer
        $app->watchdog();
    
        // Exit if stop file is enabled in meanwhile
        // ???? nem jo ez itt, nem szabad kilepni?????
		//if ( is_file( $app->config['datapath'] . 'jobs/' . $job . '.stop' ) or is_file( $app->config['datapath'] . 'jobs/all.stop' ) ) exit;

		// Establish database connection
		try {
			$db = $app->bootstrap->getAdoDB();
			if ( is_resource($db->_connectionID) ) {
				// Send mail when recovered
				if ( $retry > 1 ) {
                    $recovery_time = time() - $outage_starttime;
					$title = "[OK] DB connection restored in " . seconds2DaysHoursMinsSecs($recovery_time) . ". Retried: " . $retry . ".\n\nJob continues to run.";
					$body  = $mail_head . "\n" . $title . "\n\nWARNING: DB outage recovered?\n";
					//if ( $nonblockingmode ) sendHTMLEmail_errorWrapper($title, nl2br($body));
                    $debug->log($jconf['log_dir'], $job . ".log", $title, $sendmail = false);
				}
				return $db;
			}
		} catch (exception $err) {
			// Log warning messages at first and every 8th retry (approx. hourly)
			if ( ( $retry == 1 ) or ( ( $retry % 8 ) == 0 ) ) {
                $outage_time = time() - $outage_starttime;
				$title = "[ERROR] Cannot connect to DB (retry: " . $retry . "). DB recovery has been tried for " . seconds2DaysHoursMinsSecs($outage_time) . ". Job operation is suspended.";
				$body  = $mail_head . "\n" . $title . "\n\nPlease check DB connections!\n\nError message:\n" . $err . "\n";
				//if ( $nonblockingmode ) sendHTMLEmail_errorWrapper($title, nl2br($body));
                $debug->log($jconf['log_dir'], $job . ".log", $title . " Error message:\n" . $err, $sendmail = false);
			}
		}

        // Return when non blocking mode is selected
        if ( $nonblockingmode ) {
            return false;
        } else {
            // Check DBUNAVAILABLE file, wait until it exists
            dbWait4Recovery();
        }

		$retry++;

		// Sleep some time then try again
		sleep($sleep_time);
        
        // Increase retry timeout (until a certain point)
        if ( $retry < 8 ) $sleep_time = $sleep_time * 2;
	}

    // Permanent error. We do not allow this to happen.
/*	$title = "[FATAL ERROR] Cannot connect to DB permanently. Check DB!!! Job has been terminated.";
	$body  = $mail_head . "<br/>" . $title . "<br/><br/>Job will be restarted, config will be reloaded. Please check DB for errors!<br/>";
	sendHTMLEmail_errorWrapper($title, $body); */

	exit -1;
}

function dbWait4Recovery() {
global $app;

    $retry = 1;
    $sleep_time = 30;
    
    // Does DBUNAVAILABLE file exist?
    while ( file_exists($app->config['dbunavailableflagpath']) ) {
        
        // Sleep for a while
        sleep($sleep_time);
        
        // Watchdog timer
        $app->watchdog();
        
        // Increase retry timeout (until a certain point) - 1: 30 sec, 2: 60 sec
        if ( $retry <= 2 ) $sleep_time = $sleep_time * 2;

        $retry++;
    }
    
    return true;
}

function sendHTMLEmail_errorWrapper($title, $body, $sendhtml = true) {
 global $app;

	$queue = $app->bootstrap->getMailqueue(TRUE);
	$queue->instant = TRUE;
	foreach ( $app->bootstrap->config['logemails'] as $email ) {
		if ( $sendhtml ) {
			$queue->sendHTMLEmail($email, $title, $body);
		} else {
			$queue->put($email, '', $title, $body, false, 'text/plain');
		}
	}

	return TRUE;
}

// Return job name (without extension)
function getRunningJobName() {
	$tmp = pathinfo(realpath($_SERVER['argv'][0]));
	return $tmp['filename'];
}

// Check if private IP
function isIpPrivate($ip) {
    $pri_addrs = array (
        '10.0.0.0|10.255.255.255',      // single class A network
        '172.16.0.0|172.31.255.255',    // 16 contiguous class B network
        '192.168.0.0|192.168.255.255',  // 256 contiguous class C network
        '169.254.0.0|169.254.255.255',  // Link-local address also refered to as Automatic Private IP Addressing
        '127.0.0.0|127.255.255.255'     // localhost
    );

    $long_ip = ip2long($ip);
    if ($long_ip != -1) {

        foreach ($pri_addrs AS $pri_addr) {
            list ($start, $end) = explode('|', $pri_addr);

            // IF IS PRIVATE
            if ($long_ip >= ip2long($start) && $long_ip <= ip2long($end)) {
                return true;
            }
        }
    }

    return false;
}

?>
