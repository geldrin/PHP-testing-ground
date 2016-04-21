<?php

class Api {
  public $apiurl = 'https://videosquare.eu/hu/api';
  public $debug = false;
  
  protected $curl;
  protected $email;
  protected $password;
  protected $chunksize = 10485760; // 10mb
  protected $options = array(
    CURLOPT_FAILONERROR    => true,
    CURLOPT_HEADER         => false,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => false,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_SSL_VERIFYHOST => 2,
    CURLOPT_CONNECTTIMEOUT => 1,
    CURLOPT_USERAGENT      => 'teleconnect api client',
  );
  
  
  public function __construct( $email, $password ) {
    $this->email    = $email;
    $this->password = $password;

    if ( !$this->checkUserPassword() )
      throw new \Exception("User/password invalid");
  }

  public function setDomain( $domain ) {
    $this->apiurl = "https://$domain/hu/api";
  }

  protected function initCurl( $options ) {
    
    if ( $this->curl )
      curl_close( $this->curl );
    
    $this->curl = curl_init();
    
    $opts = $this->options;
    foreach( $options as $key => $value )
      $opts[ $key ] = $value;
    
    if ( $this->debug ) {
      
      $defines = get_defined_constants();
      $options = array();
      foreach( $defines as $key => $value ) {
        
        if ( substr( $key, 0, 8 ) != 'CURLOPT_' )
          continue;
        
        if ( isset( $opts[ $value ] ) )
          $options[ $key ] = $opts[ $value ];
        
      }
      
      var_dump( $options );
      
    }
    
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

  private function isUTF8( $string ) {
    // http://www.w3.org/International/questions/qa-forms-utf-8.en.php
    return preg_match('%^(?:
            [\x09\x0A\x0D\x20-\x7E]            # ASCII
          | [\xC2-\xDF][\x80-\xBF]             # non-overlong 2-byte
          | \xE0[\xA0-\xBF][\x80-\xBF]         # excluding overlongs
          | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}  # straight 3-byte
          | \xED[\x80-\x9F][\x80-\xBF]         # excluding surrogates
          | \xF0[\x90-\xBF][\x80-\xBF]{2}      # planes 1-3
          | [\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
          | \xF4[\x80-\x8F][\x80-\xBF]{2}      # plane 16
        )*$%xs', $string );
  }

  protected function checkArrayValidUTF8( &$data ) {
    foreach( $data as $key => $value ) {
      if ( is_string( $value ) and !$this->isUTF8( $value ) )
        throw new \Exception("The value of array member $key is not valid UTF8: $value\n");

      if ( is_array( $value ) and !empty( $value ) )
        $this->checkArrayValidUTF8( $data[ $key ] );

    }
  }

  public function modifyRecording( $id, $values ) {
    
    if ( empty( $values ) or !is_array( $values ) )
      throw new \Exception('Nothing to modify');

    $this->checkArrayValidUTF8( $values );

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
      CURLOPT_POSTFIELDS => Array(),
    );
    
    return $this->executeCall( $options, strtoupper( $action ) );
    
  }
  
  private function executeCall( $options, $action ) {

    $this->initCurl( $options );
    $json = curl_exec( $this->curl );

    if ( $json === false )

      throw new \Exception(
        'videosquare HTTP API call CURL error: ' . curl_error( $this->curl )
      );

    else {

      $data = json_decode( $json, true );

      if ( $this->debug ) {
        echo "\n\n\n-----" . $action . "-----\n";
        var_dump( $data, $options, $json, curl_error( $this->curl ) );
        echo "------------------------\n";
      }

      return $data;

    }

  }
  
  public function uploadContent( $id, $file, $userid = null ) {
    return $this->uploadRecording( $file, '', $userid, 1, $id );
  }
  
  public function uploadRecording( $file, $language, $userid = 0, $iscontent = 0, $recordingid = 0 ) {
    
    if ( !isset( $file ) or !is_readable( $file ) )
      throw new \Exception("Unreadable file: " . $file );
    
    $filename     = basename( $file );
    $size         = filesize( $file );
    if ( $size < 0 )
      throw new \Exception("Filesize is negative, your PHP cannot handle large files");

    $chunkcount   = 1;
    $currentchunk = 0;
    $resumeinfo   = $this->getResumeInfo(
      $filename, $size, $iscontent, $userid
    );
    
    if ( $resumeinfo and $resumeinfo['status'] == 'success' )
      $currentchunk = $resumeinfo['startfromchunk'];
    
    if ( $size > $this->chunksize )
      $chunkcount = ceil( $size / $this->chunksize );
    
    if ( $currentchunk >= $chunkcount )
      $currentchunk = 0;
    
    while( $currentchunk < $chunkcount ) {
      
      $tmpfile   = $this->getChunk( $file, $currentchunk );
      $chunkinfo = $this->uploadChunk( $tmpfile, array(
          'name'         => $filename,
          'chunks'       => $chunkcount,
          'chunk'        => $currentchunk,
          'iscontent'    => $iscontent,
          'userid'       => $userid,
          'size'         => $size,
          'textlanguage' => $language,
          'id'           => $recordingid,
        )
      );
      
      unlink( $tmpfile );
      if ( !$chunkinfo or !isset( $chunkinfo['status'] ) or $chunkinfo['status'] == 'error' )
        throw new \Exception(
          "Failed uploading chunk($tmpfile) #$currentchunk out of $chunkcount: " .
          var_export( $chunkinfo, true )
        );
      
      $currentchunk++;
      
    }
    
    return array('result' => 'OK', 'data' => array( 'id' => $chunkinfo['id'] ) );
    
  }
  
  private function getResumeInfo( $filename, $size, $iscontent, $userid ) {
    
    $method     = 'checkfileresume';
    $parameters = array(
      'name'      => $filename,
      'size'      => (string)$size,
      'iscontent' => $iscontent,
      'userid'    => $userid,
    );
    
    if ( $parameters['userid'] )
      $method  .= 'asuser';
    
    $options    = array(
      CURLOPT_URL        => $this->getURL('controller', 'recordings', $method, $parameters ),
    );
    
    return $this->executeCall( $options, "RESUMEINFO" );
    
  }
  
  private function getChunk( $file, $currentchunk ) {
    
    $tmpfile    = __DIR__ . '/.currentchunk';
    if (
         ( file_exists( $tmpfile ) and !is_writable( $tmpfile ) ) or
         !is_writable( dirname( $tmpfile ) )
       )
      throw new \Exception("Temporary file: $tmpfile is not writable!");
      
    $tmphandle  = fopen( $tmpfile , 'wb' ); // open for writing only and truncate to zero
    $filehandle = fopen( $file, 'rb' );
    $dataread   = 0;
    $offset     = $currentchunk * $this->chunksize;
    
    fseek( $filehandle, $offset );
    while( $dataread < $this->chunksize and !feof( $filehandle ) ) {
      
      $data     = fread( $filehandle, 8192 );
      $dataread += fwrite( $tmphandle, $data );
      
    }
    
    fclose( $tmphandle );
    fclose( $filehandle );
    return $tmpfile;
    
  }
  
  private function uploadChunk( $file, $parameters ) {
    
    $method     = 'uploadchunk';
    
    if ( $parameters['userid'] )
      $method  .= 'asuser';
    
    $options    = array(
      CURLOPT_URL        => $this->getURL('controller', 'recordings', $method, $parameters ),
      CURLOPT_POST       => true,
      CURLOPT_POSTFIELDS => array(
        'file' => '@' . $file,
      ),
    );
    
    return $this->executeCall( $options, "UPLOAD" );
    
  }
  
  public function setUserField( $userid, $field, $value ) {
    
    $parameters = array(
      'userid' => $userid,
      'field'  => $field,
      'value'  => $value,
    );
    
    $options    = array(
      CURLOPT_URL => $this->getURL('controller', 'users', 'setuserfield', $parameters ),
    );
    
    return $this->executeCall( $options, "SETUSERFIELD" );
    
  }
  
  private function checkUserPassword() {
    $parameters = array(
      'email'    => $this->email,
      'password' => $this->password,
    );
    $options    = array(
      CURLOPT_URL => $this->getURL('controller', 'users', 'authenticate', $parameters ),
    );

    $result = $this->executeCall( $options, "USERAUTHENTICATE" );
    if ( !$result or !isset( $result['data'] ) )
      return false;

    return $result['data'] !== false;
  }
}
