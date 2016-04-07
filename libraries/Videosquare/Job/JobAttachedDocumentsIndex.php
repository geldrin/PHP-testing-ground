<?php
namespace Videosquare\Job;

define('BASE_PATH',	realpath( __DIR__ . '/../../..' ) . '/' );
define('PRODUCTION', false );
define('DEBUG', false );

include_once('Job.php');
include_once('../../../modules/Jobs/job_utils_base.php');

class AttachedDocumentsIndex extends Job {
    
    // Job level config
    protected $needsLoop            = true;
    protected $needsSleep           = true;
    protected $closeDbOnSleep       = true;
    protected $sleepSeconds         = 1;
    protected $maxSleepSeconds      = 5;

    // Videosquare job specific config options
    protected $removeLockOnStart        = true; // !!!
            
    // Process job task
    protected function process() {

    echo "processing...\n";
    
        try {
    
            // Temporary directory cleanup and log result
            $err = tempdir_cleanup($this->bootstrap->config['config_jobs']['doc_dir']);
            if ( !$err['code'] ) throw new \Videosquare\Model\Exception("[ERROR] Temporary directory cleanup error.\nERROR MSG: " . $err['message'] . "\nCOMMAND: " . $err['command'] . "\nRESUlT: " . $err['result']);

            $model = $this->bootstrap->getVSQModel("Attached_Documents");
            var_dump($this->debug_mode);
            $model->initLog($this->debug_mode);
            
            // Query next documents to process
            $attachedDocJobs = $model->getNextTasks();
            
            // Finish processing if no documents to be processed
            if ( $attachedDocJobs !== false ) {

                // Is LibreOffice running?
                if ( !$this->isLibreOfficeRunning() ) throw new \Videosquare\Model\Exception('[EXCEPTION] Libreoffice is not running.');
            
                while ( !$attachedDocJobs->EOF ) {

                    // Reinit document, clear cache, etc.
                    $model->killDocument();
                
                    // Get next attached document to process
                    $model->doc = $attachedDocJobs->fields;

                    // Select attachment for further processing
                    $model->selectAttachedDocument($model->doc['id']);
                    
                    // Debug
                    $this->debugLog("[INFO] NEW attached document to process:\n" . print_r($model->doc, true), false);

                    // Get indexing start time
                    $total_duration = time();

                    // Start global log for email reports
                    $global_log  = "";
                    $global_log .= "Start time: " . date("Y-m-d H:i:s.u") . "\n";
                    $global_log .= "Source front-end: " . $model->doc['sourceip'] . "\n";
                    $global_log .= "Status: " . $model->doc['status'] . "\n";
                    $global_log .= "Recording: " . $model->doc['rec_id'] . "\n";
                    $global_log .= "Recording URL: http://" . $this->bootstrap->config['baseuri'] . "hu/recordings/details/" . $model->doc['rec_id'] . "\n\n";
                    $global_log .= "Original filename: " . $model->doc['masterfilename'] . "\n";
                    $global_log .= "Filename: " . $model->doc['id'] . "." . $model->doc['masterextension'] . "\n";

                    // User information
                    $uploader_user['email'] = $model->doc['email'];
                    $uploader_user['nickname'] = $model->doc['nickname'];
                    $uploader_user['userid'] = $model->doc['userid'];
                    
                    $this->debugLog("[INFO] Uploader user:\n" . print_r($uploader_user, true), false);

                    // ## SSH: Copy attached document to converter
                    // Update media status to copying
                    $model->updateStatus($this->bootstrap->config['config_jobs']['dbstatus_copyfromfe'], 'indexingstatus');
                    
                    try {
                        $model->copyAttachedDocumentToConverter();
                    } catch ( \Videosquare\Model\Exception $err) {
                        $model->updateStatus($this->bootstrap->config['config_jobs']['dbstatus_copyfromfe_err'], 'indexingstatus');
                        throw $err;
                    }

                    // Check file size
                    $global_log .= "Filesize: " . sprintf("%.2f", $model->doc['filesize'] / 1024 ) . " Kbyte\n";

                    // Identify attached document file (text vs. data)
                    if ( !$model->identifyAttachedDocument() ) {
                        // Update document status to invalid input
                        $model->updateStatus($this->bootstrap->config['config_jobs']['dbstatus_invalidinput'], 'indexingstatus');
                        $attachedDocJobs->MoveNext();
                        continue;
                    }
                        
                    // Add title and filename to document cache
                    $model->addTextToLocalCache($model->doc['title']);
                    $model->addTextToLocalCache($model->doc['masterfilename']);

                    // Text document: insert text to database, no conversion
                    if ( ( array_search($model->doc['masterextension'], $this->bootstrap->config['config_jobs']['document_types_text']) !== false ) and ( $this->model['file_type'] == "text" ) ) $model->addTextFileToLocalCache($model->doc['source_file']);

                    // Presentation: convert to PDF before extracting text
                    $is_pres_pdf_source = false;
                    if ( array_search($model->doc['masterextension'], $this->bootstrap->config['config_jobs']['document_types_pres']) !== false ) {

                        $err = $model->convertUnoConv("pdf");
                        $pdf_temp = $err['output_file'];
                        
                        if ( $err['result'] != 0 ) {
//                            $this->debugLog("[ERROR] Unoconv conversion error.\nCOMMAND: " . $err['command'] . "\nOUTPUT: " . $err['command_output'] . "\nRESULT: " . $err['result'], false);
                            // Permanent unoconv error: report and stop processing
                            if ( ( stripos($err['command_output'], "Floating point exception") === false ) and ( stripos($err['command_output'], "Segmentation fault") === false ) ) {
                                $model->updateStatus($this->bootstrap->config['config_jobs']['dbstatus_indexing_err'], 'indexingstatus');
                                $attachedDocJobs->MoveNext();
                                continue;
                            } else {
                                // WORKAROUND: Sometimes "Floating point exception" or "Segmentation fault" is returned. Check resulting file size, do not set an error if not zero.
                                if ( !file_exists($err['output_file']) ) {
                                    $this->debugLog("[ERROR] Unoconv conversion error. Output not created. Will start over.\nCOMMAND: " . $err['command'] . "\nOUTPUT: " . $err['command_output'] . "\nRESULT: " . $err['result'], true);
                                    $model->updateStatus(null, 'indexingstatus');
                                    $attachedDocJobs->MoveNext();
                                    continue;
                                }
                                if ( filesize($err['output_file']) == 0 ) {
                                    $this->debugLog("[ERROR] Unoconv conversion error. Output is zero size. Will start over.\nCOMMAND: " . $err['command'] . "\nOUTPUT: " . $err['command_output'] . "\nRESULT: " . $err['result'], true);
                                    $model->updateStatus(null, 'indexingstatus');
                                    $attachedDocJobs->MoveNext();
                                    continue;
                                }
                                
                                $this->debugLog("[WARN] Unoconv might ran into an error. However, output seems valid. Going forward.", false);
                            }
                        }
                        
                        $is_pres_pdf_source = true;
                    }

                    // PDF: use pdftotext to extract plain text then check if valid text file
                    if ( ( ( array_search($model->doc['masterextension'], $this->bootstrap->config['config_jobs']['document_types_pdf']) !== false ) and ( $model->doc['file_type'] == "pdf" ) ) or ( $is_pres_pdf_source == true ) ) {

                        // Presentation: if converted to PDF first, change input file
                        $source_file = $model->doc['source_file'];
                        if ( $is_pres_pdf_source ) $source_file = $pdf_temp;
                        
                        // pdf2text conversion
                        $err = $model->convertPdf2Text($source_file);
                        if ( $err['result'] != 0 ) {
                            $model->updateStatus($this->bootstrap->config['config_jobs']['dbstatus_indexing_err'], 'indexingstatus');
                            $this->debugLog("[ERROR] pdftotext failed.\nCOMMAND: " . $err['command'] . "\nOUTPUT: " . $err['command_output'] . "\nRESULT: " . $err['result'], true);
                            $attachedDocJobs->MoveNext();
                            continue;
                        }
                        
                        // Add resulting text file to cache
                        $model->addTextFileToLocalCache($err['output_file']);
                        
                        // Check output if text: pdftotext can provide crap output (based on PDF character encoding) - GIVES WRONG OUTPUT SOMETIMES
            /*			$file_type = file_identify($content_file);
                        if ( ( $file_type === false ) OR ( stripos($file_type, "text") === false ) ) {
                            updateStatus($jconf['dbstatus_indexing_err'], 'indexingstatus');
                            $attachedDocJobs->MoveNext();
                            continue;
                        }
            */
                    }

                    // Other documents: use unoconv to extract text
                    if ( ( array_search($model->doc['masterextension'], $this->bootstrap->config['config_jobs']['document_types_doc']) !== false ) and ( $model->doc['file_type'] == "text" or $model->doc['file_type'] == "data" ) ) {
                        
                        $err = $model->convertUnoConv("txt");
                        
                        if ( $err['result'] != 0 ) {
//                            $this->debugLog("[ERROR] Unoconv conversion error.\nCOMMAND: " . $err['command'] . "\nOUTPUT: " . $err['command_output'] . "\nRESULT: " . $err['result'], true);
                            // Permanent unoconv error: report and stop processing
                            if ( ( stripos($err['command_output'], "Floating point exception") === false ) and ( stripos($err['command_output'], "Segmentation fault") === false ) ) {
                                $model->updateStatus($this->bootstrap->config['config_jobs']['dbstatus_indexing_err'], 'indexingstatus');
                                $attachedDocJobs->MoveNext();
                                continue;
                            } else {
                                // WORKAROUND: Sometimes "Floating point exception" or "Segmentation fault" is returned. Check resulting file size, do not set an error if not zero.
                                if ( !file_exists($err['output_file']) ) {
                                    $this->debugLog("[ERROR] Unoconv conversion error. Output not created. Will start over.", true);
                                    $model->updateStatus(null, 'indexingstatus');
                                    $attachedDocJobs->MoveNext();
                                    continue;
                                }
                                if ( filesize($err['output_file']) == 0 ) {
                                    $this->debugLog("[ERROR] Unoconv conversion error. Output is zero size. Will start over.", true);
                                    $model->updateStatus(null, 'indexingstatus');
                                    $attachedDocJobs->MoveNext();
                                    continue;
                                }
                                
                                $this->debugLog("[WARN] Unoconv might ran into an error. However, output seems valid. Going forward.", false);
                            }
                            
                        }

                        // Add resulting text file to cache            
                        $model->addTextFileToLocalCache($err['output_file']);
                    }
                    
                    // Update document cache in DB
                    if ( !$model->updateLocalCacheToDB() ) {
                        $model->updateStatus($this->bootstrap->config['config_jobs']['dbstatus_indexing_err'], 'indexingstatus');
                        $attachedDocJobs->MoveNext();
                        continue;
                    }

                    // Update recording search cache
                    $recObj = $this->bootstrap->getModel('recordings');
                    $recObj->select($model->doc['rec_id']);
                    $recObj->updateFulltextCache();
                    $model->updateStatus($this->bootstrap->config['config_jobs']['dbstatus_indexing_ok'], 'indexingstatus');
                
                    // Logging
                    $indexing_duration = time() - $total_duration;
                    $hms = secs2hms($indexing_duration);
                    $global_log .= "Indexed text: " . sprintf("%.2f", $model->cache_size / 1024 ) . " Kbyte\n";
                    
                    $this->debugLog("[OK] Successful document indexation in " . $hms . " time.\n\n" . $global_log, false);        

                    // Process next
                    $attachedDocJobs->MoveNext();
                }
            
            }

        } catch ( \Videosquare\Model\Exception $err ) {
            
            if ( $err->getCode() == 100 ) {
                $this->debugLog("[EXCEPTION] Fatal Error (100) is detected. Stop processing.", true);
                throw $err;
            } else {
                $this->debugLog("[EXCEPTION] Processing error: " . $err->getMessage() . "\n" . $err->getTraceAsString(), true);
            }
            
        }
        
        if ( $this->debug_mode ) $this->debugLog("[DEBUG] Going to sleep now: " . $this->currentSleepSeconds);
        
        $this->updateLock();
        $this->handlePanic();
        
        return true;
    }

}

$job = new AttachedDocumentsIndex(BASE_PATH, PRODUCTION);

try {
    $job->run();
} catch( \Videosquare\Model\Exception $err ) {
    throw $err;
}

?>
