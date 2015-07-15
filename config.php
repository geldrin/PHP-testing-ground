<?php
$config = array(
  'version'      => '_v20150625',
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
  'production'   => true, // eles = true, dev = false
  // a session id amivel kulon valasztjuk az organizationoket
  // nem kell kulon beallitani
  'sessionidentifier' => '',
  //-----
  'datapath'     => $this->basepath . 'data/',
  'logpath'      => $this->basepath . 'data/logs/',
  'cachepath'    => $this->basepath . 'data/cache/',
  'modulepath'   => $this->basepath . 'modules/',
  'libpath'      => $this->basepath . 'libraries/',
  'templatepath' => $this->basepath . 'views/',
  'modelpath'    => $this->basepath . 'models/',
  'convpath'     => $this->basepath . 'data/temp/',
  'storagepath'  => '/srv/vsq/videosquare.eu/', // always absolute!
  // szoljunk e azert mert a bongeszo elavult?
  'warnobsoletebrowser' => true,
  //-----
  'destroysession' => array(
    'onuserlogout'  => true,
    'onadminlogout' => true,
  ),
  //-----
  'logemails'    => array(
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
    'ogg', 'wav', 'wma', 'mpg', 'mpeg', 'ogm', 'f4v', 'm4v', 'mkv', 'm2v', 'webm',
  ),
  'uploadpath'            => '/srv/upload/videosquare.eu/',
  'chunkpath'             => '/srv/upload/videosquare.eu/recordings_chunks/',
  'useravatarpath'        => '/srv/upload/videosquare.eu/useravatars/',
  'mediapath'             => '/srv/vsq/videosquare.eu/',
  'recordingpath'         => '/srv/vsq/videosquare.eu/recordings/',
  'livestreampath'        => '/srv/vsq/videosquare.eu/livestreams/',
  'recordings_seconds_minlength' => 3,
  'categoryiconpath'      => $this->basepath . 'httpdocs_static/images/categories/',
  'relatedrecordingcount' => 6,

  // Converter related settings
  'mediainfo_identify'     => 'mediainfo --full --output=XML %s 2>&1',
  // FFmpeg
  'ffmpeg_alt'             => '/home/conv/ffmpeg/ffmpeg-git-20140623-64bit-static/ffmpeg', // current FFMpeg static build'
  'ffmpeg_loglevel'        => 25,          // Loglevel
  'ffmpeg_threads'         => 0,           // Threads to use (0 - automatic)
  'max_duration_error'     => 20,          // margin of error when comparing master and converted video lengths
  'ffmpeg_resize_filter'   => false,       // use libav-filter's resize method?
  // Thumbnailer
  'ffmpegthumbnailer'      => '/usr/bin/ffmpegthumbnailer-2.0.8', // Path to FFmpegThumbnailer
  'thumb_video_numframes'  => 20,          // Number of video thumbnails generated per recording
  // OCR
  'ocr_engine'             => 'cuneiform', // Supported: cuneiform, tesseract
  'ocr_alt'                => 'cuneiform', // Path to ocr binary
  'ocr_frame_distance'     => 1.0,         // Desired distance between frames (in seconds)
  'ocr_threshold'          => 0.004,       // Max. difference between ocr frames 
  // Converter restraints                  
  'video_min_length'       => 3,           // Min. media length in seconds (unused!)
  'video_res_modulo'       => 8,           // Rescaled video X/Y resolution modulo 0 divider (16 = F4V!)
  'video_max_bw'           => 6500000,     // Maximum of video bandwidth (absolute limit)
  'video_max_res'          => '4096x2160', // Max. resolution for uploaded video (otherwise fraud upload)
  'video_max_fps'          => 60,          // Max. video FPS (unused!)
  'video_default_fps'      => 25,          // Default video FPS
  'video_enable_fixed_gop' => false,       // Enable/disable fixed keyframe length
  'video_gop_length_ms'    => 4000,        // Set fixed keyframe length

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
  'ondemandhdsenabled' => false,
  'livehdsenabled' => false,
  // ha false akkor rtsp-t kuldunk androidnak
  'ondemandandroidhls' => false,
  'liveandandroidhls' => false,
  'wowza'      => array(
    
    'httpurl'           => 'http://%s/vsq/_definst_/',
    'sechttpurl'        => 'https://%s/vsq/_definst_/',

    'smilurl'           => 'http://%s/vsq/_definst_/',
    'secsmilurl'        => 'https://%s/vsq/_definst_/',

    'livesmilurl'       => 'http://%s/vsqlive/_definst_/',
    'seclivesmilurl'    => 'https://%s/vsqlive/_definst_/',

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
    'seclivehttpurl'    => 'https://%s/vsqlivesec/',
    
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
    '91.120.59.241',  // stream2.videosquare.eu
    '91.120.59.236'   // conv-1.videosquare.eu
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
  // akkor ez lesz használva, asszociativ tomb, server es type kulcsnak lennie kell
  // amugy a cdn_streaming_servers adatbazis tablat akarja utanozni
  'fallbackstreamingserver' => array(
    'server' => 'stream.videosquare.eu',
    'type'   => 'wowza',
  ),
  
  //----
  // adott userid-k minden egyes a frontend fele erkezo requestje logolasra kerul
  // a data/logs/userdebuglog.txt -ben
  'debugloguserids' => array(
  ),
  
  //----
  'loadgoogleanalytics' => true,
  'googleanalytics_fallbacktrackingcode' => 'UA-34892054-1',
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

  //-------
  // a facebook userid amivel adminisztralhato a site
  // https://developers.facebook.com/docs/insights/
  'facebook_admins' => '',

  //-------
  // a reflector altal hasznalt accesscheck visszateresi erteket cacheljuk eddig
  'accesscheckcacheseconds' => 300,

  //-------
  // Job configuration template for frontend and converter nodes
  'jobs' => array(
    'frontend'  => array(
      'job_system_health' => array(
        'enabled'             => true,    // watcher to check or skip this job
        'watchdogtimeoutsecs' => 5 * 60,  // watchdog timeout (stuck processes)
        'supresswarnings'     => false    // do not send warnings (e.g. stop files)
      ),
      'job_upload_finalize' => array(
        'enabled'             => true,
        'watchdogtimeoutsecs' => 60,
        'supresswarnings'     => false
      )
    ),
    'converter' => array(
      'job_system_health'   => array(
        'enabled'               => true,     // watcher to check or skip this job
        'watchdogtimeoutsecs'   => 15 * 60,  // watchdog timeout (stuck processes)
        'supresswarnings'       => false     // do not send warnings (e.g. stop files)
      ),
      'job_media_convert2'	=> array(
        'enabled'				=> true,
        'watchdogtimeoutsecs'	=> 15 * 60,
        'supresswarnings'		=> false
      ),
      'job_document_index'	=> array(
        'enabled'				=> true,
        'watchdogtimeoutsecs'	=> 15 * 60,
        'supresswarnings'		=> false
      ),
    ),
  ),
  // Job priorities 
  'nice'            => 'nice -n 19',  // General: lowest
  'nice_high'       => 'nice -n 10',  // High
  'nice_moderate'   => 'nice -n 14',  // Moderate
  'encoding_nice'   => "nice -n 10",
  
  // Sleep duration - number of seconds to sleep after an operation
  'sleep_media'     => 60,            // Media conversion
  'sleep_short'     => 5,             // Short sleep
  'sleep_long'      => 100,           // Long sleep
  'sleep_vcr'       => 20,            // VCS job
  'sleep_vcr_wait'  => 20,            // VCS job Cisco TCS wait timeout
  'sleep_doc'       => 300,           // Document conversion
  //-------
  // ldap belepeseket ennyi idonkent vegezzuk el ujra ha a user mar be van lepve
  'directoryreauthminutes'           => 240, // default 4 ora
  // a regex amibe kell hogy legyen egy "username" nevezetu subpattern ami a
  // a felhasznalo nevet nyeri ki a kerberos remote_user-bol
  'directoryusernameregex'           => '/^(?<username>.+)@.*$/',
  'directorygroupnestedcachetimeout' => 60, // Nested group user cache refresh timeout (job_ldap_cache) in mins
  //-------
  'apidebuglog' => false,
  'checkaccessdebuglog' => false,
  'livecheckaccessdebuglog' => false,
  //-------
  // csak letezniuk kell a file-oknak, kikapcsolashoz torolni kell oket
  'sitemaintenanceflagpath'   => $this->basepath . 'data/SITEMAINTENANCE',
  'uploadmaintenanceflagpath' => $this->basepath . 'data/UPLOADMAINTENANCE',
  'dbunavailableflagpath'     => $this->basepath . 'data/DBUNAVAILABLE',
  'sshunavailableflagpath'    => $this->basepath . 'data/SSHUNAVAILABLE',

  // VCR options
  'vcr' => array(
    'server'     => '',
    'user'       => '',
    'password'   => ''
  ),
  // API authentication data
  'api_user'     => 'support@videosqr.com',
  'api_password' => '',

  // SSH authentication data
  'ssh_user'     => 'conv',
  'ssh_key'      => '/home/conv/.ssh/id_rsa',
  
  // flash playernek atadott config valtozok, ha valamit nem kell atadni
  // akkor szimplan torolni kell oket
  'flashplayer_extraconfig' => array(
    // software rendering, alapbol nem adjuk at, default erteke false
    // 'recording_swRendering' => true,

    // Video + content synchronization (Flash Player)
    'recording_synchronization'        => true,
    'recording_synchronizationTimeMin' => 1.5,    // Min. skew (sec, float)
    'recording_synchronizationTimeMax' => 30.0,   // Max. skew (sec, float)
  ),
);

$config['phpsettings'] = array(
  'log_errors'       => 1,
  'display_errors'   => !$this->production, // kikapcsolas utan johet fatal error, ugyhogy meg itt dontsuk el
  'output_buffering' => 0,
  'error_log'        => $config['logpath'] . date( 'Y-m-' ) . 'php.txt',
);

return $config;
