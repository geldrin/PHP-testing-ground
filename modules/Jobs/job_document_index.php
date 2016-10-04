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
define('JOB_FILE', __FILE__);

include_once( BASE_PATH . 'libraries/Springboard/Application/Cli.php');

// Utils
include_once('job_utils_base.php');
include_once('job.attachment.class.php');

set_time_limit(0);

// Init
$attachedDoc = new Attachment();

// WORKAROUND!!!
$app = $attachedDoc->app;
$jconf = $app->config['config_jobs'];
// WORKAROUND!!!

$attachedDoc->debugLog("*************************** Job: Document index ***************************", false);

if ( iswindows() ) {
	echo "ERROR: Non-Windows process started on Windows platform\n";
	exit;
}

// Start an infinite loop - exit if any STOP file appears
while( !is_file( $attachedDoc->config['datapath'] . 'jobs/' . $attachedDoc->jobid . '.stop' ) and !is_file( $attachedDoc->config['datapath'] . 'jobs/all.stop' ) ) {

	clearstatcache();
	
	// Check if OpenOffice listener is running
	if ( !soffice_isrunning() ) {
		$attachedDoc->debugLog("[ERROR] OpenOffice is not running. Indexing postponed.", true);
		$converter_sleep_length = 15 * 60;
		break;
	}
    
    // Check job file modification - if more fresh version is available, then restart
    if ( $attachedDoc->configChangeOccured() ) {
        $attachedDoc->debugLog("[INFO] Seems like an updated version is available of me or config file has been changed. Exiting...", false);
        exit;
    }
	
    while ( 1 ) {

		$attachedDoc->watchdog();

		$converter_sleep_length = $attachedDoc->config['sleep_doc'];

		// Temporary directory cleanup and log result
		$err = tempdir_cleanup($attachedDoc->config_jobs['doc_dir']);
		if ( !$err['code'] ) {
            $attachedDoc->debugLog("[ERROR] Temporary directory cleanup error.\nERROR MSG: " . $err['message'] . "\nCOMMAND: " . $err['command'] . "\nRESUlT: " . $err['result'], true);
			$converter_sleep_length = 15 * 60;
			break;
		}

		// Query next job
		$attachedDocJobs = $attachedDoc->getAttachedDocumentJobs();
		if ( $attachedDocJobs === false ) break;
        
        while ( !$attachedDocJobs->EOF ) {

            // Get next attached document to process
            $attachedDoc->killDocument();
            $attachedDoc->doc = $attachedDocJobs->fields;

            // Debug
            $attachedDoc->debugLog("[INFO] NEW attached document to process:\n" . print_r($attachedDoc->doc, true), false);

            // Get indexing start time
            $total_duration = time();

            // Start global log for email reports
            $global_log  = "";
            $global_log .= "Start time: " . date("Y-m-d H:i:s.u") . "\n";
            $global_log .= "Source front-end: " . $attachedDoc->doc['sourceip'] . "\n";
            $global_log .= "Status: " . $attachedDoc->doc['status'] . "\n";
            $global_log .= "Recording: " . $attachedDoc->doc['rec_id'] . "\n";
            $global_log .= "Recording URL: http://" . $attachedDoc->config['baseuri'] . "hu/recordings/details/" . $attachedDoc->doc['rec_id'] . "\n\n";
            $global_log .= "Original filename: " . $attachedDoc->doc['masterfilename'] . "\n";
            $global_log .= "Filename: " . $attachedDoc->doc['id'] . "." . $attachedDoc->doc['masterextension'] . "\n";

            // User information
            $uploader_user['email'] = $attachedDoc->doc['email'];
            $uploader_user['nickname'] = $attachedDoc->doc['nickname'];
            $uploader_user['userid'] = $attachedDoc->doc['userid'];
            
            $attachedDoc->debugLog("[INFO] Uploader user:\n" . print_r($uploader_user, true), false);

            // Copy attached document to converter
            if ( !$attachedDoc->copyAttachedDocumentToConverter() ) {
                $attachedDocJobs->MoveNext();
                continue;
            }

            // Check file size
            $global_log .= "Filesize: " . sprintf("%.2f", $attachedDoc->doc['filesize'] / 1024 ) . " Kbyte\n";

            // Identify attached document file (text vs. data)
            if ( !$attachedDoc->identifyAttachedDocument() ) {
                // Update document status to invalid input
                $attachedDoc->updateAttachedDocumentStatus($attachedDoc->config_jobs['dbstatus_invalidinput'], 'indexingstatus');
                $attachedDocJobs->MoveNext();
                continue;
            }
                
            // Add title and filename to document cache
            $attachedDoc->addTextToDocumentCache($attachedDoc->doc['title']);
            $attachedDoc->addTextToDocumentCache($attachedDoc->doc['masterfilename']);

            // Text document: insert text to database, no conversion
            if ( ( array_search($attachedDoc->doc['masterextension'], $attachedDoc->config_jobs['document_types_text']) !== false ) and ( $attachedDoc->doc['file_type'] == "text" ) ) $attachedDoc->addTextFileToDocumentCache($attachedDoc->doc['source_file']);

            // Presentation: convert to PDF before extracting text
            $is_pres_pdf_source = false;
            if ( array_search($attachedDoc->doc['masterextension'], $attachedDoc->config_jobs['document_types_pres']) !== false ) {

                $err = $attachedDoc->convertUnoConv("pdf");
                $pdf_temp = $err['output_file'];
                
                if ( $err['result'] != 0 ) {
                    $attachedDoc->debugLog("[ERROR] Unoconv conversion error.\nCOMMAND: " . $err['command'] . "\nOUTPUT: " . $err['command_output'] . "\nRESULT: " . $err['result'], false);
                    // Permanent unoconv error: report and stop processing
                    if ( ( stripos($err['command_output'], "Floating point exception") === false ) and ( stripos($err['command_output'], "Segmentation fault") === false ) ) {
                        $attachedDoc->updateAttachedDocumentStatus($attachedDoc->config_jobs['dbstatus_indexing_err'], 'indexingstatus');
                        $attachedDocJobs->MoveNext();
                        continue;
                    } else {
                        // WORKAROUND: Sometimes "Floating point exception" or "Segmentation fault" is returned. Check resulting file size, do not set an error if not zero.
                        if ( !file_exists($err['output_file']) ) {
                            $attachedDoc->debugLog("[ERROR] Unoconv conversion error. Output not created. Will start over.\nCOMMAND: " . $err['command'] . "\nOUTPUT: " . $err['command_output'] . "\nRESULT: " . $err['result'], true);
                            $attachedDoc->updateAttachedDocumentStatus(null, 'indexingstatus');
                            $attachedDocJobs->MoveNext();
                            continue;
                        }
                        if ( filesize($err['output_file']) == 0 ) {
                            $attachedDoc->debugLog("[ERROR] Unoconv conversion error. Output is zero size. Will start over.\nCOMMAND: " . $err['command'] . "\nOUTPUT: " . $err['command_output'] . "\nRESULT: " . $err['result'], true);
                            $attachedDoc->updateAttachedDocumentStatus(null, 'indexingstatus');
                            $attachedDocJobs->MoveNext();
                            continue;
                        }
                        
                        $attachedDoc->debugLog("[WARN] Unoconv might ran into an error. However, output seems valid. Going forward.", false);
                    }
                }
                
                $is_pres_pdf_source = true;
            }

            // PDF: use pdftotext to extract plain text then check if valid text file
            if ( ( ( array_search($attachedDoc->doc['masterextension'], $attachedDoc->config_jobs['document_types_pdf']) !== false ) and ( $attachedDoc->doc['file_type'] == "pdf" ) ) or ( $is_pres_pdf_source == true ) ) {

                // Presentation: if converted to PDF first, change input file
                $source_file = $attachedDoc->doc['source_file'];
                if ( $is_pres_pdf_source ) $source_file = $pdf_temp;
                
                // pdf2text conversion
                $err = $attachedDoc->convertPdf2Text($source_file);
                if ( $err['result'] != 0 ) {
                    $attachedDoc->updateAttachedDocumentStatus($attachedDoc->config_jobs['dbstatus_indexing_err'], 'indexingstatus');
                    $attachedDoc->debugLog("[ERROR] pdftotext failed.\nCOMMAND: " . $err['command'] . "\nOUTPUT: " . $err['command_output'] . "\nRESULT: " . $err['result'], true);
                    $attachedDocJobs->MoveNext();
                    continue;
                }
                
                // Add resulting text file to cache
                $attachedDoc->addTextFileToDocumentCache($err['output_file']);
                
                // Check output if text: pdftotext can provide crap output (based on PDF character encoding) - GIVES WRONG OUTPUT SOMETIMES
    /*			$file_type = file_identify($content_file);
                if ( ( $file_type === false ) OR ( stripos($file_type, "text") === false ) ) {
                    updateAttachedDocumentStatus($jconf['dbstatus_indexing_err'], 'indexingstatus');
                    $attachedDocJobs->MoveNext();
                    continue;
                }
    */
            }

            // Other documents: use unoconv to extract text
            if ( ( array_search($attachedDoc->doc['masterextension'], $attachedDoc->config_jobs['document_types_doc']) !== false ) and ( $attachedDoc->doc['file_type'] == "text" or $attachedDoc->doc['file_type'] == "data" ) ) {
                
                $err = $attachedDoc->convertUnoConv("txt");
                
                if ( $err['result'] != 0 ) {
                    $attachedDoc->debugLog("[ERROR] Unoconv conversion error.\nCOMMAND: " . $err['command'] . "\nOUTPUT: " . $err['command_output'] . "\nRESULT: " . $err['result'], true);
                    // Permanent unoconv error: report and stop processing
                    if ( ( stripos($err['command_output'], "Floating point exception") === false ) and ( stripos($err['command_output'], "Segmentation fault") === false ) ) {
                        $attachedDoc->updateAttachedDocumentStatus($attachedDoc->config_jobs['dbstatus_indexing_err'], 'indexingstatus');
                        $attachedDocJobs->MoveNext();
                        continue;
                    } else {
                        // WORKAROUND: Sometimes "Floating point exception" or "Segmentation fault" is returned. Check resulting file size, do not set an error if not zero.
                        if ( !file_exists($err['output_file']) ) {
                            $attachedDoc->debugLog("[ERROR] Unoconv conversion error. Output not created. Will start over.", true);
                            $attachedDoc->updateAttachedDocumentStatus(null, 'indexingstatus');
                            $attachedDocJobs->MoveNext();
                            continue;
                        }
                        if ( filesize($err['output_file']) == 0 ) {
                            $attachedDoc->debugLog("[ERROR] Unoconv conversion error. Output is zero size. Will start over.", true);
                            $attachedDoc->updateAttachedDocumentStatus(null, 'indexingstatus');
                            $attachedDocJobs->MoveNext();
                            continue;
                        }
                    }                
                }

                // Add resulting text file to cache            
                $attachedDoc->addTextFileToDocumentCache($err['output_file']);
            }
            
            // Update document cache
            if ( !$attachedDoc->updateAttachedDocumentCache() ) {
                $attachedDoc->updateAttachedDocumentStatus($attachedDoc->config_jobs['dbstatus_indexing_err'], 'indexingstatus');
                $attachedDocJobs->MoveNext();
                continue;
            }

            // Update recording search cache
            $recObj = $attachedDoc->app->bootstrap->getModel('recordings');
            $recObj->select($attachedDoc->doc['rec_id']);
            $recObj->updateFulltextCache();
            $attachedDoc->updateAttachedDocumentStatus($attachedDoc->config_jobs['dbstatus_indexing_ok'], 'indexingstatus');
        
            // Logging
            $indexing_duration = time() - $total_duration;
            $hms = secs2hms($indexing_duration);
            $global_log .= "Indexed text: " . sprintf("%.2f", $attachedDoc->cache_size / 1024 ) . " Kbyte\n";
            
            $attachedDoc->debugLog("[OK] Successful document indexation in " . $hms . " time.\n\n" . $global_log, false);        

            // Process next
            $attachedDocJobs->MoveNext();
        }
            
		break;
    }

    $attachedDoc->watchdog();
        
    // Debug
    if ( $attachedDoc->debug_mode ) $attachedDoc->debugLog("[DEBUG] Going to sleep now for " . $converter_sleep_length . " secs.", false);
    
    // Sleep
	sleep($converter_sleep_length);
}

exit;