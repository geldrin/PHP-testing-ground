<?php
// Media conversion job v0 @ 2012/02/??

define('BASE_PATH',	realpath( __DIR__ . '/../..' ) . '/' );
define('PRODUCTION', false );
define('DEBUG', false );

include_once( BASE_PATH . 'libraries/Springboard/Application/Cli.php');

// Utils
include_once('job_utils_base.php');
include_once('job_utils_log.php');
include_once('job_utils_status.php');
include_once('job_utils_media.php');

// Init
$app = new Springboard\Application\Cli(BASE_PATH, PRODUCTION);

// Load jobs configuration file
$app->loadConfig('modules/Jobs/config_jobs.php');
$jconf = $app->config['config_jobs'];

// Send e-mail to user about successful conversion
$smarty = $app->bootstrap->getSmarty();
$organization = $app->bootstrap->getModel('organizations');
$organization->select( 1 );
$smarty->assign('organization', $organization->row );
$smarty->assign('filename', "a.mp3");
$smarty->assign('language', "hu");
$smarty->assign('recid', 1234);
$queue = $app->bootstrap->getMailqueue();
//$queue->embedImages = FALSE;
$queue->sendHTMLEmail("hiba@teleconnect.hu", "hoppa", $smarty->fetch('Visitor/Recordings/Email/job_media_converter.tpl') );

/*		$smarty = $app->bootstrap->getSmarty();
		$smarty->assign('filename', $recording['mastervideofilename']);
		$smarty->assign('language', $uploader_user['language']);
		$smarty->assign('recid', $recording['id']);
		if ( $uploader_user['language'] == "hu" ) {
			$subject = "Video konverzió kész";
		} else {
			$subject = "Video conversion ready";
		}
		if ( !empty($recording['mastervideofilename']) ) $subject .= ": " . $recording['mastervideofilename'];
		$queue = $app->bootstrap->getMailqueue();
		$queue->embedImages = FALSE;
		$queue->sendHTMLEmail($uploader_user['email'], $subject, $smarty->fetch('Visitor/Recordings/Email/job_media_converter.tpl') ); */


//$pwd = base64_encode("hakapeci");
//echo $pwd . "\n";

exit;

?>
