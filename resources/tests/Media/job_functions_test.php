<?php
// Media conversion job v0 @ 2012/02/??

define('BASE_PATH',	realpath( __DIR__ . '/../../..' ) . '/' );
define('PRODUCTION', false );
define('DEBUG', false );

include_once( BASE_PATH . 'libraries/Springboard/Application/Cli.php');

// Utils
include_once('../../../modules/Jobs/job_utils_base.php');
include_once('../../../modules/Jobs/job_utils_log.php');

// Init
$app = new Springboard\Application\Cli(BASE_PATH, PRODUCTION);

// Load jobs configuration file
$app->loadConfig('modules/Jobs/config_jobs.php');
$jconf = $app->config['config_jobs'];
$myjobid = "test_installation";

// Log init
$debug = Springboard\Debug::getInstance();
$debug->log($jconf['log_dir'], $myjobid . ".log", "Installation testing started", $sendmail = false);

// Check operating system - exit if Windows
if ( iswindows() ) {
	echo "ERROR: Non-Windows process started on Windows platform\n";
	exit;
}

// Watchdog test
$app->watchdog();
	
// Establish database connection
$db = $app->bootstrap->getAdoDB();
if ( is_resource($db->_connectionID) ) {
	$debug->log($jconf['log_dir'], $myjobid . ".log", "[OK] Connected to DB.", $sendmail = false);
} else {
	$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] Faild to connect to DB.", $sendmail = false);
}

// Check if directories readable/writable
// For both type of nodes
$dirs2check = array(
	$app->config['datapath'],
	$app->config['datapath'] . "jobs/",
	$app->config['datapath'] . "logs/",
	$app->config['datapath'] . "logs/jobs/",
	$app->config['datapath'] . "cache/",
	$app->config['datapath'] . "watchdog/"
);
for ( $i = 0; $i < count($dirs2check); $i++ ) {
	if ( !is_writable($dirs2check[$i]) ) {
		$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] Directory " . $dirs2check[$i] . " is not writable. CHECK\n", $sendmail = false);
	} else {
		$debug->log($jconf['log_dir'], $myjobid . ".log", "[OK] Directory " . $dirs2check[$i] . " is writable.\n", $sendmail = false);
	}
}

if ( $app->config['node_role'] == "converter" ) {
	$dirs2check = array(
		$jconf['temp_dir'],
		$jconf['media_dir'],
		$jconf['content_dir'],
		$jconf['ocr_dir'],
		$jconf['doc_dir'],
		$jconf['vcr_dir'],
		$jconf['job_dir'],
		$jconf['log_dir']
	);
	for ( $i = 0; $i < count($dirs2check); $i++ ) {
		if ( !is_writable($dirs2check[$i]) ) {
			$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] Directory " . $dirs2check[$i] . " is not writable. CHECK\n", $sendmail = false);
		} else {
			$debug->log($jconf['log_dir'], $myjobid . ".log", "[OK] Directory " . $dirs2check[$i] . " is writable.\n", $sendmail = false);
		}
	}
}

// SSH testing
//$ssh_command = "ssh -i /home/conv/.ssh/id_rsa conv@telenorstream.videosquare.eu date";
$ssh_command = "ssh -i /home/conv/.ssh/id_rsa conv@stream.videosquare.eu date";
exec($ssh_command, $output, $result);
$output_string = implode("\n", $output);
if ( $result != 0 ) {
	$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] SSH command not successful.\nCommand: " . $ssh_command . "\nOutput: " . $output_string, $sendmail = false);
} else {
	$debug->log($jconf['log_dir'], $myjobid . ".log", "[OK] SSH command successful.\nCommand: " . $ssh_command . "\nOutput: " . $output_string, $sendmail = false);
}

// DB testing
$err = getSomethingFromDB();
if ( $err === false ) {
	$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] DB query was not successful.", $sendmail = false);
} else {
	$debug->log($jconf['log_dir'], $myjobid . ".log", "[OK] DB query result:\n" . print_r($err, true), $sendmail = false);
}

// Send test mail
$mail_head  = "NODE: " . $app->config['node_sourceip'] . "<br/>";
$mail_head .= "SITE: " . $app->config['baseuri'] . "<br/>";
$title = "This is a test message from Videosquare";
$body  = $mail_head . "<br/>" . $title . "<br/><br/>Test message<br/>";
sendHTMLEmail_errorWrapper($title, $body);

/*
// Smarty test + mail send
$smarty = $app->bootstrap->getSmarty();
$smarty->assign('organization', "Organization Name");
$smarty->assign('filename', "1234.mp4");
$smarty->assign('language', "kismiska");
$smarty->assign('recid', "123");
$smarty->assign('supportemail', "support@videosqr.com");
$smarty->assign('domain', "videosquare.eu");

// Get e-mail subject line from localization
$localization = $app->bootstrap->getLocalization();
$subject = $localization('recordings', 'email_conversion_done_subject', "hu");
$subject .= ": 1234.mp4";

// Send e-mail
try {
	$queue = $app->bootstrap->getMailqueue();
	$body = $smarty->fetch('Visitor/Recordings/Email/job_media_converter.tpl');	
echo $body . "\n";

	$queue->sendHTMLEmail("hiba@videosqr.com", $subject, $body);
} catch (exception $err) {
	$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] Cannot send mail to: hiba@videosqr.com\n\n" . trim($body), $sendmail = false);
}
$debug->log($jconf['log_dir'], $myjobid . ".log", "[OK] Mail sent to hiba@videosqr.com. CHECK.", $sendmail = false);
*/

// Close DB connection if open
if ( is_resource($db->_connectionID) ) $db->close();

exit;

function getSomethingFromDB() {
global $jconf, $debug, $db, $myjobid;

	$query = "SELECT * FROM	organizations WHERE id > 1 LIMIT 1";

	$debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] Executing SQL query: " . trim($query), $sendmail = false);

	try {
		$rs = $db->getArray($query);
	} catch (exception $err) {
		$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] SQL query failed." . trim($query), $sendmail = false);
		return false;
	}

	return $rs;
}

?>
