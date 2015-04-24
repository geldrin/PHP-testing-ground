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
$app = new Springboard\Application\Cli(BASE_PATH, false);

// Load jobs configuration file
$app->loadConfig('modules/Jobs/config_jobs.php');
$jconf = $app->config['config_jobs'];
$myjobid = $jconf['jobid_document_index'];
$myjobpath = $jconf['job_dir'] . $myjobid . ".php";

// Log related init
$thisjobstarted = time();
$debug = Springboard\Debug::getInstance();
$debug->log($jconf['log_dir'], $myjobid . ".log", "*************************** Job: Document index ***************************" ."\n", $sendmail = false);

if ( iswindows() ) {
	echo "ERROR: Non-Windows process started on Windows platform\n";
	exit;
}

// Start an infinite loop - exit if any STOP file appears
while( !is_file( $app->config['datapath'] . 'jobs/' .$myjobid . '.stop' ) and !is_file( $app->config['datapath'] . 'jobs/all.stop' ) ) {

	clearstatcache();
    
    // Check job file modification - if more fresh version is available, then restart
    if ( filemtime($myjobpath) > $thisjobstarted ) {
        $debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] Seems like an updated version is available of me. Exiting...", $sendmail = false);
        exit;
    }

    while ( 1 ) {

		$app->watchdog();

		// Establish database connection
		$db = null;
		$db = db_maintain();

		$converter_sleep_length = $app->config['sleep_media'];

		// Check if temp directory readable/writable
		if ( !is_writable($jconf['doc_dir']) ) {
			$debug->log($jconf['log_dir'], $jconf['jobid_document_index'] . ".log", "[FATAL ERROR] Temp directory " . $jconf['doc_dir'] . " is not writable. Storage error???\n", $sendmail = true);
			// Sleep one hour then resume
			$converter_sleep_length = 60 * 60;
			break;
		}

		// Temporary directory cleanup and log result
		$err = tempdir_cleanup($jconf['doc_dir']);
		if ( !$err['code'] ) {
			log_document_conversion(0, 0, $jconf['jobid_document_index'], "-", $err['message'], $err['command'], $err['result'], 0, true);
			$converter_sleep_length = 15 * 60;
			break;
		}

		// Query next job
		unset($attached_doc);
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
			$converter_sleep_length = 15 * 60;
			break;
		}

		// Copy slide file to converter, if cannot copy then we go to sleep
		if ( !copy_attacheddoc_to_converter($attached_doc) ) break;

		// Set status to indexing
        updateAttachedDocumentStatus($attached_doc['id'], $jconf['dbstatus_indexing'], 'indexingstatus');

		// Check file size
		$attached_doc['filesize'] = filesize($attached_doc['source_file']);
		$global_log .= "Filesize: " . sprintf("%.2f", $attached_doc['filesize'] / 1024 ) . " Kbyte\n";

		// Identification: text vs. data
		$file_type = file_identify($attached_doc['source_file']);
		if ( $file_type !== false ) {

			$attached_doc['file_unix_type'] = $file_type;
			// DB: update document type
			$update = array(
				'type'	=> $attached_doc['file_unix_type']
			);
			$attDoc = $app->bootstrap->getModel('attached_documents');
			$attDoc->select($attached_doc['id']);
			$attDoc->updateRow($update);

			// Text file, XML document, CSV, HTML, DOCX, PPTX, ODT, ODP or other text
			if ( stripos($file_type, "text") !== false ) {
				$attached_doc['file_type'] = "text";
			}

			// PDF file
			if ( stripos($file_type, "PDF") !== false ) {
				$attached_doc['file_type'] = "pdf";
			}

			// Data file
			if ( stripos($file_type, "data") !== false ) {
				$attached_doc['file_type'] = "data";
			}

			// Executable
			if ( stripos($file_type, "executable") !== false ) {
				$attached_doc['file_type'] = "executable";
				// Update media status to invalid input
                updateAttachedDocumentStatus($attached_doc['id'], $jconf['dbstatus_invalidinput'], 'indexingstatus');
				// Send a warning to admin
				$debug->log($jconf['log_dir'], $jconf['jobid_document_index'] . ".log", "[WARNING] Document to be indexed is an executable. Not indexing. Unix type: " . $file_type, $sendmail = true);
				break;
			}

		}

		// Document type arrays
		$types_text = array("txt", "csv", "xml");
		$types_doc  = array("htm", "html", "doc", "docx", "odt", "ott", "sxw");
		$types_pres  = array("ppt", "pptx", "pps", "odp");
		$types_pdf  = array("pdf");

		// Add titles to document cache
		$contents = "";
		if ( !empty($attached_doc['title']) ) $contents .= trim($attached_doc['title']) . "\n";

		// Text document: insert text to database, no conversion
		if ( ( array_search($attached_doc['masterextension'], $types_text) !== false ) AND ( $attached_doc['file_type'] == "text" ) ) {
			$content_file = $attached_doc['source_file'];
		}

		// Presentation: convert to PDF before extracting text
		$is_pres_pdf_source = false;
		if ( array_search($attached_doc['masterextension'], $types_pres) !== false ) {

			$content_file = $attached_doc['temp_directory'] . $attached_doc['id'] . ".txt";
			$content_pdf = $attached_doc['temp_directory'] . $attached_doc['id'] . ".pdf";

			// Launch unoconv
			$command = "unoconv -f pdf " . $attached_doc['source_file']. " 2>&1";
			exec($command, $output, $result);
			$output_string = implode("\n", $output);
			// unoconv: sometimes it returns "Floating point exception", but result is produced. Maybe output is truncated?
			if ( ( $result != 0 ) and ( stripos($output_string, "Floating point exception") === false ) ) {
                updateAttachedDocumentStatus($attached_doc['id'], $jconf['dbstatus_indexing_err'], 'indexingstatus');
				log_document_conversion($attached_doc['id'], $attached_doc['rec_id'], $jconf['jobid_document_index'], $jconf['dbstatus_indexing'], "[ERROR] unoconv conversion error.\n\n" . $output_string, $command, $result, 0, true);
				break;
			}

			$is_pres_pdf_source = true;
		}

		// PDF: use pdftotext to extract plain text then check if valid text file
		if ( ( ( array_search($attached_doc['masterextension'], $types_pdf) !== false ) AND ( $attached_doc['file_type'] == "pdf" ) ) OR ( $is_pres_pdf_source == true ) ) {

			// Presentation: if converted to PDF first, change input file
			$source_file = $attached_doc['source_file'];
			if ( $is_pres_pdf_source ) $source_file = $content_pdf;

			$content_file = $attached_doc['temp_directory'] . $attached_doc['id'] . ".txt";

			// Call pdftotext
			$command = "pdftotext -q -nopgbrk -layout -enc UTF-8 " . $source_file;
			exec($command, $output, $result);
			$output_string = implode("\n", $output);
			if ( $result != 0 ) {
                updateAttachedDocumentStatus($attached_doc['id'], $jconf['dbstatus_indexing_err'], 'indexingstatus');
				log_document_conversion($attached_doc['id'], $attached_doc['rec_id'], $jconf['jobid_document_index'], $jconf['dbstatus_indexing'], "[ERROR] pdftotext failed.\n\n" . $output_string, $command, $result, 0, true);
				break;
			}

			// Check output if text: pdftotext can provide crap output (based on PDF character encoding) - GIVES WRONG OUTPUT SOMETIMES
/*			$file_type = file_identify($content_file);
			if ( ( $file_type === false ) OR ( stripos($file_type, "text") === false ) ) {
                updateAttachedDocumentStatus($attached_doc['id'], $jconf['dbstatus_indexing_err'], 'indexingstatus');
				log_document_conversion($attached_doc['id'], $attached_doc['rec_id'], $jconf['jobid_document_index'], $jconf['dbstatus_indexing'], "[WARNING] pdftotext output is not text.\n\n" . $output_string, $command, $result, 0, true);
				break;
			}
*/
		}

		// Other documents: use unoconv to extract text
		if ( ( array_search($attached_doc['masterextension'], $types_doc) !== false ) AND ( $attached_doc['file_type'] == "text" OR $attached_doc['file_type'] == "data" ) ) {

			$content_file = $attached_doc['temp_directory'] . $attached_doc['id'] . ".txt";

			// Launch unoconv
			$command = "unoconv -f txt " . $attached_doc['source_file']. " 2>&1";
			exec($command, $output, $result);
			$output_string = implode("\n", $output);
			// unoconv: sometimes it returns "Floating point exception", but result is produced. Maybe output is truncated.
			if ( ( $result != 0 ) and ( $output_string != "Floating point exception" ) ) {
                updateAttachedDocumentStatus($attached_doc['id'], $jconf['dbstatus_indexing_err'], 'indexingstatus');
				log_document_conversion($attached_doc['id'], $attached_doc['rec_id'], $jconf['jobid_document_index'], $jconf['dbstatus_indexing'], "[ERROR] unoconv conversion error.\n\n" . $output_string, $command, $result, 0, true);
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
            updateAttachedDocumentStatus($attached_doc['id'], $jconf['dbstatus_indexing_err'], 'indexingstatus');
			log_document_conversion($attached_doc['id'], $attached_doc['rec_id'], $jconf['jobid_document_index'], $jconf['dbstatus_indexing'], "[ERROR] Indexing output file does not exist or zero size. File: " . $content_file . "\n", "-", "-", 0, true);
			break;
		}

		$tmp = mb_convert_encoding($contents, "UTF-8");
		$contents = $tmp;

		// Update document cache and status
		if ( empty($contents) ) {
            updateAttachedDocumentStatus($attached_doc['id'], $jconf['dbstatus_indexing_empty'], 'indexingstatus');
		} else {
            updateAttachedDocumentCache($attached_doc['id'], $contents);
			// Update recording search cache
			$recObj = $app->bootstrap->getModel('recordings');
			$recObj->select($attached_doc['rec_id']);
			$recObj->updateFulltextCache();
            updateAttachedDocumentStatus($attached_doc['id'], $jconf['dbstatus_indexing_ok'], 'indexingstatus');
		}
	
		$indexing_duration = time() - $total_duration;
		$hms = secs2hms($indexing_duration);

		$global_log .= "Indexed text: " . sprintf("%.2f", $attached_doc['documentcache_size'] / 1024 ) . " Kbyte\n";

		log_document_conversion($attached_doc['id'], $attached_doc['rec_id'], $jconf['jobid_document_index'], "-", "[OK] Successful document indexation in " . $hms . " time.\n\n" . $global_log, "-", "-", $indexing_duration, false);

		break;
    }

	// Close DB connection if open
	if ( is_resource($db->_connectionID) ) $db->close();

    $app->watchdog();

	sleep($converter_sleep_length);	
}

exit;

// Check file type
function file_identify($filename) {
 global $jconf;

	$command = "file -z -b " . $filename;
	exec($command, $output, $result);
	$output_string = implode("\n", $output);
	if ( $result != 0 ) {
		$debug->log($jconf['log_dir'], $jconf['jobid_document_index'] . ".log", "[WARNING] file command output error. Command:\n" . $command . "\nError message:\n" . $output_string, $sendmail = true);
		return false;
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
 global $db, $app, $jconf;

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
		( a.masterextension IN (\"txt\", \"csv\", \"xml\", \"htm\", \"html\", \"doc\", \"docx\", \"odt\", \"ott\", \"sxw\", \"pdf\", \"ppt\", \"pptx\", \"pps\", \"odp\") ) AND
		( a.indexingstatus IS NULL OR a.indexingstatus = \"\" ) AND
		a.userid = b.id
    LIMIT 1";

//echo $query . "\n";

  try {
    $rs = $db->Execute($query);
  } catch (exception $err) {
    log_document_conversion(0, 0, $jconf['jobid_document_index'], $jconf['dbstatus_init'], "[ERROR] SQL query failed", trim($query), $err, 0, true);
    return false;
  }

//echo "recs: " . $rs->RecordCount() . "\n";

  // Check if pending job exists
  if ( $rs->RecordCount() < 1 ) {
    return false;
  }

  $attached_doc = $rs->fields;

  return true;
}

function copy_attacheddoc_to_converter(&$attached_doc) {
 global $db, $app, $jconf;

	// Update watchdog timer
	$app->watchdog();

	// Update media status to copying
    updateAttachedDocumentStatus($attached_doc['id'], $jconf['dbstatus_copyfromfe'], 'indexingstatus');

	// Prepare temporary conversion directories, remove any existing content
	$temp_directory = $jconf['doc_dir'] . $attached_doc['rec_id'] . "/";
	$err = create_remove_directory($temp_directory);
	if ( !$err['code'] ) {
		log_document_conversion($attached_doc['id'], $attached_doc['rec_id'], $jconf['jobid_document_index'], $jconf['dbstatus_copyfromfe'], $err['message'], $err['command'], $err['result'], 0, true);
		return false;
	}

	// Path and filename
	$remote_path = $app->config['recordingpath'] . ( $attached_doc['rec_id'] % 1000 ) . "/" . $attached_doc['rec_id'] . "/attachments/";
	$base_filename = $attached_doc['id'] . "." . $attached_doc['masterextension'];
	$remote_filename = $app->config['ssh_user'] . "@" . $attached_doc['sourceip'] . ":" . $remote_path . $base_filename;
	$master_filename = $temp_directory . $base_filename;

	$attached_doc['remote_filename'] = $remote_filename;
	$attached_doc['source_file'] = $master_filename;
	$attached_doc['temp_directory'] = $temp_directory;

	// SCP copy from remote location
	$err = ssh_filecopy2($attached_doc['sourceip'], $remote_path . $base_filename, $attached_doc['source_file']);
	if ( !$err['code'] ) {
		log_document_conversion($attached_doc['id'], $attached_doc['rec_id'], $jconf['jobid_document_index'], $jconf['dbstatus_copyfromfe'], $err['message'], $err['command'], $err['result'], 0, true);
        updateAttachedDocumentStatus($attached_doc['id'], $jconf['dbstatus_copyfromfe_err'], 'indexingstatus');
		return false;
	}
	log_document_conversion($attached_doc['id'], $attached_doc['rec_id'], $jconf['jobid_document_index'], $jconf['dbstatus_copyfromfe'], $err['message'], $err['command'], $err['result'], 0, false);

	// Input file does not exist in temp directory
	if ( !file_exists($attached_doc['source_file']) ) {
        updateAttachedDocumentStatus($attached_doc['id'], $jconf['dbstatus_copyfromfe_err'], 'indexingstatus');
		log_document_conversion($attached_doc['id'], $attached_doc['rec_id'], $jconf['jobid_document_index'], $jconf['dbstatus_copyfromfe'], "[ERROR] Document file does NOT EXIST: " . $attached_doc['source_file'], "-", "-", 0, true);
		return false;
	}

	// Update watchdog timer
	$app->watchdog();

	return true;
}



?>