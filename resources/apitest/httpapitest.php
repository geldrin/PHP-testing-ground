<?php

include('httpapi.php');

$api       = new Api('support@videosqr.com', 'FeketeLeves622');
$api->apiurl = 'https://dev.videosquare.eu/hu/api';

// debug (var_dump)
$api->debug = true;

/*
$filename = "/home/conv/temp/ffmpeg/andor_agnes.mp4";

$recording = $api->uploadRecording($filename, 'hun');

if ( $recording and isset( $recording['data']['id'] ) ) {
  
  $recordingid = $recording['data']['id'];
  $api->modifyRecording( $recordingid, array(
      'title' => 'API CS TESZT',
      'subtitle' => 'Subtitle is van',
    )
  );
  
  $api->uploadContent( $recordingid, $filename);

  // $api->addRecordingToChannel( $recordingid, 123 );
  // $api->removeRecordingFromChannel( $recordingid, 123 );
  
}

*/

?>