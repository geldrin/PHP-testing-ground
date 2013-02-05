<?php

include('httpapi.php');

$api       = new Api('info@dotsamazing.com', 'asdasd');
$api->apiurl = 'http://teleconnect.home.sztanpet.net/hu/api';
// ha kell a var_dump kijelzes, akkor ezzel bekapcsolhato:
 $api->debug = true;

$recording = $api->uploadRecording('/home/sztanpet/teleconnect/resources/local/big.mkv', 'hun');

if ( $recording and isset( $recording['data']['id'] ) ) {
  
  $recordingid = $recording['data']['id'];
  $api->modifyRecording( $recordingid, array(
      'title' => 'API CS TESZT',
      'subtitle' => 'Subtitle is van',
    )
  );
  
  $api->uploadContent( $recordingid, '/home/sztanpet/teleconnect/resources/local/big.mkv');

  // $api->addRecordingToChannel( $recordingid, 123 );
  // $api->removeRecordingFromChannel( $recordingid, 123 );
  
}
