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

// Config
$alarm_levels['warning'] = 70;
$alarm_levels['critical'] = 90;
$node_role = $app->config['node_role'];
$firstround = true;
$sleep_time = 60;
$db_outage = false;
$db_outage_starttime = 0;

// Prepare possibly needed mail intro with site information
$mail_head  = "NODE: " . $app->config['node_sourceip'] . "\n";
$mail_head .= "ROLE: " . $app->config['node_role'] . "\n";
$mail_head .= "SITE: " . $app->config['baseuri'] . "\n";
$mail_head .= "JOB: " . $myjobid . ".php\n";

// Start an infinite loop - exit if any STOP file appears
while( !is_file( $app->config['datapath'] . 'jobs/' . $myjobid . '.stop' ) and !is_file( $app->config['datapath'] . 'jobs/all.stop' ) ) {

    echo "--- WOKE UP! ---: " . date("H:i:s") . "\n";

    clearstatcache();

    $system_health_log = "";

    // Time: get minutes in the hour
    $minutes = date("i");
    echo "min = " . $minutes . "\n";
    
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
                $debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] Cannot create DB unavailable flag file at " . $db_unavailable_flag . ". CHECK!", $sendmail = true);
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
            if ( ( $db_outage_minutes % 30 ) == 0 ) {
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
                // !!! sendmail = true eseten mail storm !!! ?
                $debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] Cannot remove DB unavailable flag file at " . $db_unavailable_flag . ". Will prevent jobs from running. CHECK!", $sendmail = true);
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
            echo "getnodeinfo: " . date("H:i:s") . "\n";
            $node_info = getNodeByName($app->config['node_sourceip']);
            if ( $node_info === false ) {
                $debug->log($jconf['log_dir'], $myjobid . ".log", "Node " . $app->config['node_sourceip'] . " is not defined in DB!", $sendmail = false);
                $usedb = false;
            }
        }
    }

    //// Directory check: do they exist?
    // Schedule: only once, when job started
    if ( $firstround ) {
        
        echo "dircheck: " . date("H:i:s") . "\n";
        
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
            $debug->log($jconf['log_dir'], $myjobid . ".log", "Directory check results:\n" . $msg, $sendmail = false);    
            $system_health_log .= $msg . "\n\n";
        }
    }

    //// CPU usage
    $node_cpu = trim(`top -b -n 1 | grep "^%Cpu" | awk '{ print $2+$4+$6; }'`);
    
    //// Storage free space check
    if ( $firstround or empty($node_info) or ( ( $minutes % 5 ) == 0 ) ) {
        echo "storage: " . date("H:i:s") . "\n";
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
            'storagesystemtotal'    => 0,
            'storagesystemfree'     => 0,
            'storagetotal'          => 0,
            'storagefree'           => 0,
            'cpuusage'              => 0,
        );
        for ( $i = 0; $i < count($storages2check); $i++) {
            $diskinfo = checkDiskSpace($storages2check[$i]['path']);
            if ( ( $diskinfo['free_percent'] >= $alarm_levels['warning'] ) and ( $diskinfo['free_percent'] < $alarm_levels['critical'] ) ) {
                $msg .= "File system free space for " . $storages2check[$i]['path'] . ": WARNING\n";
                $msg .= "\tTotal: " . round($diskinfo['total'] / 1024 / 1024 / 1024, 2) . "GB Free: " . round($diskinfo['free'] / 1024 / 1024 / 1024, 2) . "GB (" . $diskinfo['free_percent'] . "%)\n";
                $msg .= "\t***** PLEASE CHECK *****\n\n";
            }
            if ( $diskinfo['free_percent'] >= $alarm_levels['critical'] ) {
                $msg .= "File system free space for " . $storages2check[$i]['path'] . ": CRITICAL\n";
                $msg .= "\tTotal: " . round($diskinfo['total'] / 1024 / 1024 / 1024, 2) . "GB Free: " . round($diskinfo['free'] / 1024 / 1024 / 1024, 2) . "GB (" . $diskinfo['free_percent'] . "%)\n";
                $node_status = "disabledstoragelow";
                
                // ## Start quick cleanup of temp areas
                if ( isset($storages2check[$i]['cleanuppath']) and file_exists($storages2check[$i]['cleanuppath']) ) {
                    if ( !isset($storages2check[$i]['cleanupdays']) ) $storages2check[$i]['cleanupdays'] = 20;
                    $err = findRemoveFilesOlderThanDays($storages2check[$i]['cleanuppath'], $storages2check[$i]['cleanupdays'], true);
                    $msg .= "\tQuick cleanup: " . round($err['size'] / 1024 / 1024, 2) . "MB (" . $err['value'] . " files) removed from " . $storages2check[$i]['cleanuppath'] . "\n";
                }
                
                $msg .= "\t***** CHECK ASAP *****\n\n";
            }
            if ( !empty($storages2check[$i]['db']) ) {
                $values[$storages2check[$i]['db'] . 'total'] = $diskinfo['total'];
                $values[$storages2check[$i]['db'] . 'free'] = $diskinfo['free'];
            }
        }
   
        // Update DB with disk data
        $values['statusstorage'] = $node_status;
        if ( is_numeric($node_cpu) ) $values['cpuusage'] = $node_cpu;
        if ( $usedb ) {
            $converterNodeObj = $app->bootstrap->getModel('infrastructure_nodes');
            $converterNodeObj->select($node_info['id']);
            $converterNodeObj->updateRow($values);
        } else {
            $msg .= "[INFO] DB is unreachable. Storage status information:\n" . print_r($values, true) . "\n";
        }

        if ( !empty($msg) ) {
            $debug->log($jconf['log_dir'], $myjobid . ".log", "Storage check results:\n" . $msg, $sendmail = false);      
            $system_health_log .= $msg . "\n\n";
        }
    }
        
    // SSH ping all frontends from converter
    if ( $firstround or ( $minutes % 5 ) == 0 ) {
        echo "sshping: " . date("H:i:s") . "\n";
        if ( ( $node_role == "converter" ) and $usedb ) {
            $msg = "";
            $values = array('statusnetwork' => $node_info['statusnetwork']);
            $node_frontends = getNodesByType("frontend");
            if ( $node_frontends === false ) {
                $debug->log($jconf['log_dir'], $myjobid . ".log", "No front-end defined in DB!", $sendmail = false);
            } else {
                $ssh_all_ok = true;
                while ( !$node_frontends->EOF ) {

                    $node_frontend = array();
                    $node_frontend = $node_frontends->fields;
                    
                    $ssh_command = "ssh -i " . $app->config['ssh_key'] . " " . $app->config['ssh_user'] . "@" . $node_frontend['server'] . " date";
                    exec($ssh_command, $output, $result);
                    $output_string = implode("\n", $output);
                    if ( $result != 0 ) {
                        updateInfrastructureNodeStatus($node_info['id'], "statusnetwork", "disabledfrontendunreachable:" . $node_frontend['server']);
                        $msg .= "[ERROR] Unsuccessful SSH ping to: " . $node_frontend['server'] . ".\n\tCommand: " . $ssh_command . "\n\tOutput: " . $output_string;
                        $ssh_all_ok = false;
                    }
                    
                    $node_frontends->MoveNext();
                }
                // Status changed back to OK
                if ( ( $ssh_all_ok ) and ( $node_info['statusnetwork'] != "ok" ) ) updateInfrastructureNodeStatus($node_info['id'], "statusnetwork", "ok");
            }
            if ( !empty($msg) ) {
                $debug->log($jconf['log_dir'], $myjobid . ".log", "SSH frontend ping results:\n" . $msg, $sendmail = false);      
                $system_health_log .= "SSH frontend ping results:\n" . $msg . "\n\n";
            }
        }
    }
    
    //echo $system_health_log . "\n";
    
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