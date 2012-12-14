<?php

// Generate given number of Videosquare users with random username, password and validated status

define('BASE_PATH',	realpath( __DIR__ . '/../..' ) . '/' );
define('PRODUCTION', false );
define('DEBUG', false );

include_once( BASE_PATH . 'libraries/Springboard/Application/Cli.php');

set_time_limit(0);

// Init
$app = new Springboard\Application\Cli(BASE_PATH, PRODUCTION);

$iscommit = FALSE;

$recordings = array();

// Establish database connection
try {
	$db = $app->bootstrap->getAdoDB();
} catch (exception $err) {
	// Send mail alert, sleep for 15 minutes
	echo "[ERROR] No connection to DB (getAdoDB() failed). Error message:\n" . $err . "\n";
	exit -1;
}

$query = "
	SELECT
		id,
		status,
		masterstatus,
		contentstatus,
		contentmasterstatus,
		mastervideofilename,
		mastervideoextension,
		mastermediatype,
		mastervideostreamselected,
		mastervideoisinterlaced,
		mastervideocontainerformat,
		mastervideofps,
		masterlength,
		mastervideocodec,
		mastervideores,
		mastervideodar,
		mastervideobitrate,
		masteraudiostreamselected,
		masteraudiocodec,
		masteraudiochannels,
		masteraudioquality,
		masteraudiofreq,
		masteraudiobitrate,
		masteraudiobitratemode,
		hascontentvideo,
		contentmastervideoisinterlaced,
		contentmastervideofilename,
		contentmastervideoextension,
		contentmastermediatype,
		contentmastervideostreamselected,
		contentmastervideocontainerformat,
		contentmasterlength,
		contentmastervideocodec,
		contentmastervideores,
		contentmastervideodar,
		contentmastervideofps,
		contentmastervideobitrate,
		contentmasteraudiostreamselected,
		contentmasteraudiocodec,
		contentmasteraudiobitratemode,
		contentmasteraudiochannels,
		contentmasteraudioquality,
		contentmasteraudiofreq,
		contentmasteraudiobitrate,
		videoreslq,
		videoreshq,
		mobilevideoreslq,
		mobilevideoreshq,
		contentvideoreslq,
		contentvideoreshq
	FROM
		recordings
	WHERE
		id < 66 AND	( ( masterstatus <> 'markedfordeletion' ) OR ( contentmasterstatus <> 'markedfordeletion' ) )
	ORDER BY
		id
";

//echo $query . "\n";

try {
	$recordings = $db->Execute($query);
} catch (exception $err) {
	echo "[ERROR] SQL query failed.\n", trim($query), $err . "\n";
	exit -1;
}

// User exist in DB, regenerate
if ( $recordings->RecordCount() < 1 ) {
	echo "ERROR: cannot find recordings.\n";
	exit -1;
}

while ( !$recordings->EOF ) {

	$rec = array();
	$rec = $recordings->fields;

	$recording_path = $app->config['recordingpath'] . ( $rec['id'] % 1000 ) . "/" . $rec['id'] . "/master/" . $rec['id'] . "_video." . $rec['mastervideoextension'];

	if ( !file_exists($recording_path) ) {
		echo "ERROR: cannot find file " . $recording_path . "\n";
	}

	if ( !empty($rec['contentmastervideofilename']) ) {
		$content_path = $app->config['recordingpath'] . ( $rec['id'] % 1000 ) . "/" . $rec['id'] . "/master/" . $rec['id'] . "_content." . $rec['contentmastervideoextension'];

		if ( !file_exists($content_path) ) {
			echo "ERROR: cannot find file " . $content_path . "\n";
		}

	}

var_dump($rec);

echo $recording_path . "\n";
echo $content_path . "\n";

	$recordings->MoveNext();
}


?>
