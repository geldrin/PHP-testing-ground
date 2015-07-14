<?php
// File remove job handling:
//  - Remove recordings.status = "markedfordeleltion" recordings from storage

define('BASE_PATH', realpath( __DIR__ . '/../..' ) . '/' );
define('PRODUCTION', false );
define('DEBUG', false );

// Utils
include_once('job_utils_base.php');
include_once('job_utils_log.php');
include_once('job_utils_status.php');
include_once('job_utils_media2.php');

include_once( BASE_PATH . 'libraries/Springboard/Application/Cli.php');

set_time_limit(0);
clearstatcache();

// Init
$app = new Springboard\Application\Cli(BASE_PATH, PRODUCTION);

// Load jobs configuration file
$app->loadConfig('modules/Jobs/config_jobs.php');
$jconf   = $app->config['config_jobs'];
$myjobid = $jconf['jobid_remove_files'];
$logdir  = $jconf['log_dir'];
$logfile = $myjobid . ".log";

// Log related init
$debug = Springboard\Debug::getInstance();

// Should we remove files and do any changes to DB?
$isexecute = true;

// Check operating system - exit if Windows
if ( iswindows() ) {
    echo "ERROR: Non-Windows process started on Windows platform\n";
    exit;
}

// Exit if any STOP file exists
if ( is_file( $app->config['datapath'] . 'jobs/job_remove_files.stop' ) or is_file( $app->config['datapath'] . 'jobs/all.stop' ) ) exit;

// Runover check. Is this process already running? If yes, report and exit
if ( !runOverControl($myjobid) ) exit;

// Watchdog
$app->watchdog();

// Establish database connection
$db = db_maintain();

// Should we delete files or just testing?
if ( !$isexecute ) {
    $debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] THIS IS A TEST RUN. NO FILES WILL BE REMOVED + DB WILL BE NOT MODIFIED!", $sendmail = false);
}

// Recording to remove: delete all recording including master, surrogates, documents and index pictures
$recordings = queryRecordingsToRemove("recording");
if ( $recordings !== false ) {

    $size_toremove = 0;

    while ( !$recordings->EOF ) {

        $recording = $recordings->fields;

        // Check recording retain time period
//        $now = time();
        $rec_deleted = 0;
        if ( !empty($recording['deletedtimestamp']) ) {
            $rec_deleted = strtotime($recording['deletedtimestamp']);
        }
/*        $rec_retain = $recording['daystoretainrecordings'] * 24 * 3600;
        if ( ( $now - $rec_deleted ) < $rec_retain ) {
            // Falls within retain period, no action taken
//			$debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] Recording rec#" . $recording['id'] . " with status '" . $recording['status'] . "' appointed for removal. Retained until: " . date("Y-m-d H:i:s", $rec_deleted + $rec_retain), $sendmail = false);
            $recordings->MoveNext();
            continue;
        }
*/

        // Directory to remove
        $remove_path = $app->config['recordingpath'] . ( $recording['id'] % 1000 ) . "/" . $recording['id'] . "/";

        // Log file path information
        $log_msg  = " Title: " . $recording['title'] . "\n";
        $log_msg .= " Uploader: " . $recording['email'] . " (domain: " . $recording['domain'] . ")\n";
        $log_msg .= " Deleted: " . date("Y-m-d H:i:s", $rec_deleted) . "\n";
        $log_msg .= " Path: " . $remove_path . "\n";

        // Check directory size
        $err = directory_size($remove_path);
        $dir_size = 0;
        if ( $err['code'] === true ) {
            $size_toremove += $err['size'];
            $dir_size = round($err['size'] / 1024 / 1024, 2);
            $log_msg .= " Recording size: " . $dir_size . "MB\n";
        }

        // Log recording info before removal
        $debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] Removing rec#" . $recording['id'] . " with status '" . $recording['status'] . "'." . $log_msg, $sendmail = false);

        // Remove recording directory
        if ( $isexecute ) {

            // ## Remove recording directory from storage
            safeCheckPath($remove_path);
            $err = remove_file_ifexists($remove_path);
            if ( !$err['code'] ) {
                // Error: we skip this one, admin must check it manually
                $debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] Cannot remove recording directory.\n" . $err['message'] . "\n\nCommand:\n" . $err['command'] . "\n\nOutput:\n" . $err['command_output'], $sendmail = true);
                // Next recording
                $recordings->MoveNext();
                continue;
            }

        }

        // ## Remove master from upload area
        if ( $recording['masterstatus'] == $jconf['dbstatus_uploaded'] ) {
            $suffix = "video";
            if ( $recording['mastermediatype'] == "audio" ) $suffix = "audio";
            $remove_filename = $app->config['uploadpath'] . "recordings/" . $recording['id'] . "_" . $suffix . "." . $recording['mastervideoextension'];
            $debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] Removing recording master from upload area: " . $remove_filename, $sendmail = false);
            if ( $isexecute ) {
                safeCheckPath($remove_filename);
                $err = remove_file_ifexists($remove_filename);
                if ( !$err['code'] ) {
                    // Error: we skip this one, admin must check it manually
                    $debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] Cannot remove file from upload area.\n" . $err['message'] . "\n\nCommand:\n" . $err['command'] . "\n\nOutput:\n" . $err['command_output'], $sendmail = true);
                } else {
                    $debug->log($jconf['log_dir'], $myjobid . ".log", "[OK] Recording removed from upload area rec#" . $recording['id'] . ", filename = " . $remove_filename, $sendmail = false);
                }
            }
        }

        // ## Update status fields (recording)
        if ( $isexecute ) {
            // status, masterstatus, mobilestatus and all active recording versions + empty smilstatus
            updateRecordingStatus($recording['id'], $jconf['dbstatus_deleted'], "recording");
            updateMasterRecordingStatus($recording['id'], $jconf['dbstatus_deleted'], "recording");
            updateRecordingStatus($recording['id'], $jconf['dbstatus_deleted'], "mobile");
            $filter = implode('|', array(
              $jconf['dbstatus_init'],
              $jconf['dbstatus_uploaded'],
              $jconf['dbstatus_conv'],
              $jconf['dbstatus_convert'],
              $jconf['dbstatus_conv_err'],
              $jconf['dbstatus_reconvert'],
              $jconf['dbstatus_copystorage_ok'],
              $jconf['dbstatus_copyfromfe_ok'],
              $jconf['dbstatus_copyfromfe_err'],
              $jconf['dbstatus_copystorage'],
              $jconf['dbstatus_copyfromfe'],
              $jconf['dbstatus_copystorage_err'],
              $jconf['dbstatus_stop'],
              $jconf['dbstatus_markedfordeletion'],
            ));
            updateRecordingVersionStatusApplyFilter($recording['id'], $jconf['dbstatus_deleted'], "all", $filter);
            updateRecordingStatus($recording['id'], null, "smil");
        }

        // ## Remove content from upload area
        if ( $recording['contentmasterstatus'] == $jconf['dbstatus_uploaded'] ) {
            $suffix = "content";
            $remove_filename = $app->config['uploadpath'] . "recordings/" . $recording['id'] . "_" . $suffix . "." . $recording['contentmastervideoextension'];
            $debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] Removing content master from upload area: " . $remove_filename, $sendmail = false);
            if ( $isexecute ) {
                safeCheckPath($remove_filename);
                $err = remove_file_ifexists($remove_filename);
                if ( !$err['code'] ) {
                    // Error: we skip this one, admin must check it manually
                    $debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] Cannot remove file from upload area.\n" . $err['message'] . "\n\nCommand:\n" . $err['command'] . "\n\nOutput:\n" . $err['command_output'], $sendmail = true);
                } else {
                    $debug->log($jconf['log_dir'], $myjobid . ".log", "[OK] Recording content removed from upload area rec#" . $recording['id'] . ", filename = " . $remove_filename, $sendmail = false);
                }
            }
        }

        // ## Update status fields (content)
        if ( !empty($recording['contentmasterstatus']) ) {
            // contentstatus, contentmasterstatus + emtpy contentsmilstatus
            if ( $isexecute ) {
                updateRecordingStatus($recording['id'], $jconf['dbstatus_deleted'], "content");
                updateMasterRecordingStatus($recording['id'], $jconf['dbstatus_deleted'], "content");
                updateRecordingStatus($recording['id'], null, "contentsmil");
            }
        }

        // Update attached documents: of removed recording: status, delete document cache
        $query = "
            UPDATE
                attached_documents
            SET
                status = '" . $jconf['dbstatus_deleted'] . "',
                indexingstatus = NULL,
                documentcache = NULL
            WHERE
                recordingid = " . $recording['id'];

        if ( $isexecute ) {
            try {
                $rs = $db->Execute($query);
            } catch (exception $err) {
                $debug->log($jconf['log_dir'], $jconf['jobid_file_remove'] . ".log", "[ERROR] SQL query failed.\n" . trim($query), $sendmail = true);
                $recordings->MoveNext();
                continue;
            }
        }

        // Log attachment cleanup
        $debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] Recording rec#" . $recording['id'] . " attachment(s) cleaned up.", $sendmail = false);

        // New recording and master size
        $values = array(
            'recordingdatasize' => 0,
            'masterdatasize'    => 0
        );
        if ( $isexecute ) {
            $recDoc = $app->bootstrap->getModel('recordings');
            $recDoc->select($recording['id']);
            $recDoc->updateRow($values);
        }
        $debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] Recording and master data size updated to 0.", $sendmail = false);

        // Log physical removal
        $debug->log($jconf['log_dir'], $myjobid . ".log", "[OK] Recording directory removed rec#" . $recording['id'] . ", dirname = " . $remove_path . ", size = " . $dir_size . "MB.", $sendmail = false);

        // Watchdog
        $app->watchdog();

        // Next recording
        $recordings->MoveNext();
    } // End of file remove

} // End of removing recordings

// Watchdog
$app->watchdog();

// Content to remove: delete content including master, surrogates and others
$recordings = queryRecordingsToRemove("content");
if ( $recordings !== false ) {

    $size_toremove = 0;

    while ( !$recordings->EOF ) {

        $recording = $recordings->fields;

        // Main path
        $remove_path = $app->config['recordingpath'] . ( $recording['id'] % 1000 ) . "/" . $recording['id'] . "/";

        // Log information
        $log_msg  = " Title: " . $recording['title'] . "\n";
        $log_msg .= " Uploader: " . $recording['email'] . " (domain: " . $recording['domain'] . ")\n";
        $log_msg .= " Deleted: " . $recording['contentdeletedtimestamp'] . "\n";
        $log_msg .= " Path: " . $remove_path . "\n";

        // ## Remove content master from storage
        if ( $recording['contentmasterstatus'] == $jconf['dbstatus_copystorage_ok'] ) {

            // Master path
            $remove_filename = $remove_path . "master/" . $recording['id'] . "_content." . $recording['contentmastervideoextension'];

            // Log content to remove
            $debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] Removing content rec#" . $recording['id'] . " with status '" . $recording['contentstatus'] . "'. Filename = " . $remove_filename, $sendmail = false);

            if ( $isexecute ) {

                // Size of the file to be removed
                $size_toremove = filesize($remove_filename);

                safeCheckPath($remove_filename);
                $err = remove_file_ifexists($remove_filename);
                if ( !$err['code'] ) {
                    // Error: we skip this one, admin must check it manually
                    $debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] Cannot remove recording.\n" . $err['message'] . "\nCommand:\n" . $err['command'] . "\nOutput:\n" . $err['command_output'], $sendmail = true);
                    // Next recording
                    $recordings->MoveNext();
                    continue;
                }
            }
        }

        // ## Remove content from upload area
        if ( $recording['contentmasterstatus'] == $jconf['dbstatus_uploaded'] ) {

            $suffix = "content";

            // Master path
            $remove_filename = $app->config['uploadpath'] . "recordings/" . $recording['id'] . "_" . $suffix . "." . $recording['contentmastervideoextension'];

            // Log recording to remove
            $debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] Removing content rec#" . $recording['id'] . " with status '" . $recording['contentstatus'] . "'. Filename = " . $remove_filename, $sendmail = false);

            if ( $isexecute ) {

                // Size of the file to be removed
                $size_toremove = filesize($remove_filename);

                safeCheckPath($remove_filename);
                $err = remove_file_ifexists($remove_filename);
                if ( !$err['code'] ) {
                    // Error: we skip this one, admin must check it manually
                    $debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] Cannot remove file from upload area.\n" . $err['message'] . "\n\nCommand:\n" . $err['command'] . "\n\nOutput:\n" . $err['command_output'], $sendmail = true);
                    // Next recording
                    $recordings->MoveNext();
                    continue;
                } else {
                    $debug->log($jconf['log_dir'], $myjobid . ".log", "[OK] Recording content removed from upload area rec#" . $recording['id'] . ", filename = " . $remove_filename, $sendmail = false);
                }
            }
        }

        // ## Update status fields
        if ( $isexecute ) {
            // contentmasterstatus, contentstatus
            updateMasterRecordingStatus($recording['id'], $jconf['dbstatus_deleted'], "content");
            updateRecordingStatus($recording['id'], $jconf['dbstatus_deleted'], "content");
            // recordings_versions.status := "markedfordeletion" for all content surrogates (will be deleted in the next step, see below)
            $filter = implode('|', array(
              $jconf['dbstatus_init'],
              $jconf['dbstatus_uploaded'],
              $jconf['dbstatus_conv'],
              $jconf['dbstatus_convert'],
              $jconf['dbstatus_conv_err'],
              $jconf['dbstatus_reconvert'],
              $jconf['dbstatus_copystorage_ok'],
              $jconf['dbstatus_copyfromfe_ok'],
              $jconf['dbstatus_copyfromfe_err'],
              $jconf['dbstatus_copystorage'],
              $jconf['dbstatus_copyfromfe'],
              $jconf['dbstatus_copystorage_err'],
              $jconf['dbstatus_stop'],
            ));
            updateRecordingVersionStatusApplyFilter($recording['id'], $jconf['dbstatus_markedfordeletion'], "content|pip", $filter);
            // contentsmilstatus
            updateRecordingStatus($recording['id'], null, "contentsmil");
        }

        // Log physical removal
        $debug->log($jconf['log_dir'], $myjobid . ".log", "[OK] Content master was removed: id = " . $recording['id'] . ", filename = " . $remove_filename . ", size = " . round($size_toremove / 1024 / 1024, 2) . "MB.", $sendmail = false);

        // ## Update recording and master size
        if ( $isexecute ) {
            $err = directory_size($remove_path);
            if ( $err['code'] === true ) {
                $recording_dir_size = $err['size'];
            } else {
                $recording_dir_size = 0;
                $debug->log($jconf['log_dir'], $myjobid . ".log", "[WARN] Cannot get recording directory size. Truncated to 0.", $sendmail = false);
            }
            // Master directory is not yet on storage
            if ( $recording['masterstatus'] == $jconf['dbstatus_uploaded'] ) {
                $suffix = "video";
                $master_dir_size = 0;
                if ( $recording['mastermediatype'] == "audio" ) $suffix = "audio";
                $master_filename = $app->config['uploadpath'] . "recordings/" . $recording['id'] . "_" . $suffix . "." . $recording['mastervideoextension'];
                if ( file_exists($master_filename) ) {
                    $master_dir_size = filesize($master_filename);
                } else {
                    $master_dir_size = 0;
                    $debug->log($jconf['log_dir'], $myjobid . ".log", "[WARN] Master is not present in upload area: " . $master_filename, $sendmail = true);
                }
            } else {
                $err = directory_size($remove_path . "master/");
                if ( $err['code'] === true ) {
                    $master_dir_size = $err['size'];
                } else {
                    $master_dir_size = 0;
                    $debug->log($jconf['log_dir'], $myjobid . ".log", "[WARN] Cannot get master directory size. Truncated to 0.", $sendmail = false);
                }
            }
            $values = array(
                'recordingdatasize' => $recording_dir_size,
                'masterdatasize'    => $master_dir_size
            );
            $recDoc = $app->bootstrap->getModel('recordings');
            $recDoc->select($recording['id']);
            $recDoc->updateRow($values);

            $debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] Recording and master data size updated: " . round($recording_dir_size / 1024 / 1024, 2) . "MB / " . round($master_dir_size / 1024 / 1024, 2) . "MB", $sendmail = false);
        }

        // Next recording
        $recordings->MoveNext();
    }
}

// Watchdog
$app->watchdog();

// Surrogates: delete recordings_versions elements one by one
$recversions = queryRecordingsVersionsToRemove();
if ( $recversions !== false ) {

    while ( !$recversions->EOF ) {

        $recversion = $recversions->fields;

        // Recording version path
        $remove_path = $app->config['recordingpath'] . ( $recversion['recordingid'] % 1000 ) . "/" . $recversion['recordingid'] . "/";
        $recversion_filename = $remove_path . $recversion['filename'];

        // Log recording version to remove
        $debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] Removing recver#" . $recversion['id'] . ", rec#" . $recversion['recordingid'] . ", filename = " . $recversion_filename, $sendmail = false);

        // Filename: if empty then assume no recording version was ever made
        if ( empty($recversion['filename']) ) {
            // ## Update status fields
            $debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] Recording version has no filename. Moving to 'deleted' status...", $sendmail = false);
            if ( $isexecute ) {
                // recordings_versions.status := "deleted"
                updateRecordingVersionStatus($recversion['id'], $jconf['dbstatus_deleted']);
                // recording.(content)smilstatus := "regenerate"
// Not needed when 'markedfordeletion' recversion is removed?
//              updateRecordingStatus($recversion['recordingid'], $jconf['dbstatus_regenerate'], $idx . "smil");
            }
            $recversions->MoveNext();
            continue;
        }

        $idx = "";
        if ( $recversion['iscontent'] ) $idx = "content";

        // Remove content surrogate
        if ( $isexecute ) {

            // Size of the file to be removed
            $size_toremove = filesize($recversion_filename);

            safeCheckPath($recversion_filename);
            $err = remove_file_ifexists($recversion_filename);
            if ( !$err['code'] ) {
                // Error: we skip this one, admin must check it manually
                $debug->log($jconf['log_dir'], $myjobid . ".log", "[WARN] Cannot remove recording version: recordingid = " . $recversion['recordingid'] . ", recordingversionid = " . $recversion['id'] . ", filename = " . $recversion_filename, $sendmail = false);
                // We does not send any alarms nor exit, as recording versions might be removed earlier.
            }

            // ## Update status fields
            // recordings_versions.status := "deleted"
            updateRecordingVersionStatus($recversion['id'], $jconf['dbstatus_deleted']);
            // recording.(content)smilstatus := "regenerate" - Not needed for 'markedfordeleletion' recording versions!
//          updateRecordingStatus($recversion['recordingid'], $jconf['dbstatus_regenerate'], $idx . "smil");

            // Log physical removal
            $debug->log($jconf['log_dir'], $myjobid . ".log", "[OK] Recording version removed recver#" . $recversion['id'] . ", rec#" . $recversion['recordingid'] . ", filename = " . $recversion_filename . ", size = " . round($size_toremove / 1024 / 1024, 2) . "MB.", $sendmail = false);

            // ## Update recording and master size
            $err = directory_size($remove_path);
            if ( $err['code'] === true ) {
                $recording_dir_size = $err['size'];
            } else {
                $recording_dir_size = 0;
                $debug->log($jconf['log_dir'], $myjobid . ".log", "[WARN] Cannot get recording directory size. Truncated to 0.", $sendmail = false);
            }
            $values = array(
                'recordingdatasize' => $recording_dir_size
            );
            $recDoc = $app->bootstrap->getModel('recordings');
            $recDoc->select($recversion['recordingid']);
            $recDoc->updateRow($values);
            $debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] Recording data size updated: " . round($recording_dir_size / 1024 / 1024, 2) . "MB", $sendmail = false);

        } // End of recording version removal

        $recversions->MoveNext();
    }
}

// Watchdog
$app->watchdog();

// Attachments: remove uploaded attachments
$attachments = queryAttachmentsToRemove();
if ( $attachments !== false ) {

    while ( !$attachments->EOF ) {

        $attached_doc = array();
        $attached_doc = $attachments->fields;

        // Path and filename
        $remove_path = $app->config['recordingpath'] . ( $attached_doc['rec_id'] % 1000 ) . "/" . $attached_doc['rec_id'] . "/";
        $base_dir = $remove_path . "attachments/";
        $base_filename = $attached_doc['id'] . "." . $attached_doc['masterextension'];
        $filename = $base_dir . $base_filename;

        // Log file to remove
        $debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] Removing attachment attacheddoc#" . $attached_doc['id'] . " (rec#" . $attached_doc['rec_id'] . "), filename = " . $filename, $sendmail = false);

        // Remove attachment
        if ( $isexecute ) {

            // Size of the file to be removed
            $size_toremove = filesize($filename);

            safeCheckPath($filename);
            $err = remove_file_ifexists($filename);
            if ( !$err['code'] ) {
                // Error: we skip this one, admin must check it manually
                $debug->log($jconf['log_dir'], $myjobid . ".log", $err['message'] . "\n\nCommand:\n" . $err['command'] . "\n\nOutput:\n" . $err['command_output'], $sendmail = true);
                $attachments->MoveNext();
                continue;
            }

            $debug->log($jconf['log_dir'], $myjobid . ".log", "[OK] Attached document removed. Info: attacheddoc#" . $attached_doc['id'] . " (rec#" . $attached_doc['rec_id'] . "), size = " . round($size_toremove / 1024 / 1024, 2) . "MB, filename = " . $filename . ".", $sendmail = false);

            // Update attached document status to DELETED
            updateAttachedDocumentStatus($attached_doc['id'], $jconf['dbstatus_deleted'], null);
            // Update attached document indexingstatus to NULL
            updateAttachedDocumentStatus($attached_doc['id'], null, "indexingstatus");
            // Update attached document cache to NULL
            updateAttachedDocumentCache($attached_doc['id'], null);

            // ## Update recording and master size
            $err = directory_size($remove_path);
            if ( $err['code'] === true ) {
                $recording_dir_size = $err['size'];
            } else {
                $recording_dir_size = 0;
                $debug->log($jconf['log_dir'], $myjobid . ".log", "[WARN] Cannot get recording directory size. Truncated to 0.", $sendmail = false);
            }
            $values = array(
                'recordingdatasize' => $recording_dir_size
            );
            $recDoc = $app->bootstrap->getModel('recordings');
            $recDoc->select($attached_doc['rec_id']);
            $recDoc->updateRow($values);
            $debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] Recording data size updated: " . round($recording_dir_size / 1024 / 1024, 2) . "MB", $sendmail = false);

        } // End of file removal

        // Watchdog
        $app->watchdog();

        // Next attachment
        $attachments->MoveNext();
    }
}

// OCR data: remove OCR frames marked for dumping
$OCRframes = queryOCRdataToRemove();
if (is_array($OCRframes)) {
	
	foreach($OCRframes as $rec) {
		
		$data_removed = .0;
		$recid = $rec['recordingid'];
		$basepath = $app->config['recordingpath'] . ( $recid % 1000 ) ."/". $recid ."/ocr/";
		
		$msg = "[INFO] Removing OCR data from Recording#". $recid .".";
		$debug->log($logdir, $logfile, $msg, false);
		
		if ($rec['ocrstatus'] == $jconf['dbstatus_markedfordeletion'] || empty($rec['ocrframes2del'])) {
			// All OCR data can be removed.
			$msg = "Recordings.ocrstatus = '". $jconf['dbstatus_markedfordeletion'] ."' > applying full cleanup.";
			$debug->log($logdir, $logfile, $msg, false);
			unset($msg);
			
			if ($isexecute === FALSE) continue;
			
			$tmp = directory_size($basepath);
			$size_toremove = $tmp['size'];
			safeCheckPath($basepath);
			$err = remove_file_ifexists($basepath);
			
			if (!$err['code']) {
				$mesg = $err['message'] . "\n\nCommand:\n" . $err['command'] . "\n\nOutput:\n" . $err['command_output'];
				$debug->log($logdir, $logfile, $mesg, $sendmail = false);
				continue;
			}
			$data_removed += $size_toremove;
			$debug->log($logdir, $logfile, "[OK] OCR directory has been removed: '". $basepath ."'.", false);
			
			updateRecordingStatus($recid, $jconf['dbstatus_deleted'], 'ocr');
			$upd = updateOCRstatus($recid, null, $jconf['dbstatus_deleted']);
			if ($upd['result'] === false) {
				$debug->log($logdir, $logfile, $upd['message'], false);
			}
			unset($err, $tmp, $upd);
		} else {
			// We have to carefully remove OCRframes one by one.
			if (!array_key_exists('ocrframes2del', $rec)) continue;
			
			foreach($rec['ocrframes2del'] as $frames) {
				$files2remove = array();
				$filename = $recid ."_". $frames['id'] .".jpg";
				
				$files2remove[] = $basepath ."original". DIRECTORY_SEPARATOR . $filename;
				
				foreach ($app->config['videothumbnailresolutions'] as $tres) {
					$tmp = explode("x", $tres);
					$files2remove[] = $basepath . $tmp[0] . DIRECTORY_SEPARATOR . $filename;
				}
				
				if ($isexecute === FALSE) continue;
				$size_toremove = .0;  // Size of the file to be removed

				foreach($files2remove as $f) {
					if (!file_exists($f)) continue;
					safeCheckPath($f);
					$size_toremove += filesize($f);
					$err = remove_file_ifexists($f);
					
					if ( !$err['code'] ) {
						$msg = $err['message'] . "\n\nCommand:\n" . $err['command'] . "\n\nOutput:\n" . $err['command_output'];
						$debug->log($logdir, $logfile, $msg, $sendmail = false);
						continue;
					}
				}
				
				$data_removed += $size_toremove;

				if ($size_toremove > 0 === false) {
					$msg = "[WARN] OCRframe#". $frames['id'] ." was already removed!\n";
				} else {
					$msg = "[OK] OCRframe#" . $frames['id'] . " removed (rec#" . $recid . "), size = " . round($size_toremove / 1024 / 1024, 2) . "MB.";
				}
				$debug->log($logdir, $logfile, $msg, $sendmail = false);
				
				$upd = updateOCRstatus($recid, $frames['id'], $jconf['dbstatus_deleted']);
				if ($upd['result'] === false) {
					$msg = "[ERROR] updating ocr_frames#". $frames['id'] ." failed. Error message: ". $upd['message'];
					$debug->log($logdir, $logfile, $msg, $sendmail = false);
				}

				unset($files2remove, $size_toremove, $upd);
			}
		}
		
		if ($data_removed > 0) {
			$msg = "[INFO] Total amount of data removed: ". round($data_removed / 1024 / 1024, 2) ."MB";
			$debug->log($logdir, $logfile, $msg, $sendmail = false);
			unset($msg);
		}
		
		unset($data_removed, $recid, $basepath);
	}
	$app->Watchdog();
}

// Close DB connection if open
if ( is_resource($db->_connectionID) ) $db->close();

// Watchdog
$app->watchdog();
    
exit;

// *************************************************************************
// *                function queryRecordingsToRemove()                     *
// *************************************************************************
// Description: queries next uploaded document from attached_documents
function queryRecordingsToRemove($type = null) {
global $jconf, $db, $app, $debug;

    if ( empty($type) or ( $type != "recording" ) and ( $type != "content" ) ) return false;

    $idx = "";
    if ( $type == "content" ) $idx = "content";

    $node = $app->config['node_sourceip'];

    $query = "
        SELECT
            r.id,
            r.title,
            r.userid,
            r.status,
            r.masterstatus,
            r.contentstatus,
            r.contentmasterstatus,
            r.mastersourceip,
            r.contentmastersourceip,
            r.deletedtimestamp,
            r.contentdeletedtimestamp,
            r.mastervideofilename,
            r.mastervideoextension,
            r.contentmastervideofilename,
            r.contentmastervideoextension,
            r.mastermediatype,
            r.contentmastermediatype,
            r.recordingdatasize,
            r.masterdatasize,
            u.email,
            o.id AS organizationid,
            o.domain,
            o.daystoretainrecordings,
            o.defaultencodingprofilegroupid
        FROM
            recordings AS r,
            users AS u,
            organizations AS o
        WHERE
            r." . $idx . "status = '" . $jconf['dbstatus_markedfordeletion'] . "' AND
            r." . $idx . "mastersourceip = '" . $node . "' AND
            r.userid = u.id AND
            r.organizationid = o.id";

//echo $query . "\n";

    try {
        $recordings = $db->Execute($query);
    } catch (exception $err) {
        $debug->log($jconf['log_dir'], $jconf['jobid_remove_files'] . ".log", "[ERROR] SQL query failed.\n\n" . trim($query), $sendmail = true);
        return false;
    }

    // Check if pending job exsits
    if ( $recordings->RecordCount() < 1 ) {
        return false;
    }

    return $recordings;
}

function queryRecordingsVersionsToRemove() {
global $jconf, $db, $app, $debug;

    $node = $app->config['node_sourceip'];
// Multinode: csak azokat torolhessuk, amiket az erre a node-ra feltett file-bol generaltunk???

    $query = "
        SELECT
            rv.id,
            rv.recordingid,
            rv.qualitytag,
            rv.iscontent,
            rv.status,
            rv.resolution,
            rv.filename,
            rv.bandwidth,
            rv.isdesktopcompatible,
            rv.ismobilecompatible,
            rv.encodingprofileid,
            ep.type,
            ep.mediatype,
            r.masterdatasize,
            r.recordingdatasize
        FROM
            recordings_versions AS rv,
            encoding_profiles AS ep,
            recordings AS r
        WHERE
            rv.recordingid = r.id AND
            rv.status = '" . $jconf['dbstatus_markedfordeletion'] . "' AND
            rv.encodingprofileid = ep.id";

    try {
        $recversions = $db->Execute($query);
    } catch (exception $err) {
        $debug->log($jconf['log_dir'], $jconf['jobid_remove_files'] . ".log", "[ERROR] SQL query failed.\n\n" . trim($query), $sendmail = true);
        return false;
    }

    // Check if pending job exsits
    if ( $recversions->RecordCount() < 1 ) {
        return false;
    }

    return $recversions;
}

function queryAttachmentsToRemove() {
global $jconf, $db, $app, $debug;

    $node = $app->config['node_sourceip'];

    $query = "
        SELECT
            a.id,
            a.masterfilename,
            a.masterextension,
            a.status,
            a.sourceip,
            a.recordingid as rec_id,
            a.userid,
            b.nickname,
            b.email,
            r.recordingdatasize
        FROM
            attached_documents AS a,
            users AS b,
            recordings AS r
        WHERE
            a.sourceip = '" . $node . "' AND
            a.status = '" . $jconf['dbstatus_markedfordeletion'] . "' AND
            ( a.indexingstatus IS NULL OR a.indexingstatus = '' OR a.indexingstatus = '" . $jconf['dbstatus_indexing_ok'] . "' ) AND
            a.userid = b.id AND
            a.recordingid = r.id
    ";

    try {
        $attachments = $db->Execute($query);
    } catch (exception $err) {
        $debug->log($jconf['log_dir'], $jconf['jobid_remove_files'] . ".log", "[ERROR] SQL query failed.\n\n" . trim($query), $sendmail = true);
        return false;
    }

    // Check if pending job exists
    if ( $attachments->RecordCount() < 1 ) {
        return false;
    }

    return $attachments;
}

///////////////////////////////////////////////////////////////////////////////////////////////////
function queryOCRdataToRemove() {
///////////////////////////////////////////////////////////////////////////////////////////////////
//
// Kigyujti a torlesre jelolt ocr_frames rekordokat, illetve a hozzajuk tartozo recording statusz 
// mezoit.
// Amennyiben nincs torlesre varo OCR adat, a visszateresi ertek TRUE, ellenkezo esetben tomb-ot
// ad vissza. Hiba soran a fuggveny FALSE ertekkel ter vissza.
//
///////////////////////////////////////////////////////////////////////////////////////////////////
	global $app, $db, $debug, $jconf;
	$logdir = $jconf['log_dir'];
	$logfile = $jconf['jobid_remove_files'] .".log";
	
	$result = false;
	
	try {
		
		if (!is_resource($db->_connectionID)) $db = db_maintain();
		
		$qry_rec = "
		SELECT
			`r`.`id` AS 'recordingid',
			`r`.`status` AS 'recordingstatus',
			`r`.`contentstatus`,
			`r`.`contentmasterstatus`,
			`r`.`ocrstatus`
		FROM
			`recordings` AS `r`
		RIGHT JOIN
			`ocr_frames` AS `o`
		ON
			`r`.`id` = `o`.`recordingid`
		WHERE
			`r`.`ocrstatus` LIKE 'markedfordeletion' OR
			`o`.`status` LIKE '". $jconf['dbstatus_markedfordeletion'] ."'
		GROUP BY `r`.`id`;";
		
		$rs_rec = $db->Execute($qry_rec);
		
		if ($rs_rec->RecordCount() > 0) {
			$junk = $rs_rec->GetArray();
			unset($rs_rec);
			
			foreach ($junk AS $cur => $rec) {
				$qry_frames = "
				SELECT
					`o`.`id`
				FROM
					`ocr_frames` AS `o`
				WHERE
					`o`.`recordingid` = ". $rec['recordingid'] ." AND
					`o`.`status` = '". $jconf['dbstatus_markedfordeletion'] ."';";
				$rs_frames = $db->Execute($qry_frames);
				$junk[$cur]['ocrframes2del'] = $rs_frames->GetArray();
				
				$qry_count = "
				SELECT
				COUNT(*) AS 'ocrcount'
				FROM
					`ocr_frames` AS `o`
				WHERE
					`o`.`status` NOT REGEXP 'failed|delete' AND
					`o`.`recordingid` = ". $rec['recordingid'] ."
				GROUP BY `o`.`recordingid`;";
				$rs_count = $db->Execute($qry_count);
				$junk[$cur]['ocrcount'] = $rs_count->Fields('ocrcount');
				unset($qry_frames, $rs_frames, $qry_count, $rs_count);
			}
			
			$result = $junk;
			unset($junk);
		} else {
			$result = true;
		}
		unset($qry_rec, $rs_rec);
		
	} catch (Exception $e) {
		$mesg = "[ERROR] ". __FUNCTION__ ." failed. Errormessage:\n" . $e->getMessage();
		$debug->log($logdir, $logfile, $mesg, $sendmail = false);
	}
	return $result;
}

// SAFE CHECK
function safeCheckPath($remove_path) {
 global $myjobid, $app, $debug, $jconf;

    if ( ( stripos($remove_path, $app->config['recordingpath']) === false ) and ( stripos($remove_path, $app->config['uploadpath'] . "recordings/") === false ) ) {
        $debug->log($jconf['log_dir'], $myjobid . ".log", "[FATAL] Remove path check failed. SAFE EXIT. Invalid path: " . $remove_path, $sendmail = false);
        exit;
    }

//  $debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] Path safe check OK. Path: " . $remove_path, $sendmail = false);
    return true;
}

?>