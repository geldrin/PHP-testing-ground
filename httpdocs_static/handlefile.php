<?php

define('PATH_PREFIX',  '/srv/storage/httpdocs/');
//define('PATH_PREFIX',  '/home/sztanpet/teleconnect/httpdocs_static/files/');

// ------------------------------------------------------------
if (
     !isset( $_GET['file'] ) or
     !strlen( $_GET['file'] ) or
     ( strpos( $_GET['file'], '..' ) !== false )
   )
  die();

define( 'DEBUG', substr( $_GET['file'], -3 ) == '---' );
if ( DEBUG ) {
  $_GET['file'] = substr( $_GET['file'], 0, -3 );
  header("Content-type: text/html");
}

$parts = explode( '/', $_GET['file'], 2 );
if ( DEBUG ) {
  echo "<pre>";
  var_dump( $parts, PATH_PREFIX . $_GET['file'] );
  var_dump( file_exists( PATH_PREFIX . $_GET['file'] ), is_file( PATH_PREFIX . $_GET['file'] ) );
  echo "</pre>";
}

// slideok, contributor kepek, recording indexkepek, attached_documents
if (
     file_exists( PATH_PREFIX . $_GET['file'] ) &&
     is_file( PATH_PREFIX . $_GET['file'] )
   ) {

  switch ( $parts[0] ) {

    case 'contributors':
      // nem szukseges az ellenorzes
      exitWithContentHeaders( $_GET['file'] );
      break;

    case 'recordings':
      
      if ( preg_match('/^\d+\/(\d+)\/.*$/', $parts[1], $results ) ) {
        
        $result = checkAccess( $results[1] );
        
        if ( $result )
          exitWithContentHeaders( $_GET['file'] );

      }

      break;

    default:
      exitWithContentHeaders( $_GET['file'] );
      break;
  }

}

// recording master/audio download
if (
      $parts[0] == 'recordings' and
      preg_match('/^(\d+)\/(\d+)\/(master\/)?(\d+),(.*)\.(.+)$/', $parts[1], $results )
   ) {
  
  // 1 mod
  // 2 recordingid
  // 3 subdir
  // 4 elementid
  // 5 title
  // 6 extension
  
  // recordings/263/2263/master/2263_2265.wmv
  // recordings/263/2263/2263_2265.mp3
  $file =
    'recordings/' . $results[1] . '/' . $results[2] . '/' . $results[3] .
    $results[2] . '_' . $results[4] . '.' . $results[6]
  ;
  
  if ( DEBUG ) {
    
    echo
      "<br/>filename: <b>", PATH_PREFIX . $file, "</b>",
      " and is_readable: <b>", (int)is_readable( PATH_PREFIX . $file ), "</b><br/>"
    ;
    
  }
  
  if ( is_readable( PATH_PREFIX . $file ) and checkAccess( $results[2] ) ) {
    
    $_GET['filename'] =
      filenameize( mb_substr( $results[5], 0, 45 ) ) .
      '-' . $results[4] . '-videotorium.' . $results[6]
    ;
    
    if ( DEBUG )
      echo "<br/>Sending file with filename: ", $_GET['filename'], "<br/>";
    
    exitWithContentHeaders( $file );
    
  }
  
}

// vegso fallback: nem kapott hozzaferest
headerOutput("HTTP/1.1 404 Not Found");
headerOutput("Status: 404 Not Found"); // FastCGI alternative
exitWithContentHeaders( '/var/www/video.teleconnect.hu/httpdocs_static/images/accessdenied.png', '' );

function exitWithContentHeaders( $file, $prefix = PATH_PREFIX ) {
  
  $sendattachment = false;
  $filename = basename( $file );
  if ( isset( $_GET['filename'] ) ) {
    
    $filename = basename( $_GET['filename'] );
    $sendattachment = true;
    
  }
  
  // az attachmentek kivetelevel szinte mindig jpg lesz
  $extension = substr( $filename, -4 );

  switch ( $extension ) {
    case '.jpg': headerOutput('Content-Type: image/jpeg'); break;
    case '.gif': headerOutput('Content-Type: image/gif'); break;
    case '.png': headerOutput('Content-Type: image/png'); break;
    case '.pdf': headerOutput('Content-Type: application/pdf'); break;
    case '.rtf': headerOutput('Content-Type: application/rtf'); break;
    case '.ppt': headerOutput('Content-Type: application/vnd.ms-powerpoint'); break;
    case '.doc': headerOutput('Content-Type: application/msword'); break;
    case '.xls': headerOutput('Content-Type: application/vnd.ms-excel'); break;
    default:     headerOutput('Content-Type: application/octet-stream'); break;
  }
  
  if ( $sendattachment ) // content-type utan kellene legyen
    headerOutput('Content-Disposition: attachment; filename="' . $filename . '"');

  handleSendfile( $prefix . $file, $extension == '.mp3' );
  die();

}

function headerOutput( $string ) {

  if ( DEBUG )
    echo $string . '<br />';
  else
    header( $string );

}

function filenameize( $filename ) {

  $filename = strtr(
    $filename,
    Array(
      'á' => 'a', 'Á' => 'A',
      'é' => 'e', 'É' => 'E',
      'í' => 'i', 'Í' => 'I',
      'ó' => 'o', 'Ó' => 'O',
      'ö' => 'o', 'Ö' => 'O',
      'ő' => 'o', 'Ő' => 'O',
      'ú' => 'u', 'Ú' => 'U',
      'ü' => 'u', 'Ü' => 'U',
      'ű' => 'u', 'Ű' => 'U'
    )
  );

  $filename = preg_replace('/[^a-zA-Z0-9_\-\.]+/u', '_', trim( $filename ) );
  return $filename;

}

function checkAccess( $recordingid ) {
  
  // - session_start (meg kell kapnunk a cookie aldomainkent)
  $cookiedomain = '.teleconnect.hu';
  
  if ( isset( $_SERVER['SERVER_NAME'] ) )
    $cookiedomain = '.' . str_replace( 'static.', '', $_SERVER['SERVER_NAME'] );
  
  ini_set('session.cookie_domain',    $cookiedomain );
  session_set_cookie_params( 0 , '/', $cookiedomain );
  
  session_start();

  if ( DEBUG or !isset( $_SESSION['teleconnect']['recordingaccess'][ $recordingid ] ) ) {
    
    define('BASE_PATH',  realpath( dirname( __FILE__ ) . '/..' ) . '/' );
    if ( strpos( BASE_PATH, 'dev.') !== false )
      define('PRODUCTION', false );
    else
      define('PRODUCTION', true );
    
    include_once( BASE_PATH . 'libraries/Springboard/Application.php');
    $application = new Springboard\Application( BASE_PATH, PRODUCTION, $_REQUEST );
    $application->loadConfig('config.php');

    if ( !PRODUCTION )
      $application->loadConfig('config_local.php');

    $application->bootstrap();
    $application->bootstrap->sessionstarted = true;
    $user            = $application->bootstrap->getUser();
    $recordingsModel = $application->bootstrap->getModel('recordings');
    $access          = $application->bootstrap->getSession('recordingaccess');
    
    $recordingsModel->select( $recordingid );
    
    if ( $recordingsModel->row ) {
      
      $access[ $recordingsModel->id ] = $recordingsModel->userHasAccess( $user );
      
      if ( $access[ $recordingsModel->id ] === true )
        $result = true;
      else
        $result = false;
      
    } else
      $result = false;
    
  } else
    $result = $_SESSION['teleconnect']['recordingaccess'][ $recordingid ] === true;
  
  // - ne lockoljuk a sessiont arra az idore sem, mig az allomany
  //   eleri a bongeszot, mivel parhuzamos szalaknak szukseguk
  //   lehet ra
  session_write_close();
  return $result;
  
}

function handleSendfile( $path, $handlerange = false ) {
  
  if ( !$handlerange or !isset( $_SERVER['HTTP_RANGE'] ) )
    headerOutput('X-Sendfile: ' . $path );
  else {
    
    // csak 1 byte range-t supportolunk, es mindig a legvegeig kuldjuk a filet
    $range = substr( stristr( trim( $_SERVER['HTTP_RANGE'] ), 'bytes=' ), 6 );
    $range = substr( $range, 0, strpos( $range, '-') + 1 );
    $path  = str_replace( ',', '%2c', urlencode( $path ) ); // sima urlencode mert azt irja a lighttpd doksi
    
    headerOutput( 'X-Sendfile2: ' . $path . ' ' . $range );
    
  }
  
}
