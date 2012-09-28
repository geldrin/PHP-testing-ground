<?php

include('httpapi.php');

$api       = new Api('info@dotsamazing.com', 'asdasd');
$recording = $api->uploadRecording('/home/sztanpet/teleconnect/resources/local/video.flv', 'hun');

if ( $recording and isset( $recording['data']['id'] ) ) {
  
  $recordingid = $recording['data']['id'];
  $api->modifyRecording( $recordingid, array(
      'title' => 'API CS TESZT',
      'subtitle' => 'Subtitle is van',
    )
  );
  
  $api->uploadContent( $recordingid, '/home/sztanpet/teleconnect/resources/local/video.flv');

  // $api->addRecordingToChannel( $recordingid, 123 );
  // $api->removeRecordingFromChannel( $recordingid, 123 );
  
}
