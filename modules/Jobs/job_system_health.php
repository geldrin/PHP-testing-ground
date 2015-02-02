<?php
// Job: maintenance 2012/08/28

define('BASE_PATH', realpath( __DIR__ . '/../..' ) . '/' );
define('PRODUCTION', false );
define('DEBUG', false );

include_once( BASE_PATH . 'libraries/Springboard/Application/Cli.php');

// Utils
include_once('job_utils_base.php');
include_once('job_utils_log.php');

set_time_limit(0);

// Init
$app = new Springboard\Application\Cli(BASE_PATH, PRODUCTION);

// Load jobs configuration file
$app->loadConfig('modules/Jobs/config_jobs.php');
$jconf = $app->config['config_jobs'];
$myjobid = $jconf['jobid_system_health'];

// Log related init
$debug = Springboard\Debug::getInstance();
$debug->log($jconf['log_dir'], $myjobid . ".log", "*************************** Job: System health job started ***************************", $sendmail = false);

// Check operating system - exit if Windows
if ( iswindows() ) {
    echo "ERROR: Non-Windows process started on Windows platform\n";
    exit;
}

// Start an infinite loop - exit if any STOP file appears
if ( is_file( $app->config['datapath'] . 'jobs/' . $myjobid . '.stop' ) or is_file( $app->config['datapath'] . 'jobs/all.stop' ) ) exit;

clearstatcache();

$system_health_log = "";

// Get node role
$node_role = $app->config['node_role'];
$node_status = "ok";

// Config
$alarm_levels['warning'] = 70;
$alarm_levels['critical'] = 90;

// Establish database connection
$db = db_maintain();

if ( $node_role == "converter" ) {
    $converter_node = getConverterNodeByName($app->config['node_sourceip']);
    if ( $converter_node === false ) {
        $debug->log($jconf['log_dir'], $myjobid . ".log", "Converter node " . $app->config['node_sourceip'] . " is not defined in DB.", $sendmail = false);
        exit;
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

$msg = "";
for ( $i = 0; $i < count($dirs2check); $i++) {
    if ( !file_exists($dirs2check[$i]) ) {
        $msg .= "[ERROR] " . $dirs2check[$i] . " does not exist.\n";
        $node_status = "disabledmissingpath";
    }
}
if ( !empty($msg) ) $system_health_log .= "Directory check results:\n" . $msg . "\n\n";

//// Storag free space check
if ( $node_role == "converter" ) {
    $storages2check = array(
        "/",                        // system root
        $app->config['convpath'],   // converter temp path
    );
}
if ( $node_role == "frontend" ) {
    $storages2check = array(
        "/",                        // system root
        $app->config['uploadpath'], // upload path
        $app->config['mediapath'],  // main storage
    );
}

$msg = "";
$diskinfo = array();
$values = array(
    'status'                => null,
    'storagesystemtotal'    => 0,
    'storagesystemfree'     => 0,
    'storagetemptotal'      => 0,
    'storagetempfree'       => 0,
    'cpuusage'              => 0,
);
for ( $i = 0; $i < count($storages2check); $i++) {
    $diskinfo = checkDiskSpace($storages2check[$i]);
    if ( ( $diskinfo['free_percent'] >= $alarm_levels['warning'] ) and ( $diskinfo['free_percent'] < $alarm_levels['critical'] ) ) {
        $msg .= "File system free space for " . $storages2check[$i] . ": WARNING\n";
        $msg .= "\tTotal: " . round($diskinfo['total'] / 1024 / 1024 / 1024, 2) . "GB Free: " . round($diskinfo['free'] / 1024 / 1024 / 1024, 2) . "GB (" . $diskinfo['free_percent'] . "%)\n";
        $msg .= "\t***** PLEASE CHECK *****\n\n";
    }
    if ( $diskinfo['free_percent'] >= $alarm_levels['critical'] ) {
        $msg .= "File system free space for " . $storages2check[$i] . ": CRITICAL\n";
        $msg .= "\tTotal: " . round($diskinfo['total'] / 1024 / 1024 / 1024, 2) . "GB Free: " . round($diskinfo['free'] / 1024 / 1024 / 1024, 2) . "GB (" . $diskinfo['free_percent'] . "%)\n";
        $msg .= "\t***** CHECK ASAP *****\n\n";
        $node_status = "disabledstoragelow";
    }
    if ( $node_role == "converter" ) {
        if ( $storages2check[$i] == "/" ) {
            $values['storagesystemtotal'] = $diskinfo['total'];
            $values['storagesystemfree'] = $diskinfo['free'];
        }
        if ( $storages2check[$i] == $app->config['convpath'] ) {
            $values['storagetemptotal'] = $diskinfo['total'];
            $values['storagetempfree'] = $diskinfo['free'];
        }
    }
}

// Update DB with disk data
if ( $node_role == "converter" ) {
    $values['status'] = $node_status;
    if ( is_numeric($node_cpu) ) $values['cpuusage'] = $node_cpu;
    $converterNodeObj = $app->bootstrap->getModel('converter_nodes');
    $converterNodeObj->select($converter_node['id']);
    $converterNodeObj->updateRow($values);
}


if ( !empty($msg) ) $system_health_log .= "Storage space check results:\n" . $msg . "\n\n";

echo $system_health_log . "\n";

// Close DB connection if open
if ( is_resource($db->_connectionID) ) $db->close();

exit;

function checkDiskSpace($dir) {

    $result = array();

    $result['total'] = disk_total_space($dir);
    $result['free'] = disk_free_space($dir);
    $result['free_percent'] = round($result['free'] * 100 / $result['total'], 2);
         
    return $result;
}

function getConverterNodeByName($converter_node) {
global $db, $myjobid, $debug, $jconf;

	$query = "
		SELECT
            cn.id,
            cn.server,
            cn.serverip,
            cn.shortname,
            cn.default,
            cn.disabled
		FROM
			converter_nodes AS cn
		WHERE
			cn.server = '" . $converter_node . "' AND
            cn.disabled = 0
        LIMIT 1";

//echo $query . "\n";

	try {
		$cn = $db->getArray($query);
	} catch (exception $err) {
		$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] SQL query failed." . trim($query), $sendmail = true);
		return false;
	}

	// Check if any record returned
	if ( count($cn) < 1 ) return false;
    
    return $cn[0];
}

?>