<?php
$config = array(
  'siteid'       => 'teleconnect',
  'hashseed'     => 'ö923mfk3a,.dműteleconnect',
  'version'      => '_v20120404',
  'charset'      => 'UTF-8',
  'cacheseconds' => 3600,
  'errormessage' => 'An unexpected error has occured, our staff has been notified. Sorry for the inconvenience and thanks for your understanding!',
  //-----
  'docroot'      => $this->basepath . 'httpdocs/',
  'baseuri'      => 'video.teleconnect.hu/', // protocol nelkul, peldaul "dotsamazing.com/"
  'staticuri'    => 'static.teleconnect.hu/',
  'adminuri'     => 'video.teleconnect.hu/a2143/',
  'loginuri'     => 'users/login',
  'cookiedomain' => '.teleconnect.hu',
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
    'hiba@teleconnect.hu',
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
    'fromemail' => 'no-reply@teleconnect.hu',
    'fromname'  => 'Teleconnect',
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
    'type'     => 'mysqli',
    'host'     => '127.0.0.1',
    'username' => 'teleconnect',
    'password' => '6NosJir7PWAanzo9hfv7',
    'database' => 'teleconnect',
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
  'uploadpath'    => '/srv/upload/',
  'mediapath'     => '/srv/storage/httpdocs/',
  'recordingpath' => '/srv/storage/httpdocs/recordings/',
  'recordings_seconds_minlength' => 3,
  'mplayer_identify' => 'mplayer -ao null -vo null -frames 0 -identify %s 2>&1',
  //----
  'combine' => array(
    'css' => true,
    'js'  => true,
    'domains' => array(
      'static.teleconnect.hu',
      'video.teleconnect.hu',
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
    'httpurl'  => 'http://stream.teleconnect.hu:1935/vod/',
    'rtmpurl'  => 'rtmp://stream.teleconnect.hu:1935/vod/',
    'rtmpturl' => 'rtmpt://stream.teleconnect.hu:80/vod/',
  ),
  //----
  'organizationfallbackurl' => 'http://video.teleconnect.hu',
);

$config['phpsettings'] = array(
  'log_errors'       => 1,
  'display_errors'   => !$this->production, // kikapcsolas utan johet fatal error, ugyhogy meg itt dontsuk el
  'output_buffering' => 0,
  'error_log'        => $config['logpath'] . date( 'Y-m-' ) . 'php.txt',
);

return $config;