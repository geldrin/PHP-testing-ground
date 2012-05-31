<?php
$config = array(
  'siteid'       => 'teleconnect',
  'hashseed'     => 'ö923mfk3a,.dműteleconnect',
  'version'      => '_v20120502',
  'charset'      => 'UTF-8',
  'cacheseconds' => 3600,
  'errormessage' => 'An unexpected error has occured, our staff has been notified. Sorry for the inconvenience and thanks for your understanding!',
  //-----
  'docroot'      => $this->basepath . 'httpdocs/',
  'baseuri'      => 'videosquare.eu/', // protocol nelkul, peldaul "dotsamazing.com/"
  'staticuri'    => 'static.videosquare.eu/',
  'adminuri'     => 'videosquare.eu/a2143/',
  'loginuri'     => 'users/login',
  'cookiedomain' => '.videosquare.eu',
  //-----
  'datapath'     => $this->basepath . 'data/',
  'logpath'      => $this->basepath . 'data/logs/',
  'cachepath'    => $this->basepath . 'data/cache/',
  'modulepath'   => $this->basepath . 'modules/',
  'libpath'      => $this->basepath . 'libraries/',
  'templatepath' => $this->basepath . 'views/',
  'modelpath'    => $this->basepath . 'models/',
  //-----
  'destroysession' => array(
    'onuserlogout'  => true,
    'onadminlogout' => true,
  ),
  //-----
  'logemails'    => array(
    'dev@dotsamazing.com',
    'hiba@videosquare.eu',
  ),
  //-----
  'smtp'         => array(
    'auth'     => false,
    'host'     => 'localhost',
    'username' => '',
    'password' => '',
  ),
  //-----
  'mail'         => array(
    'fromemail' => 'support@videosquare.eu',
    'fromname'  => 'Videosquare',
    'errorsto'  => '',
    'type'      => 'text/html; charset="UTF-8"'
  ),
  //-----
  'defaultlanguage' => 'hu',
  'languages'       => array( 'hu', 'en' ),
  'locales'         => array( // setlocale-hez
    'hu' => array(
      'hu_HU.UTF-8',
      'Hungarian_Hungary.1250',
    ),
  ),
  'timezone' => 'Europe/Budapest',
  //-----
  'database' => array(
    'type'     => 'mysql',
    'host'     => 'localhost',
    'username' => 'videosquare',
    'password' => '6NosJir7PWAanzo9hfv7',
    'database' => 'videosquare',
    'reconnectonbusy' => true,
    'maxretries' => 30,
  ),
  'image' => array(
    'jpgquality' => 80,
  ),
  //-----
  'cache' => array(
    'type' => 'redis',
    'host' => '127.0.0.1',
    'port' => 6379,
  ),
  //----
  'disable_uploads' => false,
  'uploadpath'    => '/srv/upload/videosquare.eu/',
  'mediapath'     => '/srv/storage/videosquare.eu/',
  'recordingpath' => '/srv/storage/videosquare.eu/recordings/',
  'recordings_seconds_minlength' => 3,
  'relatedrecordingcount' => 6,
  'mplayer_identify' => 'mplayer -ao null -vo null -frames 0 -identify %s 2>&1',
  //----
  'combine' => array(
    'css' => true,
    'js'  => true,
    'domains' => array(
      'static.videosquare.eu',
      'videosquare.eu',
    ),
  ),
  //----
  'videothumbnailresolutions' => array(
    '4:3'    => '220x130',
    'wide'   => '300x168',
    'player' => '618x348',
  ),
  //----
  'wowza' => array(
    'httpurl'  => 'http://stream.videosquare.eu:1935/vsq/',
    'rtmpurl'  => 'rtmp://stream.videosquare.eu:1935/vsq/',
    'rtmpturl' => 'rtmpt://stream.videosquare.eu:80/vsq/',
    'rtspurl'  => 'rtsp://stream.videosquare.eu/vsq/',
  ),
  //----
  // lehet ures is ha nem akarjuk redirectelni a usert, ilyenkor
  // siman die()-ol az applikacio
  'organizationfallbackurl' => 'http://www.videosquare.eu',
);

$config['phpsettings'] = array(
  'log_errors'       => 1,
  'display_errors'   => !$this->production, // kikapcsolas utan johet fatal error, ugyhogy meg itt dontsuk el
  'output_buffering' => 0,
  'error_log'        => $config['logpath'] . date( 'Y-m-' ) . 'php.txt',
);

return $config;