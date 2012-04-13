<?php

class Api {
  public $apiurl = 'http://dev.video.teleconnect.hu/hu/api';
  
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
  
  public function upload( $file, $language ) {
    
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
    
    $this->initCurl( $options );
    $json = curl_exec( $this->curl );
    $data = json_decode( $json, true );
    
    echo "\n\n\n-----UPLOAD-----\n";
    var_dump( $data, $options, $json, curl_error( $this->curl ) );
    echo "------------------------\n";
    
    return $data;
    
  }
  
  public function modify( $id, $values ) {
    
    if ( empty( $values ) or !is_array( $values ) )
      throw new Exception('Nothing to modify');
    
    // nem szamit hogy az url-ben vagy POST parameterekkent erkeznek a parameterek
    $parameters = array('id' => $id );
    $options    = array(
      CURLOPT_URL        => $this->getURL('controller', 'recordings', 'modifyrecording', $parameters ),
      CURLOPT_POST       => true,
      CURLOPT_POSTFIELDS => $values,
    );
    
    $this->initCurl( $options );
    $json = curl_exec( $this->curl );
    $data = json_decode( $json, true );
    
    echo "\n\n\n-----MODIFY-----\n";
    var_dump( $data, $options, $json, curl_error( $this->curl ) );
    echo "------------------------\n";
    
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
    
    $this->initCurl( $options );
    $json = curl_exec( $this->curl );
    $data = json_decode( $json, true );
    
    echo "\n\n\n-----UPLOADCONTENT-----\n";
    var_dump( $data, $options, $json, curl_error( $this->curl ) );
    echo "------------------------\n";
    
  }
  
}

$api       = new Api('info@dotsamazing.com', 'asdasd');
$recording = $api->upload('/home/sztanpet/teleconnect/resources/local/video.flv', 'hun');

if ( $recording and isset( $recording['data']['id'] ) ) {
  
  $recordingid = $recording['data']['id'];
  $api->modify( $recordingid, array(
      'title' => 'API CS TESZT',
      'subtitle' => 'Subtitle is van',
    )
  );
  
  $api->uploadContent( $recordingid, '/home/sztanpet/teleconnect/resources/local/video.flv');
  
}
