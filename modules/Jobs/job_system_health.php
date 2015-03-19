<?php
// Job: system health

define('BASE_PATH', realpath( __DIR__ . '/../..' ) . '/' );
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
$myjobid = $jconf['jobid_system_health'];

// Log related init
$debug = Springboard\Debug::getInstance();
$debug->log($jconf['log_dir'], $jconf['jobid_system_health'] . ".log", "*************************** Job: System Health started ***************************", $sendmail = false);

// Check operating system - exit if Windows
if ( iswindows() ) {
    $debug->log($jconf['log_dir'], $myjobid . ".log", "ERROR: Non-Windows process started on Windows platform" , $sendmail = false);
    exit;
}

//// Config
// Alarm levels for storage free space
$alarm_levels['warning'] = 80;
$alarm_levels['critical'] = 90;
// Sleep time between each check
$sleep_time = 60;
// Node role: front-end/converter
$node_role = $app->config['node_role'];
// Massage resend timeout
$mail_report_resend_timeout = 10*60;
// DB alert repeat every N minutes
$db_outage_alert_every_mins = 30;
// Storage check every N minutes
$storage_check_every_mins = 60;
// SSH front-end ping every N minutes
$ssh_check_every_mins = 10;
// Helping variables
$firstround = true;
$db_outage = false;
$db_outage_starttime = 0;
$ssh_outage = array();

// Prepare possibly needed mail intro with site information
$mail_head  = "NODE: " . $app->config['node_sourceip'] . "\n";
$mail_head .= "ROLE: " . $app->config['node_role'] . "\n";
$mail_head .= "SITE: " . $app->config['baseuri'] . "\n";
$mail_head .= "JOB: " . $myjobid . ".php\n";

// MD5 mail hash to avoid repeating messages
$mail_hash = array();

// Start an infinite loop - exit if any STOP file appears
while( !is_file( $app->config['datapath'] . 'jobs/' . $myjobid . '.stop' ) and !is_file( $app->config['datapath'] . 'jobs/all.stop' ) ) {

    clearstatcache();

    $system_health_log = "";

    // Time: get minutes in the hour
    $minutes = date("i");
    
    // Assume status is OK
    $node_status = "ok";

    //// DB: Establish database connection, ping database in non-blocking mode
    // Schedule: every round
    $usedb = true;
    $db_unavailable_flag = $app->config['dbunavailableflagpath'];
    $db = db_maintain($nonblockingmode = true);
    // Do not use DB if not available, enable DBUNAVAILABLE file flag for other jobs to stop useless polling
    if ( $db === false ) {
                
        // Set DBUNAVAILABLE flag in filesystem
        $usedb = false;
        if ( !file_exists($db_unavailable_flag) ) {
            
            // Start outage timer
            $db_outage = true;
            $db_outage_starttime = time();

            $err = touch($db_unavailable_flag);
            if ( $err === false ) {
                $debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] Cannot create DB unavailable flag file at " . $db_unavailable_flag . ". CHECK!", $sendmail = false);
            } else {
                $outage_time = time() - $db_outage_starttime;
                $title = "[ERROR] DB has been unavailable for " . seconds2DaysHoursMinsSecs($outage_time) . " time. DBUNAVAILABLE flag created at " . $db_unavailable_flag . ". Job polls are blocked until DB comes back.";
                $body  = $mail_head . "\n" . $title . "\n";
                sendHTMLEmail_errorWrapper($title, nl2br($body));
                $debug->log($jconf['log_dir'], $myjobid . ".log", $title, $sendmail = false);
            }
        } else {
            // Send notice in every additional 30 minutes
            $db_outage_minutes = floor( ( time() - $db_outage_starttime ) / 60 );
            if ( ( $db_outage_minutes % $db_outage_alert_every_mins ) == 0 ) {
                $outage_time = time() - $db_outage_starttime;
                $title = "[ERROR] DB has been unavailable for " . seconds2DaysHoursMinsSecs($outage_time) . " time. DBUNAVAILABLE flag is in place at " . $db_unavailable_flag . ". Job polls are blocked until DB comes back.";
                $body  = $mail_head . "\n" . $title . "\n";
                sendHTMLEmail_errorWrapper($title, nl2br($body));
                $debug->log($jconf['log_dir'], $myjobid . ".log", $title, $sendmail = false);
            }
        }
            
    } else {
        // Remove DBUNAVAILABLE flag if DB is recovered
        if ( file_exists($db_unavailable_flag) ) {
            $err = unlink($db_unavailable_flag);
            if ( $err === false ) {
                $debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] Cannot remove DB unavailable flag file at " . $db_unavailable_flag . ". Will prevent jobs from running. CHECK!", $sendmail = false);
            } else {
                $title = "[OK] DB is back after " . seconds2DaysHoursMinsSecs(time() - $db_outage_starttime) . " outage. DBUNAVAILABLE flag removed from " . $db_unavailable_flag . ". Job polls are now enabled.";
                $body  = $mail_head . "\n" . $title . "\n";
                sendHTMLEmail_errorWrapper($title, nl2br($body));
                $debug->log($jconf['log_dir'], $myjobid . ".log", $title, $sendmail = false);
            }
        }
        
        $db_outage = false;
        $db_outage_starttime = 0;
    }

    //// Get node information from DB
    if ( $usedb ) {
        if ( empty($node_info) or ( ( $minutes % 10 ) == 0 ) ) {
            $node_info = getNodeByName($app->config['node_sourceip']);
            if ( $node_info === false ) {
                $debug->log($jconf['log_dir'], $myjobid . ".log", "[WARN] Node " . $app->config['node_sourceip'] . " is not defined in DB!", $sendmail = false);
                $usedb = false;
            }
        }
    }

    //// Directory check: do they exist?
    // Schedule: only once, when job started
    if ( $firstround ) {
        
        // For both type of nodes
        $dirs2check = array(
            $app->config['datapath'],
            $app->config['datapath'] . "jobs/",
            $app->config['datapath'] . "logs/",
            $app->config['datapath'] . "logs/jobs/",
            $app->config['datapath'] . "cache/",
            $app->config['datapath'] . "watchdog/"
        );

        // For converters
        if ( $node_role == "converter" ) {

            $dirs2check_conv = array(
                $app->config['convpath'],
                $jconf['media_dir'],
                $jconf['content_dir'],
                $jconf['ocr_dir'],
                $jconf['doc_dir'],
                $jconf['vcr_dir'],
            );
            $dirs2check = array_merge($dirs2check, $dirs2check_conv);
        }

        // For frontends
        if ( $node_role == "frontend" ) {

            $dirs2check_fe = array(
                $app->config['uploadpath'],
                $app->config['chunkpath'],
                $app->config['uploadpath'] . "attachments/",
                $app->config['uploadpath'] . "recordings/",
                $app->config['uploadpath'] . "useravatars/",
                $app->config['uploadpath'] . "web_uploads/",
                $app->config['storagepath'],
                $app->config['mediapath'],
                $app->config['recordingpath'],
                $app->config['useravatarpath'],
                $app->config['livestreampath'],
            );
            $dirs2check = array_merge($dirs2check, $dirs2check_fe);
            
        }

        // Loop through directories
        $msg = "";
        for ( $i = 0; $i < count($dirs2check); $i++) {
            if ( !file_exists($dirs2check[$i]) ) {
                $msg .= "[ERROR] " . $dirs2check[$i] . " does not exist.\n";
                $node_status = "disabledmissingpath";
            } else {
                if ( !is_writeable($dirs2check[$i]) ) {
                    $msg .= "[ERROR] " . $dirs2check[$i] . " is not writeable.\n";
                    $node_status = "disabledfailpath";
                }
            }
        }
        if ( !empty($msg) ) {
            $debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] Directory check results:\n" . $msg, $sendmail = false);    
            $system_health_log .= "[ERROR] Directory check results:\n" . $msg . "\n";
        }
    }

    //// Performance indicators: CPU, load average
    // CPU usage
    $node_cpu = trim(`top -b -n 1 | grep "Cpu(s)" | awk '{ print $2+$4+$6; }'`);
    // Load average
    $load = trim(`cat /proc/loadavg | awk '{print $1 "#" $2 "#" $3}'`);
    $tmp = explode("#", $load, 3);
    $node_load['min'] = $tmp[0];
    $node_load['min5'] = $tmp[1];
    $node_load['min15'] = $tmp[2];
    
    //// Storage free space check
    if ( $firstround or empty($node_info) or ( ( $minutes % $storage_check_every_mins ) == 0 ) ) {
        
        if ( $node_role == "converter" ) {
            $storages2check = array(
                0   => array(
                            'path'          => "/",                 // System root
                            'db'            => "storagesystem",
                            'cleanuppath'   => "/tmp",              // if auto cleanup is possible, what path?
                            'cleanupdays'   => 20
                        ),
                1   => array(
                            'path'          => $app->config['convpath'],    // Converter temp path
                            'db'            => "storagework",
                            'cleanuppath'   => $app->config['convpath'],
                            'cleanupdays'   => 10
                        )
            );
        }
        if ( $node_role == "frontend" ) {
            $storages2check = array(
                0   => array(
                            'path'  => "/",                         // System root
                            'db'    => "storagesystem",
                        ),
                1   => array(
                            'path'  => $app->config['uploadpath'],  // Upload path
                            'db'    => "storagework",
                        ),
                2   => array(
                            'path'  => $app->config['mediapath'],   // Media storage
                            'db'    => "storage",
                        )
            );
        }

        $msg = "";
        $diskinfo = array();
        $values = array(
            'statusstorage'         => null,
            'storagesystemtotal'    => null,
            'storagesystemfree'     => null,
            'storagetotal'          => null,
            'storagefree'           => null,
            'cpuusage'              => null,
            'cpuloadmin'            => null,
            'cpuload5min'           => null,
            'cpuload15min'          => null
        );
        for ( $i = 0; $i < count($storages2check); $i++) {
            $diskinfo = checkDiskSpace($storages2check[$i]['path']);
            if ( ( $diskinfo['used_percent'] >= $alarm_levels['warning'] ) and ( $diskinfo['used_percent'] < $alarm_levels['critical'] ) ) {
                $msg .= "File system free space for " . $storages2check[$i]['path'] . ": WARNING\n";
                $msg .= "\tTotal: " . round($diskinfo['total'] / 1024 / 1024 / 1024, 2) . "GB Free: " . round($diskinfo['free'] / 1024 / 1024 / 1024, 2) . "GB (" . $diskinfo['used_percent'] . "%)\n";
                $msg .= "\t***** PLEASE CHECK *****\n";
            }
            if ( $diskinfo['used_percent'] >= $alarm_levels['critical'] ) {
                $msg .= "File system free space for " . $storages2check[$i]['path'] . ": CRITICAL\n";
                $msg .= "\tTotal: " . round($diskinfo['total'] / 1024 / 1024 / 1024, 2) . "GB Free: " . round($diskinfo['free'] / 1024 / 1024 / 1024, 2) . "GB (" . $diskinfo['used_percent'] . "%)\n";
                $node_status = "disabledstoragelow";
                
                // ## Start quick cleanup of temp areas
                if ( isset($storages2check[$i]['cleanuppath']) and file_exists($storages2check[$i]['cleanuppath']) ) {
                    if ( !isset($storages2check[$i]['cleanupdays']) ) $storages2check[$i]['cleanupdays'] = 20;
                    $err = findRemoveFilesOlderThanDays($storages2check[$i]['cleanuppath'], $storages2check[$i]['cleanupdays'], true);
                    $msg .= "\tQuick cleanup: " . round($err['size'] / 1024 / 1024, 2) . "MB (" . $err['value'] . " files) removed from " . $storages2check[$i]['cleanuppath'] . "\n";
                }
                
                $msg .= "\t***** CHECK ASAP *****\n";
            }
            if ( !empty($storages2check[$i]['db']) ) {
                $values[$storages2check[$i]['db'] . 'total'] = $diskinfo['total'];
                $values[$storages2check[$i]['db'] . 'free'] = $diskinfo['free'];
            }
        }
   
        // Update DB with disk data
        $values['statusstorage'] = $node_status;
        if ( is_numeric($node_cpu) ) $values['cpuusage'] = $node_cpu;
        if ( isset($node_load) ) {
            $values['cpuloadmin'] = $node_load['min'];
            $values['cpuload5min'] = $node_load['min5'];
            $values['cpuload15min'] = $node_load['min15'];
        }
        if ( $usedb ) {
            $converterNodeObj = $app->bootstrap->getModel('infrastructure_nodes');
            $converterNodeObj->select($node_info['id']);
            $converterNodeObj->updateRow($values);
        }

        if ( !empty($msg) ) $system_health_log .= "[ERROR] Storage free space issues:\n\n" . $msg . "\n";
    }

    // SSH ping all frontends from converter
    if ( $firstround or ( $minutes % $ssh_check_every_mins ) == 0 ) {
        if ( ( $node_role == "converter" ) and $usedb ) {
            $msg = "";
            $ssh_all_ok = true;
            $ssh_status = "";
            $values = array('statusnetwork' => $node_info['statusnetwork']);
            $node_frontends = getNodesByType("frontend");
            if ( $node_frontends === false ) {
                $debug->log($jconf['log_dir'], $myjobid . ".log", "[WARN] No front-end defined in DB!", $sendmail = false);
            } else {
                
                // SSH ping: Loop through front-ends
                while ( !$node_frontends->EOF ) {

                    $node_frontend = array();
                    $node_frontend = $node_frontends->fields;
                                        
                    $ssh_unavailable_flag = $app->config['sshunavailableflagpath'] . "." . $node_frontend['server'];
                    
                    $output = array();
                    $ssh_command = "ssh -i " . $app->config['ssh_key'] . " " . $app->config['ssh_user'] . "@" . $node_frontend['server'] . " date 2>&1";
                    exec($ssh_command, $output, $result);                    
                    $output_string = implode("\n", $output);
                    if ( $result != 0 ) {

                    // SSH connection to front-end was not successful (problem first detected)
                        if ( !file_exists($ssh_unavailable_flag) ) {
                        
                            $ssh_outage[$node_frontend['server']]['outage'] = true;
                            $ssh_outage[$node_frontend['server']]['outage_starttime'] = time();
                            
                            // Log error
                            $err = touch($ssh_unavailable_flag);
                            if ( $err === false ) {
                                $debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] Cannot create SSH unavailable flag file at " . $ssh_unavailable_flag . ". CHECK!", $sendmail = false);
                            } else {
                                $outage_time = time() - $ssh_outage[$node_frontend['server']]['outage_starttime'];
                                $msg .= "[ERROR] Unsuccessful SSH ping to: " . $node_frontend['server'] . ". SSH has been unavailable for " . seconds2DaysHoursMinsSecs($outage_time) . " time.\n\tCommand: " . $ssh_command . "\n\tOutput: " . $output_string . "\n\n";
                            }

                        } else {

                            if ( !isset($ssh_outage[$node_frontend['server']]) ) {
                                $ssh_outage[$node_frontend['server']]['outage'] = true;
                                $ssh_outage[$node_frontend['server']]['outage_starttime'] = time();
                            }
                        
                            // Send notice in every additional 30 minutes
                            $ssh_outage_minutes = floor( ( time() - $ssh_outage[$node_frontend['server']]['outage_starttime'] ) / 60 );
                            if ( ( $ssh_outage_minutes % 30 ) == 0 ) {
                                $outage_time = time() - $ssh_outage[$node_frontend['server']]['outage_starttime'];
                                $msg .= "[ERROR] Unsuccessful SSH ping to: " . $node_frontend['server'] . ". SSH has been unavailable for " . seconds2DaysHoursMinsSecs($outage_time) . " time. \n\tCommand: " . $ssh_command . "\n\tOutput: " . $output_string. "\n\n";
                            }               
                        }

                        $ssh_status .= "disabledfrontendunreachable:" . $node_frontend['server'];

                        // At least one SSH connection problem is pending
                        $ssh_all_ok = false;
                        
                    } else {
                        
                        if ( !isset($ssh_outage[$node_frontend['server']]) ) {
                            $ssh_outage[$node_frontend['server']]['outage'] = false;
                            $ssh_outage[$node_frontend['server']]['outage_starttime'] = time();
                        }
                            
                        // Remove SSHUNAVAILABLE flag if SSH was recovered
                        if ( file_exists($ssh_unavailable_flag) ) {
                            $err = unlink($ssh_unavailable_flag);
                            if ( $err === false ) {
                                $debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] Cannot remove SSH unavailable flag file at " . $ssh_unavailable_flag . ". Will prevent jobs from download/upload. CHECK!", $sendmail = false);
                            } else {
                                $outage_time = time() - $ssh_outage[$node_frontend['server']]['outage_starttime'];
                                $msg .= "[OK] Front-end " . $node_frontend['server'] . " is back after " . seconds2DaysHoursMinsSecs($outage_time) . "\n\n";
                            }
                        }
        
                        // Reinit flags
                        $ssh_outage[$node_frontend['server']]['outage'] = false;
                        $ssh_outage[$node_frontend['server']]['outage_starttime'] = 0;
                        
                    }
                    
                    $node_frontends->MoveNext();
                }
                
                // Status changed back to OK
                if ( ( $ssh_all_ok ) and ( $node_info['statusnetwork'] != "ok" ) ) {
                    updateInfrastructureNodeStatus($node_info['id'], "statusnetwork", "ok");
                } else {
                    updateInfrastructureNodeStatus($node_info['id'], "statusnetwork", $ssh_status);
                }
            }
            
            if ( !empty($msg) ) {
                $system_health_log .= "SSH frontend ping results:\n" . $msg . "\n";
            }
        }
    }
    
    // Send error summary (prevent repetition with md5 checksums)
    if ( !empty($system_health_log) ) {
        $md5 = md5($system_health_log);
//echo "check 4: " . $md5 . "\n";
        if ( !isset($mail_hash[$md5]) ) {
//echo "no match!\n";
            // Send mail
            sendHTMLEmail_errorWrapper("[ERROR] System health check error report", nl2br($mail_head . "\n" . $system_health_log));
            $mail_hash[$md5]['sentmail'] = true;
            $mail_hash[$md5]['sentmail_date'] = time();
            
            // Log summary
            $debug->log($jconf['log_dir'], $myjobid . ".log", $system_health_log, $sendmail = false);
        } else {
//echo "already sent. sec ago: " . (time() - $mail_hash[$md5]['sentmail_date']) . "\n";
            if ( ( time() - $mail_hash[$md5]['sentmail_date'] ) > $mail_report_resend_timeout ) {
                unset($mail_hash[$md5]);
//echo "zero! send again next time\n";
            } else {
//echo "found! not sending mail\n";
            }
        }

    }
    
    // Maintain mail hash
    foreach ($mail_hash as $idx => $value) {
        if ( ( time() - $mail_hash[$idx]['sentmail_date'] ) > $mail_report_resend_timeout ) {
            unset($mail_hash[$idx]);
            echo "idx: " . $idx . " cleaned up!\n";
        }
    }

//    echo "post cleanup:\n";
//    var_dump($mail_hash);
    
    if ( $firstround ) $firstround = false;

    // Close DB connection if open
    if ( ( $db !== false ) and is_resource($db->_connectionID) ) $db->close();

    sleep($sleep_time);
}

exit;

function checkDiskSpace($dir) {

    $result = array();

    $result['total'] = disk_total_space($dir);
    $result['free'] = disk_free_space($dir);
    $result['free_percent'] = round($result['free'] * 100 / $result['total'], 2);
    $result['used_percent'] = 100 - $result['free_percent'];
    
    return $result;
}

function getNodeByName($node) {
global $db, $myjobid, $debug, $jconf;

	$query = "
		SELECT
            inode.id,
            inode.server,
            inode.serverip,
            inode.shortname,
            inode.default,
            inode.statusstorage,
            inode.statusnetwork,
            inode.disabled
		FROM
			infrastructure_nodes AS inode
		WHERE
			inode.server = '" . $node . "' AND
            inode.disabled = 0
        LIMIT 1";

	try {
		$in = $db->getArray($query);
	} catch (exception $err) {
		$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] SQL query failed." . trim($query), $sendmail = true);
		return false;
	}

	// Check if any record returned
	if ( count($in) < 1 ) return false;
    
    return $in[0];
}

function getNodesByType($type = "frontend") {
global $db, $myjobid, $debug, $jconf;

	$query = "
		SELECT
            inode.id,
            inode.server,
            inode.serverip,
            inode.type,
            inode.shortname,
            inode.default,
            inode.statusstorage,
            inode.statusnetwork,
            inode.disabled
		FROM
			infrastructure_nodes AS inode
		WHERE
			inode.type = '" . $type . "' AND
            inode.disabled = 0
        LIMIT 1";

	try {
		$in = $db->Execute($query);
	} catch (exception $err) {
		$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] SQL query failed." . trim($query), $sendmail = true);
		return false;
	}

    // Check if any record returned
	if ( $in->RecordCount() < 1 ) return false;
        
    return $in;
}

?>