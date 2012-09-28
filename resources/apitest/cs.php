<?php

class Api {
  public $apiurl = 'http://dev.videosquare.eu/hu/api';
  
  protected $curl;
  protected $email;
  protected $password;
  protected $options = array(
    CURLOPT_FAILONERROR    => true,
    CURLOPT_HEADER         => false,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => false,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => 0,
    CURLOPT_CONNECTTIMEOUT => 1,
    CURLOPT_USERAGENT      => 'teleconnect api client',
  );
  
  
  public function __construct( $email, $password ) {
    $this->email    = $email;
    $this->password = $password;
    
  }
  
  protected function initCurl( $options ) {
    
    if ( $this->curl )
      curl_close( $this->curl );
    
    $this->curl = curl_init();
    
    $opts = $this->options;
    foreach( $options as $key => $value )
      $opts[ $key ] = $value;
    
    curl_setopt_array( $this->curl, $opts );
    
  }
  
  protected function getURL( $layer, $module, $method, $parameters = array() ) {
    
    $params = array(
      'format'   => 'json',
      'email'    => $this->email,
      'password' => $this->password,
      'layer'    => $layer,
      'method'   => $method,
      'module'   => $module,
    );
    
    return $this->apiurl . '?' . http_build_query( array_merge( $params, $parameters ) );
    
  }
  
  public function uploadRecording( $file, $language ) {
    
    if ( !is_readable( $file ) )
      throw new Exception('Unreadable file: ' . $file );
    
    $parameters = array('language' => $language );
    $options    = array(
      CURLOPT_URL        => $this->getURL('controller', 'recordings', 'apiupload', $parameters ),
      CURLOPT_POST       => true,
      CURLOPT_POSTFIELDS => array(
        'file' => '@' . $file,
      ),
    );
    
    return $this->executeCall( $options, "UPLOAD" );
    
  }
  
  public function modifyRecording( $id, $values ) {
    
    if ( empty( $values ) or !is_array( $values ) )
      throw new Exception('Nothing to modify');
    
    // nem szamit hogy az url-ben vagy POST parameterekkent erkeznek a parameterek
    $parameters = array('id' => $id );
    $options    = array(
      CURLOPT_URL        => $this->getURL('controller', 'recordings', 'modifyrecording', $parameters ),
      CURLOPT_POST       => true,
      CURLOPT_POSTFIELDS => $values,
    );
    
    return $this->executeCall( $options, "MODIFY" );
    
  }
  
  public function addRecordingToChannel( $recordingid, $channelid ) {

    return $this->recordingChannelOperation( 'addtochannel', $recordingid, $channelid );

  }

  public function removeRecordingFromChannel( $recordingid, $channelid ) {

    return $this->recordingChannelOperation( 'removefromchannel', $recordingid, $channelid );  

  }

  private function recordingChannelOperation( $action, $recordingid, $channelid ) {

    $parameters = array(
      'recordingid' => $recordingid,
      'channelid'   => $channelid
    );
    $options    = array(
      CURLOPT_URL        => $this->getURL('controller', 'recordings', $action, $parameters ),
      CURLOPT_POST       => true,
      CURLOPT_POSTFIELDS => $values,
    );
    
    return $this->executeCall( $options, strtoupper( $action ) );
    
  }
  
  public function uploadContent( $id, $file ) {
    
    if ( !is_readable( $file ) )
      throw new Exception('Unreadable file: ' . $file );
    
    $parameters = array('id' => $id );
    $options    = array(
      CURLOPT_URL        => $this->getURL('controller', 'recordings', 'apiuploadcontent', $parameters ),
      CURLOPT_POST       => true,
      CURLOPT_POSTFIELDS => array(
        'file' => '@' . $file
      ),
    );

    return $this->executeCall( $options, "UPLOAD" );
    
  }

  private function executeCall( $options, $action ) {

    $this->initCurl( $options );
    $json = curl_exec( $this->curl );
    $data = json_decode( $json, true );
    
    echo "\n\n\n-----" . $action . "-----\n";
    var_dump( $data, $options, $json, curl_error( $this->curl ) );
    echo "------------------------\n";

    return $data;
    
  }
  
}

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
