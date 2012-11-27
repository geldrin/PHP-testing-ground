<?php
$config = array(
  'siteid'       => 'teleconnect',
  'hashseed'     => 'ö923mfk3a,.dműteleconnect',
  'version'      => '_v201211271500',
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
    'fromname'  => 'Videosquare DEV',
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
    'en' => array(
      'en_US.UTF-8',
      'us'
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
  'allowedextensions' => array(
    'wmv', 'avi', 'mov', 'flv', 'mp4', 'asf', 'mp3', 'flac',
    'ogg', 'wav', 'wma', 'mpg', 'mpeg', 'ogm', 'f4v', 'm4v', 'mkv', 'm2v',
  ),
  'disable_uploads'  => false,
  'uploadpath'       => '/srv/upload/videosquare.eu/',
  'chunkpath'        => '/srv/upload/videosquare.eu/recordings_chunks/',
  'mediapath'        => '/srv/storage/videosquare.eu/',
  'recordingpath'    => '/srv/storage/videosquare.eu/recordings/',
  'useravatarpath'   => '/srv/storage/videosquare.eu/useravatars/',
  'recordings_seconds_minlength' => 3,
  'categoryiconpath' => $this->basepath . 'httpdocs_static/images/categories/',
  'relatedrecordingcount' => 6,
  'mplayer_identify' => 'mplayer -ao null -vo null -frames 0 -identify %s 2>&1',
  'mediainfo_identify' => 'mediainfo --output=XML %s 2>&1',
  //----
  'combine' => array(
    'css'     => true,
    'js'      => true,
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
    
    'httpurl'           => 'http://stream.videosquare.eu/vsq/_definst_/',
    'sechttpurl'        => 'http://stream.videosquare.eu/vsq/_definst_/',
    
    'rtmpurl'           => 'rtmp://stream.videosquare.eu:1935/vsq/',
    'secrtmpurl'        => 'rtmpe://stream.videosquare.eu:1935/vsqsec/',
    
    'rtmpturl'          => 'rtmpt://stream.videosquare.eu:80/vsq/',
    'secrtmpturl'       => 'rtmpte://stream.videosquare.eu:80/vsqsec/',
    
    'rtspurl'           => 'rtsp://stream.videosquare.eu/vsq/_definst_/',
    'secrtspurl'        => 'rtsp://stream.videosquare.eu/vsq/_definst_/',
    
    'liveingressurl'    => 'rtmp://stream.videosquare.eu:1935/vsqlive/',
    'secliveingressurl' => 'rtmp://stream.videosquare.eu:1935/vsqlive/',
    
    'liveurl'           => 'rtmpt://stream.videosquare.eu:80/vsqlive/',
    'secliveurl'        => 'rtmpte://stream.videosquare.eu:80/vsqlivesec/',
    
    'livehttpurl'       => 'http://stream.videosquare.eu/vsqlive/',
    'seclivehttpurl'    => 'http://stream.videosquare.eu/vsqlive/',
    
    'livertspurl'       => 'rtsp://stream.videosquare.eu/vsqlive/',
    'seclivertspurl'    => 'rtsp://stream.videosquare.eu/vsqlive/',
    
  ),
  //----
  // lehet ures is ha nem akarjuk redirectelni a usert, ilyenkor
  // siman die()-ol az applikacio
  'organizationfallbackurl' => 'http://videosquare.eu',
  'chatpolltimems'          => 1000,
  //----
  'presenterroleids' => array(
    1,
  ),
  
);

$config['phpsettings'] = array(
  'log_errors'       => 1,
  'display_errors'   => !$this->production, // kikapcsolas utan johet fatal error, ugyhogy meg itt dontsuk el
  'output_buffering' => 0,
  'error_log'        => $config['logpath'] . date( 'Y-m-' ) . 'php.txt',
);

return $config;