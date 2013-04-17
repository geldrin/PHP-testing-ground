<?php

$curl = curl_init();

$action = "START";
$live_stream_id = "433766";
//v: 730673
//c: 433766

$wowza_app = "vsqlive";
$recording_format = 2; 		// 1 = FLV, 2 = MP4
$recording_path = "";

$wowza_username = "admin";
$wowza_passwd = "jafo%4576";

switch ( $action ) {
	case "START":
		$wowza_action = "startRecording";
		break;
	case "STOP":
		$wowza_action = "stopRecording";
		break;
	default:
		$wowza_action = "stopRecording";
		break;
}

$url_record  = "http://stream.videosquare.eu:8086/livestreamrecord?app=" . $wowza_app;
$url_record .= "&streamname=" . $live_stream_id;
$url_record .= "&action=" . $wowza_action;

if ( $action == "START" ) {
	$url_record .= "&format=2";
//	$url_record .= "&output=" . $recording_path;
}

echo $url_record . "\n";

//curl_setopt($curl, CURLOPT_URL, "http://stream.videosquare.eu:8086/streammanager/index.html"); 
curl_setopt($curl, CURLOPT_URL, $url_record); 
curl_setopt($curl, CURLOPT_PORT, 8086); 
curl_setopt($curl, CURLOPT_VERBOSE, 0); 
curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); 
curl_setopt($curl, CURLOPT_USERPWD, $wowza_username . ":" . $wowza_passwd);
curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
//curl_setopt($curl, CURLOPT_POST, 1); 
//curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

$data = curl_exec($curl); 
if( curl_errno($curl) ){ 
	echo "CURL ERROR: " . curl_error($curl);
	exit -1;
}

// Check if authentication failed
$header = curl_getinfo($curl);
if ( $header['http_code'] == 401 ) {
	echo "ERROR: HTTP 401. Cannot authenticate to Wowza.\n";
	exit -1;
}

//var_dump($data);

if ( preg_match('/.*Stream Recorder Not Found: ' . $live_stream_id . '.*/', $data) ) {
	echo "ERROR: Cannot stop live stream recording for " . $live_stream_id . ". Wrong ID or recording already stopped.\n";
	exit -1;
}

if ( preg_match('/.*startRecording ' . $live_stream_id . '.*/', $data) ) {
	echo "OK: Stream " . $live_stream_id . " started.\n";
	exit;
}

if ( preg_match('/.*stopRecording ' . $live_stream_id . '.*/', $data) ) {
	echo "OK: Stream " . $live_stream_id . " stopped.\n";
	exit;
}

$info = curl_getinfo($curl); 
echo "URL: " . $info['url'] . "\n"; 
echo "Total time: " . $info['total_time'] . " seconds.\n"; 

curl_close($curl); 

//var_dump($header);


exit;

?>
