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

$logfile = $jconf['log_dir'] . date("Y-m-") . $myjobid . ".log";
$summary_result = true;

// Check operating system - exit if Windows
if ( iswindows() ) {
	echo "ERROR: Non-Windows process started on Windows platform\n";
	exit;
}

// Watchdog test
$app->watchdog();

// Establish database connection
try {
	$db = $app->bootstrap->getAdoDB();
} catch (exception $err) {
	$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] Failed to connect to DB.", $sendmail = false);
	$summary_result = false;
}
if ( is_resource($db->_connectionID) ) $debug->log($jconf['log_dir'], $myjobid . ".log", "[OK] Connected to DB.", $sendmail = false);

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
$err = checkDirectories($dirs2check);
if ( !$err ) {
	echo "[ERROR] One or more directory check failed. PLEASE CHECK!";
	$summary_result = false;
}
// Node role specific checks
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
	$err = checkDirectories($dirs2check);
	if ( !$err ) {
		echo "[ERROR] One or more directory check failed. PLEASE CHECK!";
		$summary_result = false;
	}
} else {
	// Front-end specific checks
	$dirs2check = array(
		$app->config['storagepath'],
		$app->config['uploadpath'],
		$app->config['chunkpath'],
		$app->config['useravatarpath'],
		$app->config['mediapath'],
		$app->config['recordingpath']
	);
	$err = checkDirectories($dirs2check);
	if ( !$err ) {
		echo "[ERROR] One or more directory check failed. PLEASE CHECK!";
		$summary_result = false;
	}
}

// SSH testing
if ( $app->config['node_role'] == "converter" ) {
	$ssh_command = "ssh -i /home/conv/.ssh/id_dsa conv@" . $app->config['fallbackstreamingserver'] . " date";
	exec($ssh_command, $output, $result);
	$output_string = implode("\n", $output);
	if ( $result != 0 ) {
		$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] SSH command not successful.\nCommand: " . $ssh_command . "\nOutput: " . $output_string, $sendmail = false);
		$summary_result = false;
	} else {
		$debug->log($jconf['log_dir'], $myjobid . ".log", "[OK] SSH command successful.\nCommand: " . $ssh_command . "\nOutput: " . $output_string, $sendmail = false);
	}
}

// DB testing
$err = getSomethingFromDB();
if ( $err === false ) {
	$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] DB query was not successful.", $sendmail = false);
	$summary_result = false;
} else {
	$debug->log($jconf['log_dir'], $myjobid . ".log", "[OK] DB query result:\n" . print_r($err, true), $sendmail = false);
}

// Send test mail
$debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] Simple HTML mail send test.", $sendmail = false);
$mail_head  = "NODE: " . $app->config['node_sourceip'] . "<br/>";
$mail_head .= "SITE: " . $app->config['baseuri'] . "<br/>";
$title = "TEST. IGNORE. This is a test message from Videosquare";
$body  = $mail_head . "<br/>" . $title . "<br/><br/>Test message<br/>";
try {
	sendHTMLEmail_errorWrapper($title, $body);
	$debug->log($jconf['log_dir'], $myjobid . ".log", "[OK] Simple HTML mail sent to log mail addresses. CHECK MAIL.\n", $sendmail = false);
} catch (exception $err) {
	$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] Failed to send simple HTML e-mail. Error: " . print_r($err, true), $sendmail = false);
	$summary_result = false;
}

// Smarty test + mail send
$debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] Smarty test.", $sendmail = false);
try {
	$smarty = $app->bootstrap->getSmarty();
	$debug->log($jconf['log_dir'], $myjobid . ".log", "[OK] Got Smarty.", $sendmail = false);
} catch (exception $err) {
	$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] Failed to open Smarty.", $sendmail = false);
	$summary_result = false;
}

$organization = $app->bootstrap->getModel('organizations');
$organization->select(1);
$smarty->assign('organization', $organization->row);
$smarty->assign('filename', "1234.mp4");
$smarty->assign('language', "hu");
$smarty->assign('recid', 123);
$smarty->assign('supportemail', "support@videosqr.com");
$smarty->assign('domain', "videosquare.eu");

// Get e-mail subject line from localization
$debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] Localization get test.", $sendmail = false);
try {
	$localization = $app->bootstrap->getLocalization();
	$subject = $localization('recordings', 'email_conversion_done_subject', 'hu');
	$debug->log($jconf['log_dir'], $myjobid . ".log", "[OK] Got localization.", $sendmail = false);
	$subject = "TEST. IGNORE. This is a test smarty generated mail from Videosquare";
} catch (exception $err) {
	$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] Failed to get localization.", $sendmail = false);
	$summary_result = false;
}

// Get mail queue
$debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] Mailqueue test.", $sendmail = false);
try {
	$queue = $app->bootstrap->getMailqueue();
	$queue->instant = true;
	$debug->log($jconf['log_dir'], $myjobid . ".log", "[OK] Got mailqueue.", $sendmail = false);
} catch (exception $err) {
	$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] Failed to get mailqueue.", $sendmail = false);
	$summary_result = false;
}
// Fetch Smarty template
$debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] Smarty fetch test.", $sendmail = false);
try {
	$body = $smarty->fetch('Visitor/Recordings/Email/job_media_converter.tpl');
	$debug->log($jconf['log_dir'], $myjobid . ".log", "[OK] Smarty template fetched.", $sendmail = false);
} catch (exception $err) {
	$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] Failed to fetch Smarty template.", $sendmail = false);
	$summary_result = false;
}
// Send HTML e-mail
$debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] Send HTML mail with Smarty content.", $sendmail = false);
try {
	$queue->sendHTMLEmail("hiba@videosqr.com", $subject, $body);
	$debug->log($jconf['log_dir'], $myjobid . ".log", "[OK] Mail sent to hiba@videosqr.com. CHECK.", $sendmail = false);
} catch (exception $err) {
	$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] Failed to send HTML e-mail\n\n" . trim($body), $sendmail = false);
	$summary_result = false;
}

// ffmpegthumbnailer/PHP test
$gdtestfile = "";
if ( !file_exists($jconf['ffmpegthumbnailer']) ) {
	$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] ffmpegthumbnailer not found: " . $jconf['ffmpegthumbnailer'], $sendmail = false);
} else {
	$testfile = "andor_agnes.mp4";
	$outfile = "output.png";
	$command  = $jconf['nice_high'] . " " . $jconf['ffmpegthumbnailer'] . " -i " . $testfile . " -o " . $outfile . " -s0 -q8 -t 5";
	$debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] Executing ffmpegthumbnailer: " . $command, $sendmail = false);
	$output = runExternal($command);
	$output_string = $output['cmd_output'];
	$result = $output['code'];
	$debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] ffmpegthumbnailer return code = " . $result . "\nOutput: " . $output_string, $sendmail = false);
	if ( $result > 0 ) {
		$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] ffmpegthumbnailer error. CHECK.", $sendmail = false);
		$summary_result = false;
	} else {
		$debug->log($jconf['log_dir'], $myjobid . ".log", "[OK] ffmpegthumbnailer test finished.", $sendmail = false);
		$gdtestfile = $outfile;
	}
}

// Imagemagick test
$emptypic = "empty.png";
$command = "convert -size 640x480 xc:none " . $emptypic;
$debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] Executing Imagemagick: " . $command, $sendmail = false);
exec($command, $output, $result);
$output_string = implode("\n", $output);
if ( $result != 0 ) {
	$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] ImageMagick test failed.\nOutput: " . $output_string, $sendmail = false);
} else {
	$debug->log($jconf['log_dir'], $myjobid . ".log", "[OK] ImageMagick test finished.", $sendmail = false);
	if ( empty($gdtestfile) ) $gdtestfile = $emptypic;
}

// php5-gd test (resize)
$debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] Executing php5-gd test: resize " . $gdtestfile . " to 320x200.", $sendmail = false);
if ( !empty($gdtestfile) ) {
	try {
		\Springboard\Image::resizeAndCropImage($gdtestfile, 320, 200, 'top');
		$debug->log($jconf['log_dir'], $myjobid . ".log", "[OK] php5-gd test finished.", $sendmail = false);
	} catch (exception $err) {
		$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] php5-gd test failed.", $sendmail = false);
	}
} else {
	$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] No test pic for php5-gd.", $sendmail = false);
}

// Close DB connection if open
if ( is_resource($db->_connectionID) ) $db->close();

echo "Installation test is finished.\n";
if ( !$summary_result ) {
	echo "One or more error(s) occured.\n";
} else {
	echo "Tests seems OK.\n";
}
echo "Please check log for details: " . $logfile . "\n";

exit;

function getSomethingFromDB() {
global $jconf, $debug, $db, $myjobid;

	$query = "SELECT * FROM	organizations WHERE id >= 1 LIMIT 1";

	$debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] Executing SQL query: " . trim($query), $sendmail = false);

	try {
		$rs = $db->getArray($query);
	} catch (exception $err) {
		$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] SQL query failed." . trim($query), $sendmail = false);
		return false;
	}

	return $rs;
}

function checkDirectories($dirs2check) {
global $debug, $myjobid, $jconf;

	$retval = true;

	for ( $i = 0; $i < count($dirs2check); $i++ ) {
		if ( !is_writable($dirs2check[$i]) ) {
			$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] Directory " . $dirs2check[$i] . " is not writable. CHECK\n", $sendmail = false);
			$retval = false;
		} else {
			$debug->log($jconf['log_dir'], $myjobid . ".log", "[OK] Directory " . $dirs2check[$i] . " is writable.\n", $sendmail = false);
		}
	}

	return $retval;
}

?>
