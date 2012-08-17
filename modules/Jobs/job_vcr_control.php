<?php
// VCR control job 2012/08/17

define('BASE_PATH',	realpath( __DIR__ . '/../..' ) . '/' );
define('PRODUCTION', false );
define('DEBUG', false );

include("SOAP/Client.php");

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
$myjobid = $jconf['jobid_vcr_control'];

// SOAP related URLs
$vcr_wsdl = "http://" . $jconf['vcr_server'] . "/tcs/Helium.wsdl";
$vcr_api_url = "http://" . $jconf['vcr_server'] . "/tcs/SoapServer.php";

// Log related init
$debug = Springboard\Debug::getInstance();
$debug->log($jconf['log_dir'], $myjobid . ".log", "VCR control job started", $sendmail = false);

// Check operating system - exit if Windows
if ( iswindows() ) {
	echo "ERROR: Non-Windows process started on Windows platform\n";
	exit;
}

// Start an infinite loop - exit if any STOP file appears
while( !is_file( $app->config['datapath'] . 'jobs/job_vcr_control.stop' ) and !is_file( $app->config['datapath'] . 'jobs/all.stop' ) ) {

	clearstatcache();

    while ( 1 ) {

		$app->watchdog();
	
		$db_close = FALSE;
		$sleep_length = $jconf['sleep_vcr'];

		// Establish database connection
		try {
			$db = $app->bootstrap->getAdoDB();
		} catch (exception $err) {
			// Send mail alert, sleep for 15 minutes
			$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] No connection to DB (getAdoDB() failed). Error message:\n" . $err, $sendmail = true);
			// Sleep 15 mins then resume
			$sleep_length = 15 * 60;
			break;
		}

		// Initialize log for closing message and total duration timer
		$global_log = "";
		$total_duration = time();

		$vcr = array();
		$vcr_user = array();

		// Query next job - exit if none
		if ( !query_vcrnew($vcr, $vcr_user) ) break;

		// Start log entry
		log_recording_conversion($vcr['id'], $myjobid, $jconf['dbstatus_init'], "START Videoconference recording: livefeed = " . $vcr['livefeedid'], "-", "-", 0, FALSE);
		$global_log .= "Live feed: " . $vcr['feed_name'] . " (ID = " . $vcr['feed_id'] . ")\n";
		$global_log .= "Stream: " . $vcr['name'] . " (ID = " . $vcr['id'] . ")\n";
		$global_log .= "User: " . $vcr_user['nickname'] . " (" . $vcr_user['email'] . ")\n";
		$global_log .= "Recording link: " . $vcr['reclink_name'] . " (ID = " . $vcr['reclink_id'] . ")\n";
		$global_log .= " Call: " . $vcr['calltype'] . ":" . $vcr['number'] . " @ " . $vcr['bitrate'] . "KBps\n";
		$global_log .= " Profile: " . $vcr['alias'] . "\n\n";

echo $global_log . "\n";

// ------------------------------------------------------------------------------------

	$soapOptions = array(
		'location'                  => $vcr_api_url,
		'authentication'            => SOAP_AUTHENTICATION_DIGEST,
		'login'                     => $jconf['vcr_user'],
		'password'                  => $jconf['vcr_password'],
		'connection_timeout'        => 10
	);

	$soap_rs = new SoapClient($vcr_wsdl, $soapOptions);

//$result = $soap_rs->GetStatus();
//var_dump($result);

	$vcr['conf_id'] = tcs_reserve_conf_id($vcr);

var_dump($vcr);

tcs_dial($vcr);

exit;

		break;
	}	// End of while(1)

	// Close DB connection if open
	if ( $db_close ) {
		$db->close();
	}

	$app->watchdog();

	sleep( $sleep_length );
	
}	// End of outer while

exit;


// *************************************************************************
// *						function query_nextjob()					   *
// *************************************************************************
// Description: queries next job from database recording_elements table
// INPUTS:
//	- AdoDB DB link in $db global variable
// OUTPUTS:
//	- Boolean:
//	  o FALSE: no pending job for conversion
//	  o TRUE: job is available for conversion
//	- $recording: recording_element DB record returned in global $recording variable
function query_vcrnew(&$vcr, &$vcr_user) {
global $jconf, $db;

	$query = "
		SELECT
			a.id,
			a.livefeedid,
			a.name,
			a.status,
			a.recordinglinkid,
			b.id as reclink_id,
			b.name as reclink_name,
			b.organizationid,
			b.calltype,
			b.number,
			b.password,
			b.bitrate,
			b.alias,
			b.status as reclink_status,
			c.id as feed_id,
			c.userid,
			c.channelid,
			c.name as feed_name
		FROM
			livefeed_streams as a,
			recording_links as b,
			livefeeds as c
		WHERE
			a.status = '" . $jconf['dbstatus_vcr_start'] . "' AND
			a.recordinglinkid = b.id AND
			b.disabled = 0 AND
			b.status = '" . $jconf['dbstatus_vcr_ready'] . "' AND
			a.livefeedid = c.id
		ORDER BY
			id
		LIMIT 1";

	try {
		$rs = $db->Execute($query);
	} catch (exception $err) {
		log_recording_conversion(0, $jconf['jobid_vcr_control'], $jconf['dbstatus_init'], "[ERROR] Cannot query next VCR job. SQL query failed.", trim($query), $err, 0, TRUE);
		return FALSE;
	}

	// Check if pending job exsits
	if ( $rs->RecordCount() < 1 ) {
		return FALSE;
	}

	$vcr = $rs->fields;

	$query = "
		SELECT
			id,
			nickname,
			email,
			language,
			organizationid
		FROM
			users
		WHERE
			id = " . $vcr['userid'];

	try {
		$rs2 = $db->Execute($query);
	} catch (exception $err) {
		log_recording_conversion(0, $jconf['jobid_vcr_control'], $jconf['dbstatus_init'], "[ERROR] Cannot query user to VCR. SQL query failed.", trim($query), $err, 0, TRUE);
		return FALSE;
	}

	// Check if user exsits to media
	if ( $rs2->RecordCount() < 1 ) {
		return FALSE;
	}

	$vcr_user = $rs2->fields;

	return TRUE;
}

function tcs_reserve_conf_id($vcr) {
global $soap_rs;

// Parameters:
//  password (password for the conference - string). Set password as an empty string for no conference password, this field is limited to 20 characters.
//  startDateTime (start date of the recording using GNU date formats - string). Setting the startDateTime to 0 means that the call will begin immediately.
//  duration (duration of call in seconds - integer). Setting a 0 duration will make the length of the call unlimited.
//  title (the title that will appear in the Content Library - string)
//  groupId (the GUID of the conference’s group, if it is recurring - string). The groupId field needs to be either unset, empty string, or a well formed GUID.
//  isRecurring (indicates whether the conference is recurring - bool)

	$conf = array(
		'owner'				=> 'admin',
		'password'			=> $vcr['password'],
		'startDateTime'		=> 0,
		'duration'			=> 0,
		'title'				=> $vcr['feed_name'],
		'groupID'			=> '',
		'isRecurring'		=> false
    );

    $result = $soap_rs->RequestConferenceID($conf);
    $conf_id = $result->RequestConferenceIDResult;

var_dump($result);

    return $conf_id;
}

function tcs_dial($vcr) {
global $soap_rs;

// Parameters:
//  Number (the number to dial - string)
//  Bitrate (the desired bandwidth - string). Must be: 64, 128, 192, 256, 384, 512, 768, 1024, 1280, 1536, 1920 and 2048 kbps (as well as 2560, 3072 and 4000 kbps for Content Servers equipped with the Premium Resolution option).
//  ConferenceID (ConferenceID to be used for this call - string)
//  Alias (alias to use – specifies call settings - string)
//  CallType (protocol for the call – optional (“sip”, “h323”) – string). If CallType is equal to sip then the address to call will be prefixed with sip:, otherwise H.323 is assumed.
//  SetMetadata (inherit conference metadata from the alias – boolean)
//  PIN (MCU conference PIN if required – string)

	$conf = array(
		'ConferenceID'		=> $vcr['conf_id'],
		'Number'			=> $vcr['number'],
		'CallType'			=> $vcr['calltype'],
		'Alias'				=> $vcr['alias'],
		'Bitrate'			=> $vcr['bitrate'],
		'SetMetadata'		=> false
	);

var_dump($conf);



	$result = $soap_rs->Dial($conf);

	var_dump($result);

	$err = $result->DialResult->Error;
	$err_code = $result->DialResult->ErrorCode;

	return TRUE;
}

?>
