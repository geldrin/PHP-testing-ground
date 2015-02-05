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

// Check operating system - exit if Windows
if ( iswindows() ) {
    echo "ERROR: Non-Windows process started on Windows platform\n";
    exit;
}

// Config
$alarm_levels['warning'] = 70;
$alarm_levels['critical'] = 90;

// Start an infinite loop - exit if any STOP file appears
if ( is_file( $app->config['datapath'] . 'jobs/' . $myjobid . '.stop' ) or is_file( $app->config['datapath'] . 'jobs/all.stop' ) ) exit;

clearstatcache();

$system_health_log = "";

// Get node role
$node_role = $app->config['node_role'];
$node_status = "ok";

// Establish database connection, ping database in non-blocking mode
$usedb = true;
$db_unavailable_flag = $app->config['dbunavailableflagpath'];
$db = db_maintain($nonblockingmode = true);
// Do not use DB if not available, enable DBUNAVAILABLE file flag for other jobs to stop useless polling
if ( $db === false ) {
    $usedb = false;
    if ( !file_exists($db_unavailable_flag) ) {
        $err = touch($db_unavailable_flag);
        if ( $err === false ) {
            $debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] Cannot create DB unavailable flag file at " . $db_unavailable_flag . ". CHECK!", $sendmail = false);
        } else {
            $debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] DB is unavailable. DBUNAVAILABLE flag created at " . $db_unavailable_flag . ". Job polls are blocked until DB comes back.", $sendmail = false);
        }
    }
} else {
    // Remove DBUNAVAILABLE flag if DB is recovered
    if ( file_exists($db_unavailable_flag) ) {
        $err = unlink($db_unavailable_flag);
        if ( $err === false ) {
            // !!! sendmail = true eseten mail storm !!! ?
            $debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] Cannot remove DB unavailable flag file at " . $db_unavailable_flag . ". Will prevent jobs from running. CHECK!", $sendmail = false);
        } else {
            $debug->log($jconf['log_dir'], $myjobid . ".log", "[OK] DB is back. DBUNAVAILABLE flag removed from " . $db_unavailable_flag . ". Job polls are now enabled.", $sendmail = true);
        }
    }
}

// Get node information from DB
if ( $usedb ) {
    $node_info = getNodeByName($app->config['node_sourceip']);
    if ( $node_info === false ) {
        $debug->log($jconf['log_dir'], $myjobid . ".log", "Node " . $app->config['node_sourceip'] . " is not defined in DB!", $sendmail = false);
        $usedb = false;
    }
}
    
// CPU usage
$node_cpu = trim(`top -b -n 1 | grep "^%Cpu" | awk '{ print $2+$4+$6; }'`);

//// Directory check: do they exist?

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
    }
}
if ( !empty($msg) ) {
    $debug->log($jconf['log_dir'], $myjobid . ".log", "Directory check results:\n" . $msg, $sendmail = false);    
    $system_health_log .= $msg . "\n\n";
}

//// Storag free space check
if ( $node_role == "converter" ) {
    $storages2check = array(
        0   => array(
                    'path'  => "/",                         // system root
                    'db'    => "storagesystem",
                ),
        1   => array(
                    'path'  => $app->config['convpath'],   // converter temp path
                    'db'    => "storagework",
                )
    );
}
if ( $node_role == "frontend" ) {
    $storages2check = array(
        0   => array(
                    'path'  => "/",                         // system root
                    'db'    => "storagesystem",
                ),
        1   => array(
                    'path'  => $app->config['uploadpath'],   // upload path
                    'db'    => "storagework",
                ),
        2   => array(
                    'path'  => $app->config['mediapath'],   // storage
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
        $msg .= "\t***** CHECK ASAP *****\n\n";
        $node_status = "disabledstoragelow";
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

// SSH ping all frontends from converter
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
            $ssh_command = "ssh -i " . $jconf['ssh_key'] . " " . $jconf['ssh_user'] . "@" . $node_frontend['server'] . " date";
            exec($ssh_command, $output, $result);
            $output_string = implode("\n", $output);
            $result = 1;
            if ( $result != 0 ) {
                updateInfrastructureNodeStatus($node_info['id'], "statusnetwork", "disabledfrontendunreachable:" . $node_frontend['server']);
                $msg = "[ERROR] Unsuccessful ping to: " . $node_frontend['server'] . ".\n\tCommand: " . $ssh_command . "\n\tOutput: " . $output_string;
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

echo $system_health_log . "\n";

// Close DB connection if open
if ( ( $db !== false )  and is_resource($db->_connectionID) ) $db->close();

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