<?php
///////////////////////////////////////////////////////////////////////////////////////////////////
// Job configuration file
///////////////////////////////////////////////////////////////////////////////////////////////////

return array('config_jobs' => array(

	// Directories
	'temp_dir'        => $this->config['convpath'],                  // Temporary dir for jobs
	'master_dir'      => $this->config['convpath'] .'master/',       // Master caching directory
	'media_dir'       => $this->config['convpath'] .'media/',        // Temporary dir for media conversion
	'content_dir'     => $this->config['convpath'] .'content/',      // Temporary dir for content conversion
	'livestreams_dir' => $this->config['convpath'] .'livestreams/',  // Temporary dir for live thumbnail
	'ocr_dir'         => $this->config['convpath'] .'ocr/',          // Temporary dir for ocr conversion
	'doc_dir'         => $this->config['convpath'] .'doc/',          // Temporary dir for document conversion
	'vcr_dir'         => $this->config['convpath'] .'vcr/',          // Temporary dir for VCR download/upload
	'job_dir'         => $this->config['modulepath'] . 'Jobs/',
	'log_dir'         => $this->config['logpath'] . 'jobs/',
	'wowza_log_dir'   => '/var/log/wowza/',

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

	// Job identifiers
	'jobid_media_convert'   => 'job_media_convert2',
	'jobid_conv_control'    => 'job_conversion_control',
	'jobid_ocr_convert'     => 'job_ocr',
	'jobid_document_index'  => 'job_document_index',
	'jobid_vcr_control'     => 'job_vcr_control',
	'jobid_maintenance'     => 'job_maintenance',
	'jobid_system_health'   => 'job_system_health',
	'jobid_upload_finalize' => 'job_upload_finalize',
	'jobid_integrity_check' => 'job_integrity_check',
	'jobid_remove_files'    => 'job_remove_files',
	'jobid_stats_process'   => 'job_stats_process',
	'jobid_watcher'         => 'watcher',
	'jobid_acc'             => 'job_accounting',
	'jobid_live_thumb'      => 'job_live_thumbnail',

	// SSH related settings
	//'ssh_user'              => 'conv',
	//'ssh_key'               => '/home/conv/.ssh/id_rsa',

	// File system related settings
	'file_owner'            => 'conv:vsq',  // conv:vsq
	'directory_access'      => '6775',      // 6775 = drwsrwsr-x
	'file_access'           => '664',       // 664  = -rw-rw-r--

	// Streaming server applications
	'streaming_live_app'            => 'vsqlive',
	'streaming_live_app_secure'     => 'vsqlivesec',
	'streaming_ondemand_app'        => 'vsq',
	'streaming_ondemand_app_secure' => 'vsqsec',
	
	// DB status definitions
	'dbstatus_init'              => 'init',
	'dbstatus_uploaded'          => 'uploaded',
	'dbstatus_markedfordeletion' => 'markedfordeletion',
	'dbstatus_deleted'           => 'deleted',
	'dbstatus_reconvert'         => 'reconvert',
	'dbstatus_copyfromfe'        => 'copyingfromfrontend',
	'dbstatus_copyfromfe_ok'     => 'copiedfromfrontend',
	'dbstatus_copyfromfe_err'    => 'failedcopyingfromfrontend',
	'dbstatus_copystorage'       => 'copyingtostorage',
	'dbstatus_copystorage_ok'    => 'onstorage',
	'dbstatus_copystorage_err'   => 'failedcopyingtostorage',
	'dbstatus_stop'              => 'stop',
	// rename!
	'dbstatus_conv'              => 'converting',
	'dbstatus_convert'           => 'convert',
	'dbstatus_conv_err'          => 'failedconverting',
	'dbstatus_regenerate'        => 'regenerate',
	'dbstatus_invalid'           => 'invalid',
	// kuka?
	'dbstatus_conv_thumbs'       => 'converting1thumbnails',
	'dbstatus_conv_audio'        => 'converting2audio',
	'dbstatus_conv_audio_err'    => 'failedconverting2audio',
	'dbstatus_conv_video'        => 'converting3video',
	'dbstatus_conv_video_err'    => 'failedconverting3video',
	'dbstatus_conv_ocr'          => 'converting4ocr',
	'dbstatus_conv_ocr_fail'     => 'failedconverting4ocr',
	'dbstatus_invalidinput'      => 'failedinput',
	'dbstatus_cimage'            => 'contributorimagecopy',
	// VCR related
	'dbstatus_vcr_start'         => 'start',
	'dbstatus_vcr_starting'      => 'starting',
	'dbstatus_vcr_recording'     => 'recording',
	'dbstatus_vcr_disc'          => 'disconnect',
	'dbstatus_vcr_discing'       => 'disconnecting',
	'dbstatus_vcr_upload'        => 'upload',
	'dbstatus_vcr_uploading'     => 'uploading',
	'dbstatus_vcr_ready'         => 'ready',
	'dbstatus_vcr_starting_err'  => 'failedstarting',
	'dbstatus_vcr_discing_err'   => 'faileddisconnecting',
	'dbstatus_vcr_upload_err'    => 'faileduploading',
	'dbstatus_vcr_recording_err' => 'failedrecording',
	// Document indexing related
	'dbstatus_indexing'          => 'indexing',
	'dbstatus_indexing_err'      => 'failedindexing',
	'dbstatus_indexing_empty'    => 'empty',
	'dbstatus_indexing_ok'       => 'completed',

	// VCR options
/*	'vcr_server'             => 'tcs.streamnet.hu',
	'vcr_user'               => 'admin',
	'vcr_password'           => 'BoRoKaBoGYo1980', */

	// API authentication data
/*	'api_user'               => 'support@videosqr.com',
	'api_password'           => 'MekkElek123', */

	// FFMpeg related
	'ffmpeg_alt'             => '/home/conv/ffmpeg/ffmpeg-customvsq-git20150116-static/ffmpeg', // current FFMpeg static build
	'ffmpeg_loglevel'        => 25,               // Loglevel
	'ffmpeg_threads'         => 0,                // Threads to use (0 - automatic)
	'ffmpeg_async_frames'    => 10,               // Max. frames to skip when audio and video is out of sync (deprecated)
	'ffmpeg_h264_passes'     => 1,                // FFMpeg passes for H.264 (not operational!)
	'max_duration_error'     => 20,               // margin of error when comparing master and converted video lengths

	// Thumbnails
	'ffmpegthumbnailer'      => '/usr/bin/ffmpegthumbnailer-2.0.8', // ffmpegthumbnailer path
	// 'thumb_video_small'      => '220x130',         // Resolution of normal video thumbnails
	// 'thumb_video_medium'     => '300x168',         // Resolution of wide video thumbnails
	// 'thumb_video_large'      => '618x348',         // Resolution of wide video thumbnails
	'thumb_video_numframes'  => 20,                // Number of video thumbnails generated per recording

	// Ocr frames
	'ocr_engine'             => 'cuneiform',       // Supported: cuneiform, tesseract
	'ocr_alt'                => '/home/gergo/cf',  // Path to ocr binary
	'ocr_frame_distance'     => 1.0,               // Desired distance between frames (in seconds)
	'ocr_threshold'          => 0.004,             // Max. difference between ocr frames 
	
	// Constraints
	'video_min_length'       => 3,                 // Min. media length in seconds
	'video_res_modulo'       => 8,                 // Rescaled video X/Y resolution modulo 0 divider (16 = F4V!)
	'video_max_bw'           => 6500000,           // Maximum of video bandwidth (absolute limit)
	'video_max_res'          => '4096x2160',       // Max. resolution for uploaded video (otherwise fraud upload)
	'video_max_fps'          => 60,                // Max. video FPS
	'video_default_fps'      => 25,                // Default video FPS
));

?>
