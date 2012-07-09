<?php

include("SOAP/Client.php");

set_time_limit(0);

$server = "tcs.streamnet.hu";
$user = "admin";
$password = "BoRoKaBoGYo1980";

$wsdl = "http://" . $server . "/tcs/Helium.wsdl";
$api_url = "http://" . $server . "/tcs/SoapServer.php";

$soapOptions = array(
    'location'			=> $api_url,
    'authentication'		=> SOAP_AUTHENTICATION_DIGEST,
    'login'			=> $user,
    'password'			=> $password,
    'connection_timeout'	=> 10
);

$client = new SoapClient($wsdl, $soapOptions);

// 1. Reserve a ConferenceID

echo "RESERVE ID\n";

// Parameters:
//  owner (username of the owner - string)
//  password (password for the conference - string). Set password as an empty string for no conference password, this field is limited to 20 characters.
//  startDateTime (start date of the recording using GNU date formats - string). Setting the startDateTime to 0 means that the call will begin immediately.
//  duration (duration of call in seconds - integer). Setting a 0 duration will make the length of the call unlimited.
//  title (the title that will appear in the Content Library - string)
//  groupId (the GUID of the conference’s group, if it is recurring - string). The groupId field needs to be either unset, empty string, or a well formed GUID.
//  isRecurring (indicates whether the conference is recurring - bool)

$conf = array(
    'owner'		=> 'admin',
    'password'		=> '',
    'startDateTime'	=> 0,
    'duration'		=> 0,
    'title'		=> 'Ez a címem',
    'groupID'		=> '',
    'isRecurring'	=> false
);

$result = $client->RequestConferenceID($conf);

$conf_id = $result->RequestConferenceIDResult;

echo "\tconferenceID: " . $conf_id . "\n";

unset($conf);

// 2. Dial

$conf_dial = '81999@teleconnect.hu';

echo "DIAL: " . $conf_id . " (h323: " . $conf_dial . ")\n";

// Parameters:
//  Number (the number to dial - string)
//  Bitrate (the desired bandwidth - string). Must be: 64, 128, 192, 256, 384, 512, 768, 1024, 1280, 1536, 1920 and 2048 kbps (as well as 2560, 3072 and 4000 kbps for Content Servers equipped with the Premium Resolution option).
//  ConferenceID (ConferenceID to be used for this call - string)
//  Alias (alias to use – specifies call settings - string)
//  CallType (protocol for the call – optional (“sip”, “h323”) – string). If CallType is equal to sip then the address to call will be prefixed with sip:, otherwise H.323 is assumed.
//  SetMetadata (inherit conference metadata from the alias – boolean)
//  PIN (MCU conference PIN if required – string)

$conf = array(
    'ConferenceID'	=> $conf_id,
    'Number'		=> $conf_dial,
    'CallType'		=> 'h323',		// or: 'sip'
    'Alias'		=> '84100',
    'Bitrate'		=> 2048,
    'SetMetadata'	=> false
);

$result = $client->Dial($conf);

//var_dump($result);

$err = $result->DialResult->Error;
$err_code = $result->DialResult->ErrorCode;

unset($conf);

sleep(20);

// 3. Get recording information (live)

echo "GET INFO: " . $conf_id . "\n";

$conf = array(
    'ConferenceID'	=> $conf_id
);

$result = $client->GetConference($conf);

var_dump($result);

$HasWatchableMovie = $result->GetConferenceResult->HasWatchableMovie;

if ( $HasWatchableMovie ) {

    $WatchableMovie = $result->GetConferenceResult->WatchableMovies->ArrayOfWatchableMovie->WatchableMovie;

    $conf_live_url = $WatchableMovie->MainURL;

    echo "Live RTMP URL: " . $conf_live_url . "\n";

}

exit;

sleep(10);

// 4. Disconnect live recording

echo "DISCONNECT: " . $conf_id . "\n";

$conf = array(
    'ConferenceID'	=> $conf_id
);

$result = $client->DisconnectCall($conf);

//var_dump($result);

$err = $result->DisconnectCallResult->Error;
$err_code = $result->DisconnectCallResult->ErrorCode;

unset($conf);

sleep(30);

// 5. Get recording information (on demand)

$conf = array(
    'ConferenceID'	=> $conf_id
);

$result = $client->GetConference($conf);

var_dump($result);

$HasWatchableMovie = $result->GetConferenceResult->HasWatchableMovie;

if ( $HasWatchableMovie ) {

    $WatchableMovie = $result->GetConferenceResult->WatchableMovies->ArrayOfWatchableMovie->WatchableMovie;

    $num = count($WatchableMovie);

    if ( $num <= 1 ) {
	$conf_vod_url = $WatchableMovie->MainURL;
    } else {
	for ( $i = 0; $i < $num; $i++) {
	    if ( $WatchableMovie[$i]->Format == "Flash" ) {
		$conf_vod_url = $WatchableMovie[$i]->MainURL;
		break;
	    }
	}
    }

    echo "VoD Flash URL: " . $conf_vod_url . "\n";
}

exit;

// MGMT: GetSystemHealth, GetCallCapacity, GetStatus, GetConfiguration
// Conf: GetConference(s), GetConferenceCount, GetConferenceThumbnails, 
// Call: RequestConferenceID

/*
SOAP based API.

Videosquare integration:
1. TCS API admin auth
2. RequestConferenceID() -> ConferenceID. Store ConferenceID in database associated to Vsq user.
3. Init call on ConferenceID: Dial() [h323/sip, alias, bw, etc.]
    Note: TCS records onto a samba share directly seen by Wowza
4. GetConferenceID(ConferenceID): live recording information is returned.
    DATA:
	RTMP live URL of Wowza server: rtmp://stream.teleconnect.hu/tcs/mp4:134156609700-59704531
	Other: width, height, status
    ACTION: status := live/tcs, live streaming is now visible on Vsq
    TODO: Test playback with Vsq flash player. Content is separate stream? (no)
5. DisconnectCall(ConferenceID): H.323/SIP call is disconnected, status := "onstorage/tcs"
6. GetConferenceID(ConferenceID):
    TODO: Wowza on demand URL (to be tested)? Playback with Vsq flash player?
7. Recording remains where it is, or moved by job to Vsq file repo. Move only after edit is executed?

*/

exit;

?>
