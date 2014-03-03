<?php
$config = array(
  'version'      => '_v20140220',
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
    'hiba@videosqr.com',
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
    'fromemail' => 'support@videosqr.com',
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
    'en' => array(
      'en_US.UTF-8',
      'us'
    ),
  ),
  'timezone' => 'Europe/Budapest',
  'image' => array(
    'jpgquality' => 80,
  ),
  //-----
  'cache' => array(
    'type' => 'redis',
    'host' => '127.0.0.1',
    'port' => 6379,
  ),
  //-----
  'redis' => array(
    'host'     => '127.0.0.1',
    'port'     => 6379,
    'database' => 0,
  ),
  //----
  'allowedextensions' => array(
    'wmv', 'avi', 'mov', 'flv', 'mp4', 'asf', 'mp3', 'flac',
    'ogg', 'wav', 'wma', 'mpg', 'mpeg', 'ogm', 'f4v', 'm4v', 'mkv', 'm2v',
  ),
  'disable_uploads'  => false,
  'uploadpath'       => '/srv/upload/videosquare.eu/',
  'chunkpath'        => '/srv/upload/videosquare.eu/recordings_chunks/',
  'useravatarpath'   => '/srv/upload/videosquare.eu/useravatars/',
  'mediapath'        => '/srv/vsq_storage/videosquare.eu/',
  'recordingpath'    => '/srv/vsq_storage/videosquare.eu/recordings/',
  'recordings_seconds_minlength' => 3,
  'categoryiconpath' => $this->basepath . 'httpdocs_static/images/categories/',
  'relatedrecordingcount' => 6,
  'mplayer_identify' => 'mplayer -ao null -vo null -frames 0 -identify %s 2>&1',
  'mediainfo_identify' => 'mediainfo --full --output=XML %s 2>&1',
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
    
    'httpurl'           => 'http://%s/vsq/_definst_/',
    'sechttpurl'        => 'http://%s/vsq/_definst_/',
    
    'rtmpurl'           => 'rtmp://%s:1935/vsq/',
    'secrtmpsurl'       => 'rtmps://%s/vsqsec/',
    'secrtmpurl'        => 'rtmpe://%s:1935/vsqsec/',
    
    'rtmpturl'          => 'rtmpt://%s:80/vsq/',
    'secrtmpturl'       => 'rtmpte://%s:80/vsqsec/',
    
    'rtspurl'           => 'rtsp://%s/vsq/_definst_/',
    'secrtspurl'        => 'rtsp://%s/vsqsec/_definst_/',
    
    'liveingressurl'     => 'rtmp://stream.videosquare.eu:1935/vsqlive/',
    'secliveingressurl'  => 'rtmps://stream.videosquare.eu:1935/vsqlivesec/',
    'secliveingressurl2' => 'rtmpe://stream.videosquare.eu:1935/vsqlivesec/',
    'secliveingressurl3' => 'rtmp://stream.videosquare.eu:1935/vsqlivesec/', // ahova feltöltenek, rtmp szigoruan de sec application
    
    'liveurl'           => 'rtmpt://%s:80/vsqlive/',
    'secliveurl'        => 'rtmpte://%s:80/vsqlivesec/',
    
    'livehttpurl'       => 'http://%s/vsqlive/',
    'seclivehttpurl'    => 'http://%s/vsqlivesec/',
    
    'livertspurl'       => 'rtsp://%s/vsqlive/',
    'seclivertspurl'    => 'rtsp://%s/vsqlivesec/',
    
    'livertmpurl'       => 'rtmp://%s:1935/vsqlive/',
    'seclivertmpsurl'   => 'rtmps://%s:1935/vsqlivesec/',
    'seclivertmpeurl'   => 'rtmpe://%s:1935/vsqlivesec/',
    'seclivertmpurl'    => 'rtmp://%s:1935/vsqlivesec/',
    
  ),
  //----
  // lehet ures is ha nem akarjuk redirectelni a usert, ilyenkor
  // siman die()-ol az applikacio
  'organizationfallbackurl' => 'http://videosquare.eu',
  'chatpolltimems'          => 1000,
  
  //----
  // Az users.issingleloginenforced=1 tipusu usereknel
  // annak az idonek a hossza masodpercekben, amig a usert 
  // belepettnek tekintjuk ujabb oldal letoltese nelkul. Maximum ennyi
  // ideig nem tud belepni a user megegyszer, ha pl. lezarta a bongeszojet,
  // es elvesztette a sessionazonositojat.
  // 
  // Ha egy felhasznalo elkezd nezni egy kozvetitest, es kozben lejar ez az 
  // idoablak, akkor masik felhasznalo be tud lepni 
  // parhuzamosan: ennek elkerulesere ajax "ping" funkcio hasznalhato, ami
  // hiba eseten akar ki is dobhatja a felhasznalot.
  'sessiontimeout' => 135,
  'sessionpingseconds' => 60,
  
  //----
  // Az itt felsorolt IP cimeknel nem ellenorizunk semmit, rogton jova
  // hagyjuk a live/checkstreamaccess hivasnal (lehet ipv4/ipv6, nem szamit)
  'allowedstreamips' => array(
    '91.120.59.239',  // tcs.streamnet.hu
    '91.120.59.241'   // stream2.videosquare.eu
  ),
  
  //----
  // Felvetelhez tartozo utolso megtekintett pozicionak frissitese ennyi
  // masodpercenkent tortenik
  'recordingpositionupdateseconds' => 60,
  
  //----
  // A flash altal is hasznalt kozos seed amivel a hasheket hasznaljuk.
  'flashhashseed' => 'ï!½Õz]Â7}h=ÎádÎ¶WâRì5mÂgà-ôZõ»',
  
  //----
  // Ha true akkor https-re forcoljuk az api urlt, amugy automata attol fuggoen
  // hogy milyen a site
  'forcesecureapiurl' => true,
  //----
  // Ha nem 0 akkor kuldjuk a HSTS headert, masodpercben van, 1nap 86400
  'forcesecuremaxage' => 0,

  //----
  // alapból adatbázisban tároljuk a streaming servereket, ha nincs alapértelmezett
  // akkor ez lesz használva
  'fallbackstreamingserver' => 'stream.videosquare.eu',
  
  //----
  // adott userid-k minden egyes a frontend fele erkezo requestje logolasra kerul
  // a data/logs/userdebuglog.txt -ben
  'debugloguserids' => array(
  ),
  
  //----
  'loadgoogleanalytics' => true,
  'loadaddthis' => true,
  
  //----
  'setupdirs' => array(
    'user'            => 'dam', // a user/group amire chown -R eljuk az egesz konyvtarat
    'group'           => 'vsq',
    'perms'           => 'g+w', // a chmod -R parametere
    'privilegeduser'  => 'www-data', // a gitignorebol vett konyvtarak user/group/permje
    'privilegedgroup' => 'www-data',
    'extradirs'       => array(
      array(
        'dir'  => $this->basepath . 'httpdocs/flash',
        'user' => 'xtro',
      ),
      array(
        'dir'   => $this->basepath . 'data',
        'perms' => 'a+w',
      ),
      array(
        'dir'   => $this->basepath . 'modules/Locale',
        'perms' => 'a+w',
      ),
      array(
        'dir'   => $this->basepath . 'modules/**/**/Locale',
        'perms' => 'a+w',
      ),
    ),
  ),
  //-------
  'recaptchaenabled' => true,
  'recaptchapub'     => '6LfNBu8SAAAAAKcud9Rcdjlt9aDHhRpxb5KeTd21',
  'recaptchapriv'    => '6LfNBu8SAAAAAF7-5iJibdVzFrC1_K-YgILLVu4I',
);

$config['phpsettings'] = array(
  'log_errors'       => 1,
  'display_errors'   => !$this->production, // kikapcsolas utan johet fatal error, ugyhogy meg itt dontsuk el
  'output_buffering' => 0,
  'error_log'        => $config['logpath'] . date( 'Y-m-' ) . 'php.txt',
);

return $config;
g;
