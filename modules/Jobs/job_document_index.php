<?php

// Document indexer job for Videosquare
// Requirements:
//	o Linux installation.
//	o OpenOffice 3.0 or later installation:
//	  - python-openoffice, python-uno, uno-libs3, unoconv packages
//	o OpenOffice listener running in background:
//	  - soffice.bin --headless --accept=\"socket,host=127.0.0.1,port=8100;urp;\" --nofirststartwizard & > /dev/null 2>&1
//	o Others:
//	  - ghostscript, poppler-utils (pdftotext)

define('BASE_PATH',	realpath( __DIR__ . '/../..' ) . '/' );
define('PRODUCTION', false );
define('DEBUG', false );

include_once( BASE_PATH . 'libraries/Springboard/Application/Cli.php');

// Utils
include_once('job_utils_base.php');
include_once('job_utils_log.php');
include_once('job_utils_status.php');

set_time_limit(0);

// Init
$app = new Springboard\Application\Cli(BASE_PATH, PRODUCTION);

// Load jobs configuration file
$app->loadConfig('modules/Jobs/config_jobs.php');
$jconf = $app->config['config_jobs'];

// Log related init
$debug = Springboard\Debug::getInstance();
$debug->log($jconf['log_dir'], $jconf['jobid_document_index'] . ".log", "Document index job started", $sendmail = false);

if ( iswindows() ) {
	echo "ERROR: Non-Windows process started on Windows platform\n";
	exit;
}

// Start an infinite loop - exit if any STOP file appears
while( !is_file( $app->config['datapath'] . 'jobs/job_document_index.stop' ) and !is_file( $app->config['datapath'] . 'jobs/all.stop' ) ) {

	clearstatcache();
    while ( 1 ) {

		$app->watchdog();
	
		$db_close = FALSE;
		$converter_sleep_length = $jconf['sleep_media'];

		// Check if temp directory readable/writable
		if ( !is_writable($jconf['doc_dir']) ) {
			$debug->log($jconf['log_dir'], $jconf['jobid_document_index'] . ".log", "[FATAL ERROR] Temp directory " . $jconf['doc_dir'] . " is not writable. Storage error???\n", $sendmail = true);
			// Sleep one hour then resume
			$converter_sleep_length = 60 * 60;
			break;
		}

		// Establish database connection
		try {
			$db = $app->bootstrap->getAdoDB();
		} catch (exception $err) {
			// Send mail alert, sleep for 15 minutes
			$debug->log($jconf['log_dir'], $jconf['jobid_document_index'] . ".log", "[ERROR] No connection to DB (getAdoDB() failed). Error message:\n" . $err, $sendmail = true);
			// Sleep 15 mins then resume
			$converter_sleep_length = 15 * 60;
			break;
		}
		$db_close = TRUE;

		// Temporary directory cleanup and log result
		$err = tempdir_cleanup($jconf['doc_dir']);
		if ( !$err['code'] ) {
			log_document_conversion(0, 0, $jconf['jobid_document_index'], "-", $err['message'], $err['command'], $err['result'], 0, TRUE);
			$converter_sleep_length = 15 * 60;
			break;
		}

// Testing!!!
//update_db_attachment_indexingstatus(3, null);
// !!!!!!!!!!

		// Query next job
		$attached_doc = array();
		if ( !query_nextjob($attached_doc) ) break;

		// Get indexing start time
		$total_duration = time();

		// Start global log for email reports
		$global_log  = "";
		$global_log .= "Start time: " . date("Y-m-d H:i:s.u") . "\n";
		$global_log .= "Source front-end: " . $attached_doc['sourceip'] . "\n";
		$global_log .= "Status: " . $attached_doc['status'] . "\n";
		$global_log .= "Recording: " . $attached_doc['rec_id'] . "\n";
		$global_log .= "Recording URL: http://" . $app->config['baseuri'] . "hu/recordings/details/" . $attached_doc['rec_id'] . "\n\n";
		$global_log .= "Original filename: " . $attached_doc['masterfilename'] . "\n";
		$global_log .= "Filename: " . $attached_doc['id'] . "." . $attached_doc['masterextension'] . "\n";

		// User information
		$uploader_user['email'] = $attached_doc['email'];
		$uploader_user['nickname'] = $attached_doc['nickname'];
		$uploader_user['userid'] = $attached_doc['userid'];

		// Check if OpenOffice listener is running
		if ( !soffice_isrunning() ) {
			$debug->log($jconf['log_dir'], $jconf['jobid_document_index'] . ".log", "[ERROR] OpenOffice is not running. Indexing failed.\n", $sendmail = true);
			$converter_sleep_length = 5*60;
			break;
		}

		// Copy slide file to converter, if cannot copy then we go to sleep
		if ( !copy_attacheddoc_to_converter($attached_doc) ) break;

		// Set status to indexing
		update_db_attachment_indexingstatus($attached_doc['id'], $jconf['dbstatus_indexing']);

		// Check file size
		$attached_doc['filesize'] = filesize($attached_doc['source_file']);
		$global_log .= "Filesize: " . sprintf("%.2f", $attached_doc['filesize'] / 1024 ) . " Kbyte\n";

		// Identification: text vs. data
		$file_type = file_identify($attached_doc['source_file']);
		if ( $file_type !== FALSE ) {

			$attached_doc['file_unix_type'] = $file_type;
			// DB: update document type
			$update = array(
				'type'	=> $attached_doc['file_unix_type']
			);
			$attDoc = $app->bootstrap->getModel('attached_documents');
			$attDoc->select($attached_doc['id']);
			$attDoc->updateRow($update);

			// Text file, XML document, CSV, HTML, DOCX, PPTX, ODT, ODP or other text
			if ( stripos($file_type, "text") !== FALSE ) {
				$attached_doc['file_type'] = "text";
			}

			// PDF file
			if ( stripos($file_type, "PDF") !== FALSE ) {
				$attached_doc['file_type'] = "pdf";
			}

			// Data file
			if ( stripos($file_type, "data") !== FALSE ) {
				$attached_doc['file_type'] = "data";
			}

			// Executable
			if ( stripos($file_type, "executable") !== FALSE ) {
				$attached_doc['file_type'] = "executable";
				// Update media status to invalid input
				update_db_attachment_indexingstatus($attached_doc['id'], $jconf['dbstatus_invalidinput']);
				// Send a warning to admin
				$debug->log($jconf['log_dir'], $jconf['jobid_document_index'] . ".log", "[WARNING] Document to be indexed is an executable. Not indexing. Unix type: " . $file_type, $sendmail = true);
				break;
			}

		}

		// Document type arrays
		$types_text = array("txt", "csv", "xml");
		$types_doc  = array("htm", "html", "doc", "docx", "odt", "ott", "sxw");
		$types_pdf  = array("pdf");

		// Add titles to document cache
		$contents = "";
		if ( !empty($attached_doc['title']) ) $contents .= trim($attached_doc['title']) . "\n";

		// Text document: insert text to database, no conversion
		if ( ( array_search($attached_doc['masterextension'], $types_text) !== FALSE ) AND ( $attached_doc['file_type'] == "text" ) ) {
			$content_file = $attached_doc['source_file'];
		}

		// PDF: use pdftotext to extract plain text then check if valid text file
		if ( ( array_search($attached_doc['masterextension'], $types_pdf) !== FALSE ) AND ( $attached_doc['file_type'] == "pdf" ) ) {

			$content_file = $attached_doc['temp_directory'] . $attached_doc['id'] . ".txt";

			// Call pdftotext
			$command = "pdftotext -q -nopgbrk -layout -enc UTF-8 " . $attached_doc['source_file'];
			exec($command, $output, $result);
			$output_string = implode("\n", $output);
			if ( $result != 0 ) {
				update_db_attachment_indexingstatus($attached_doc['id'], $jconf['dbstatus_indexing_err']);
				log_document_conversion($attached_doc['id'], $attached_doc['rec_id'], $jconf['jobid_document_index'], $jconf['dbstatus_indexing'], "[ERROR] pdftotext failed.\n\n" . $output_string, $command, $result, 0, TRUE);
				break;
			}

			// Check output if text: pdftotext can provide crap output (based on PDF character encoding)
			$file_type = file_identify($content_file);
			if ( ( $file_type === FALSE ) OR ( stripos($file_type, "text") === FALSE ) ) {
				update_db_attachment_indexingstatus($attached_doc['id'], $jconf['dbstatus_indexing_err']);
				log_document_conversion($attached_doc['id'], $attached_doc['rec_id'], $jconf['jobid_document_index'], $jconf['dbstatus_indexing'], "[WARNING] pdftotext output is not text.\n\n" . $output_string, $command, $result, 0, TRUE);
				break;
			}

		}

		// Other documents: use unoconv to extract text
		if ( ( array_search($attached_doc['masterextension'], $types_doc) !== FALSE ) AND ( $attached_doc['file_type'] == "text" OR $attached_doc['file_type'] == "data" ) ) {

			$content_file = $attached_doc['temp_directory'] . $attached_doc['id'] . ".txt";

			// Launch unoconv
			$command = "unoconv -f txt " . $attached_doc['source_file']. " 2>&1";
			exec($command, $output, $result);
			$output_string = implode("\n", $output);
			// unoconv: sometimes it returns "Floating point exception", but result is produced. Maybe output is truncated.
			if ( ( $result != 0 ) and ( $output_string != "Floating point exception" ) ) {
				update_db_attachment_indexingstatus($attached_doc['id'], $jconf['dbstatus_indexing_err']);
				log_document_conversion($attached_doc['id'], $attached_doc['rec_id'], $jconf['jobid_document_index'], $jconf['dbstatus_indexing'], "[ERROR] unoconv conversion error.\n\n" . $output_string, $command, $result, 0, TRUE);
				break;
			}

		}

		// Clean up index text file line by line
		if ( file_exists($content_file) AND ( filesize($content_file) > 0 ) ) {
			$fh = fopen($content_file, 'r');
			$line_num = 0;
			while( !feof($fh) ) {
				// Remove empty lines
				$line = trim(fgets($fh));
				$line_num++;
				if ( !empty($line) ) {
					// Remove excess white spaces to squeeze text
					$line_striped = preg_replace('/\s\s+/', ' ', $line);
					$contents .= $line_striped . "\n";
				}
			}
			$attached_doc['documentcache_size'] = strlen($contents);
			fclose($fh);
		} else {
			update_db_attachment_indexingstatus($attached_doc['id'], $jconf['dbstatus_indexing_err']);
			log_document_conversion($attached_doc['id'], $attached_doc['rec_id'], $jconf['jobid_document_index'], $jconf['dbstatus_indexing'], "[ERROR] Indexing output file does not exist or 0 size. File: " . $content_file . "\n", "-", "-", 0, TRUE);
			break;
		}

		$tmp = mb_convert_encoding($contents, "UTF-8");
		$contents = $tmp;

/*
		// Change character encoding to UTF-8 if other format is outputed
		$content_encoding = mb_detect_encoding($contents);
		if ( $content_encoding != "UTF-8" ) {
			if ( empty($content_encoding) OR ( $content_encoding === FALSE ) ) {
				$tmp = mb_convert_encoding($contents, "UTF-8");
			} else {
				$tmp = mb_convert_encoding($contents, "UTF-8", $content_encoding);
			}
			$contents = $tmp;
		}
*/

		// Update document cache and status
		if ( empty($contents) ) {
			update_db_attachment_indexingstatus($attached_doc['id'], $jconf['dbstatus_indexing_empty']);
		} else {
			update_db_attachment_documentcache($attached_doc['id'], $contents);
			// Update recording search cache
			$recObj = $app->bootstrap->getModel('recordings');
			$recObj->select($attached_doc['rec_id']);
			$recObj->updateFulltextCache();
			update_db_attachment_indexingstatus($attached_doc['id'], $jconf['dbstatus_indexing_ok']);
		}
		
		$indexing_duration = time() - $total_duration;
		$hms = secs2hms($indexing_duration);

		$global_log .= "Indexed text: " . sprintf("%.2f", $attached_doc['documentcache_size'] / 1024 ) . " Kbyte\n";

		log_document_conversion($attached_doc['id'], $attached_doc['rec_id'], $jconf['jobid_document_index'], "-", "[OK] Successful document indexation in " . $hms . " time.\n\n" . $global_log, "-", "-", $indexing_duration, TRUE);

		break;
    }

    if ( $db_close ) {
		$db->close();
    }

    $app->watchdog();

	sleep( $converter_sleep_length );	
}

exit;

// Check file type
function file_identify($filename) {
 global $jconf;

	$command = "file -z -b " . $filename;
	exec($command, $output, $result);
	$output_string = implode("\n", $output);
	if ( $result != 0 ) {
		$debug->log($jconf['log_dir'], $jconf['jobid_document_index'] . ".log", "[WARNING] file command output error. Command:\n" . $command . "\nError message:\n" . $output_string, $sendmail = TRUE);
		return FALSE;
	}

	$type = $output_string;

	return $type;
}

// *************************************************************************
// *			function query_nextjob()			   *
// *************************************************************************
// Description: queries next job from database slideconversion table
// INPUTS:
//	- AdoDB DB link in $db global variable
// OUTPUTS:
//	- Boolean:
//	  o FALSE: no pending job for conversion
//	  o TRUE: job is available for conversion
//	- $slide: slideconversion table DB record returned in global variable
function query_nextjob(&$attached_doc) {
 global $db, $jconf;

  $query = "
    SELECT
		a.id,
		a.title,
		a.masterfilename,
		a.masterextension,
		a.isdownloadable,
		a.status,
		a.sourceip,
		a.recordingid as rec_id,
		a.userid,
		b.nickname,
		b.email,
		b.language
	FROM
		attached_documents as a,
		users as b
    WHERE
		a.status = \"" . $jconf['dbstatus_copystorage_ok'] . "\" AND
		( a.masterextension IN (\"txt\", \"csv\", \"xml\", \"htm\", \"html\", \"doc\", \"docx\", \"odt\", \"ott\", \"sxw\", \"pdf\") ) AND
		( a.indexingstatus IS NULL OR a.indexingstatus = \"\" ) AND
		a.userid = b.id
    LIMIT 1";

//echo $query . "\n";

  try {
    $rs = $db->Execute($query);
  } catch (exception $err) {
    log_document_conversion(0, 0, $jconf['jobid_document_index'], $jconf['dbstatus_init'], "[ERROR] SQL query failed", trim($query), $err, 0, TRUE);
    return FALSE;
  }

//echo "recs: " . $rs->RecordCount() . "\n";

  // Check if pending job exists
  if ( $rs->RecordCount() < 1 ) {
    return FALSE;
  }

  $attached_doc = $rs->fields;

  return TRUE;
}

function copy_attacheddoc_to_converter(&$attached_doc) {
 global $db, $app, $jconf;

	// Update watchdog timer
	$app->watchdog();

	// Update media status to copying
	update_db_attachment_indexingstatus($attached_doc['id'], $jconf['dbstatus_copyfromfe']);

	// Prepare temporary conversion directories, remove any existing content
	$temp_directory = $jconf['doc_dir'] . $attached_doc['rec_id'] . "/";
	$err = create_remove_directory($temp_directory);
	if ( !$err['code'] ) {
		log_document_conversion($attached_doc['id'], $attached_doc['rec_id'], $jconf['jobid_document_index'], $jconf['dbstatus_copyfromfe'], $err['message'], $err['command'], $err['result'], 0, TRUE);
		return FALSE;
	}

	// Path and filename
	$remote_path = $app->config['recordingpath'] . ( $attached_doc['rec_id'] % 1000 ) . "/" . $attached_doc['rec_id'] . "/attachments/";
	$base_filename = $attached_doc['id'] . "." . $attached_doc['masterextension'];
	$remote_filename = $jconf['ssh_user'] . "@" . $attached_doc['sourceip'] . ":" . $remote_path . $base_filename;
	$master_filename = $temp_directory . $base_filename;

	$attached_doc['remote_filename'] = $remote_filename;
	$attached_doc['source_file'] = $master_filename;
	$attached_doc['temp_directory'] = $temp_directory;

	// SCP copy from remote location
	$err = ssh_filecopy($attached_doc['sourceip'], $remote_path . $base_filename, $attached_doc['source_file']);
	if ( !$err['code'] ) {
		log_document_conversion($attached_doc['id'], $attached_doc['rec_id'], $jconf['jobid_document_index'], $jconf['dbstatus_copyfromfe'], $err['message'], $err['command'], $err['result'], 0, TRUE);
		update_db_attachment_indexingstatus($attached_doc['id'], $jconf['dbstatus_copyfromfe_err']);
		return FALSE;
	}
	log_document_conversion($attached_doc['id'], $attached_doc['rec_id'], $jconf['jobid_document_index'], $jconf['dbstatus_copyfromfe'], $err['message'], $err['command'], $err['result'], 0, FALSE);

	// Input file does not exist in temp directory
	if ( !file_exists($attached_doc['source_file']) ) {
		update_db_attachment_indexingstatus($attached_doc['id'], $jconf['dbstatus_copyfromfe_err']);
		log_document_conversion($attached_doc['id'], $attached_doc['rec_id'], $jconf['jobid_document_index'], $jconf['dbstatus_copyfromfe'], "[ERROR] Document file does NOT EXIST: " . $attached_doc['source_file'], "-", "-", 0, TRUE);
		return FALSE;
	}

	// Update watchdog timer
	$app->watchdog();

	return TRUE;
}



?>