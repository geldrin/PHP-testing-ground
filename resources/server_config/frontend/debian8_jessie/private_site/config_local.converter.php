<?php

$config = array(
  'siteid'          => 'padabudapest',
  'node_sourceip'   => 'vsq-conv.pallasvideo.hu',
  'node_role'       => 'converter',
  'baseuri'         => 'pallasvideo.hu/',
  'staticuri'       => 'vsq-static.pallasvideo.hu/',
  'cookiedomain'    => '.pallasvideo.hu',
  'logemails' => array(
    'pada@videosqr.com',
  ),
  'database' => array(
    'type'     => 'mysql',
    'host'     => 'vsq-stream.pallasvideo.hu',
    'username' => 'videosquare',
    'password' => 'bvdhhAJqcqhhpkKF',
    'database' => 'videosquare',
    'reconnectonbusy' => true,
    'maxretries' => 30,
  ),
  'combine' => array(
    'css' => true,
    'js'  => true,
    'domains' => array(
      'vsq-static.pallasvideo.hu',
      'pallasvideo.hu',
    ),
  ),

  // Path
  'convpath'              => '/srv/vsq_temp/videosquare.eu/converter/',
  'uploadpath'            => '/srv/vsq/upload/videosquare.eu/',
  'chunkpath'             => '/srv/vsq/upload/videosquare.eu/recordings_chunks/',
  'useravatarpath'        => '/srv/vsq/upload/videosquare.eu/useravatars/',
  'mediapath'             => '/srv/vsq/videosquare.eu/',
  'recordingpath'         => '/srv/vsq/videosquare.eu/recordings/',
  'livestreampath'        => '/srv/vsq/videosquare.eu/livestreams/',

  'fallbackstreamingserver' => array(
    'server' => 'vsq-stream.padabudapest.hu',
    'type'   => 'wowza',
  ),

  // Converter related settings
  'mediainfo_identify'     => 'mediainfo --full --output=XML %s 2>&1',
  // FFmpeg
  'ffmpeg_alt'             => '/home/conv/ffmpeg/ffmpeg-customVSQ-20150116-git-gc4f1abe/ffmpeg', // current FFMpeg static build'
  'ffmpeg_loglevel'        => 25,          // Loglevel
  'ffmpeg_threads'         => 0,           // Threads to use (0 - automatic)
  'max_duration_error'     => 20,          // margin of error when comparing master and converted video lengths
  'ffmpeg_resize_filter'   => false,       // use libav-filter's resize method?
  // Thumbnailer
  'ffmpegthumbnailer'      => '/usr/bin/ffmpegthumbnailer', // Path to FFmpegThumbnailer
  'thumb_video_numframes'  => 20,          // Number of video thumbnails generated per recording
  // OCR
  'ocr_engine'             => 'cuneiform', // Supported: cuneiform, tesseract
  'ocr_alt'                => '/usr/bin/cuneiform', // Path to ocr binary
  'ocr_frame_distance'     => 1.0,         // Desired distance between frames (in seconds)
  'ocr_threshold'          => 0.004,       // Max. difference between ocr frames 
  // Converter restraints                  
  'video_min_length'       => 3,           // Min. media length in seconds (unused!)
  'video_res_modulo'       => 8,           // Rescaled video X/Y resolution modulo 0 divider (16 = F4V!)
  'video_max_bw'           => 6500000,     // Maximum of video bandwidth (absolute limit)
  'video_max_res'          => '4096x2160', // Max. resolution for uploaded video (otherwise fraud upload)
  'video_max_fps'          => 60,          // Max. video FPS (unused!)
  'video_default_fps'      => 25,          // Default video FPS

  // API authentication data
  'api_user' => 'support@videosqr.com',
  'api_password' => '****************',

  // Debug
  'logtoconsole' => false,

);

// Job configuration template for frontend and converter nodes
$config['jobs'] = array(
  'converter' => array(
    'job_system_health'  => array(
      'enabled'             => true,
      'watchdogtimeoutsecs' => 5 * 60,
      'supresswarnings'     => false
    ),
    'job_media_convert2' => array(
      'enabled'             => true,
      'watchdogtimeoutsecs' => 15 * 60,
      'supresswarnings'     => false
    ),
    'job_ocr'            => array(
      'enabled'             => true,
      'watchdogtimeoutsecs' => 15 * 60,
      'supresswarnings'     => false
    ),
    'job_document_index' => array(
      'enabled'             => true,
      'watchdogtimeoutsecs' => 5 * 60,
      'supresswarnings'     => false
    ),
    'job_vcr_control'    => array(
      'enabled'             => false,
      'watchdogtimeoutsecs' => 15 * 60,
      'supresswarnings'     => false
    )
  )
);

return $config;
