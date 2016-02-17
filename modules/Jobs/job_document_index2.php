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
include_once('job.attachment.class.php');

set_time_limit(0);

// Init
$att = new Attachment('jobid_document_index2');

// WORKAROUND!!!
$app = $att->app;
$jconf = $app->config['config_jobs'];
// WORKAROUND!!!

$att->debugLog("*************************** Job: Document index ***************************", false);

if ( iswindows() ) {
	echo "ERROR: Non-Windows process started on Windows platform\n";
	exit;
}

// Start an infinite loop - exit if any STOP file appears
while( !is_file( $att->config['datapath'] . 'jobs/' . $att->jobid . '.stop' ) and !is_file( $att->config['datapath'] . 'jobs/all.stop' ) ) {

	clearstatcache();
    
    // Check job file modification - if more fresh version is available, then restart
    if ( $att->configChangeOccured() ) {
        $att->debugLog("[INFO] Seems like an updated version is available of me or config file has been changed. Exiting...", false);
        exit;
    }

    while ( 1 ) {

		$att->watchdog();

		$converter_sleep_length = $att->config['sleep_media'];

		// Temporary directory cleanup and log result
		$err = tempdir_cleanup($att->config_jobs['doc_dir']);
		if ( !$err['code'] ) {
            $att->debugLog("[ERROR] Temporary directory cleanup error.\nERROR MSG: " . $err['message'] . "\nCOMMAND: " . $err['command'] . "\nRESUlT: " . $err['result'], true);
			$converter_sleep_length = 15 * 60;
			break;
		}

		// Query next job
		$err = $att->getNextAttachedDocumentJob();
		if ( $err === false ) break;
        
        // Debug
        if ( $att->debug_mode ) $att->debugLog("[DEBUG] Attached document to process:\n" . print_r($att->doc, true), false);

		// Get indexing start time
		$total_duration = time();

		// Start global log for email reports
		$global_log  = "";
		$global_log .= "Start time: " . date("Y-m-d H:i:s.u") . "\n";
		$global_log .= "Source front-end: " . $att->doc['sourceip'] . "\n";
		$global_log .= "Status: " . $att->doc['status'] . "\n";
		$global_log .= "Recording: " . $att->doc['rec_id'] . "\n";
		$global_log .= "Recording URL: http://" . $att->config['baseuri'] . "hu/recordings/details/" . $att->doc['rec_id'] . "\n\n";
		$global_log .= "Original filename: " . $att->doc['masterfilename'] . "\n";
		$global_log .= "Filename: " . $att->doc['id'] . "." . $att->doc['masterextension'] . "\n";

		// User information
		$uploader_user['email'] = $att->doc['email'];
		$uploader_user['nickname'] = $att->doc['nickname'];
		$uploader_user['userid'] = $att->doc['userid'];
        
        if ( $att->debug_mode ) $att->debugLog("[DEBUG] Uploader user:\n" . print_r($uploader_user, true), false);

		// Check if OpenOffice listener is running
		if ( !soffice_isrunning() ) {
			$att->debugLog("[ERROR] OpenOffice is not running. Indexing failed.\n", true);
			$converter_sleep_length = 15 * 60;
			break;
		}

		// Copy attached document to converter
		if ( !$att->copyAttachedDocumentToConverter() ) break;

		// Check file size
		$global_log .= "Filesize: " . sprintf("%.2f", $att->doc['filesize'] / 1024 ) . " Kbyte\n";

		// Identify attached document file (text vs. data)
        $err = $att->identifyAttachedDocument();

		// Document type arrays
		$types_text = array("txt", "csv", "xml");
		$types_doc  = array("htm", "html", "doc", "docx", "odt", "ott", "sxw");
		$types_pres = array("ppt", "pptx", "pps", "odp");
		$types_pdf  = array("pdf");

		// Add title and filename to document cache
		$att->addTextToDocumentCache($att->doc['title']);
        $att->addTextToDocumentCache($att->doc['masterfilename']);

		// Text document: insert text to database, no conversion
		if ( ( array_search($att->doc['masterextension'], $types_text) !== false ) and ( $att->doc['file_type'] == "text" ) ) $att->addTextFileToDocumentCache($att->doc['source_file']);

		// Presentation: convert to PDF before extracting text
		$is_pres_pdf_source = false;
		if ( array_search($att->doc['masterextension'], $types_pres) !== false ) {

            $err = $att->convertUnoConv("pdf");
            $pdf_temp = $err['output_file'];
            
			$is_pres_pdf_source = true;
		}

		// PDF: use pdftotext to extract plain text then check if valid text file
		if ( ( ( array_search($att->doc['masterextension'], $types_pdf) !== false ) and ( $att->doc['file_type'] == "pdf" ) ) or ( $is_pres_pdf_source == true ) ) {

			// Presentation: if converted to PDF first, change input file
			$source_file = $att->doc['source_file'];
			if ( $is_pres_pdf_source ) $source_file = $pdf_temp;
            
            // pdf2text conversion
            $err = $att->convertPdf2Text($source_file);
            $att->addTextFileToDocumentCache($err['output_file']);

			// Check output if text: pdftotext can provide crap output (based on PDF character encoding) - GIVES WRONG OUTPUT SOMETIMES
/*			$file_type = file_identify($content_file);
			if ( ( $file_type === false ) OR ( stripos($file_type, "text") === false ) ) {
                updateAttachedDocumentStatus($attached_doc['id'], $jconf['dbstatus_indexing_err'], 'indexingstatus');
				break;
			}
*/
		}

		// Other documents: use unoconv to extract text
		if ( ( array_search($att->doc['masterextension'], $types_doc) !== false ) and ( $att->doc['file_type'] == "text" or $att->doc['file_type'] == "data" ) ) {
            $err = $att->convertUnoConv("txt");            
            $att->addTextFileToDocumentCache($err['output_file']);
		}
        
        // Update document cache
		if ( empty($att->cache) ) {
            $att->updateAttachedDocumentStatus($att->doc['id'], $att->config_jobs['dbstatus_indexing_empty'], 'indexingstatus');
        } else {
            // Update attached document cache
            $att->updateAttachedDocumentCache($att->doc['id'], $att->cache);
            
            // Update recording search cache
            $recObj = $att->app->bootstrap->getModel('recordings');
            $recObj->select($att->doc['rec_id']);
            $recObj->updateFulltextCache();
            $att->updateAttachedDocumentStatus($att->doc['id'], $att->config_jobs['dbstatus_indexing_ok'], 'indexingstatus');
        }
	
		$indexing_duration = time() - $total_duration;
		$hms = secs2hms($indexing_duration);

		$global_log .= "Indexed text: " . sprintf("%.2f", $att->cache_size / 1024 ) . " Kbyte\n";

        $att->debugLog("[OK] Successful document indexation in " . $hms . " time.\n\n" . $global_log, false);        
   
		break;
    }

    $att->watchdog();
    
    // Kill this document
    $att->killDocument();
    
    // Debug
    if ( $att->debug_mode ) $att->debugLog("[DEBUG] Going to sleep now for " . $converter_sleep_length . " secs.", false);
    
    // Sleep
	sleep($converter_sleep_length);
}

exit;

?>