<?php
// Job configuration file

// Profiles: baseline, main, high
// Worked on Debian stable stream.teleconnect.hu
//define('H264_PROFILE',			'-preset fast -profile main');
//define('H264_PROFILE_MOBILE',		'-preset fast -profile baseline');
// Worked on testing Debian until 05/2012
//define('H264_PROFILE',				'-preset fast -vpre main');
//define('H264_PROFILE_MOBILE',		'-preset fast -vpre baseline');
define('H264_PROFILE',				'-profile:v main -preset:v fast');
define('H264_PROFILE_MOBILE',		'-profile:v baseline -preset:v fast');

return array('config_jobs' => array(

	// Node
	'node'							=> 'conv-1.teleconnect.hu',
	'node_role'						=> 'converter',

	// Directories
	'temp_dir'						=> $this->config['datapath'] . 'temp/',				// Temporary dir for jobs
	'media_dir'						=> $this->config['datapath'] . 'temp/media/',		// Temporary dir for media conversion
	'content_dir'					=> $this->config['datapath'] . 'temp/content/',		// Temporary dir for content conversion
	'job_dir'						=> $this->config['modulepath'] . 'Jobs/',
	'log_dir'						=> $this->config['logpath'] . 'jobs/',

	// Job priority 
	'nice'							=> 'nice -n 19',
	
	// Sleep duration - number of seconds to sleep after an operation
	'sleep_media'					=> 20,
	'sleep_vcr'						=> 20,
	'sleep_vcr_wait'				=> 20,

	// Job identifiers
	'jobid_media_convert'			=> 'job_media_convert',
	'jobid_content_convert'			=> 'job_content_convert',
	'jobid_vcr_control'				=> 'job_vcr_control',
	'jobid_watcher'					=> 'watcher',

	// SSH related settings
	'ssh_user'						=> 'conv',
	'ssh_key'						=> '/home/conv/.ssh/id_rsa',

	// File system related settings
	'directory_access'				=> '6775',		// 6775 = drwsrwsr-x
	'file_access'					=> '664',		// 664  = -rw-rw-r--
	
	// DB status definitions
	'dbstatus_init'					=> 'init',
	'dbstatus_uploaded'				=> 'uploaded',
	'dbstatus_markedfordeletion'	=> 'markedfordeletion',
	'dbstatus_reconvert'			=> 'reconvert',
	'dbstatus_copyfromfe'			=> 'copyingfromfrontend',
	'dbstatus_copyfromfe_ok'		=> 'copiedfromfrontend',
	'dbstatus_copyfromfe_err'		=> 'failedcopyingfromfrontend',
	'dbstatus_copystorage'			=> 'copyingtostorage',
	'dbstatus_copystorage_ok'		=> 'onstorage',
	'dbstatus_copystorage_err'		=> 'failedcopyingtostorage',
	'dbstatus_conv'					=> 'converting',
	'dbstatus_conv_err'				=> 'failedconverting',
	'dbstatus_conv_thumbs'			=> 'converting1thumbnails',
	'dbstatus_conv_audio'			=> 'converting2audio',
	'dbstatus_conv_audio_err'		=> 'failedconverting2audio',
	'dbstatus_conv_video'			=> 'converting3video',
	'dbstatus_conv_video_err'		=> 'failedconverting3video',
	'dbstatus_invalidinput'			=> 'invalidinput',
	// VCR related
	'dbstatus_vcr_start'			=> 'start',
	'dbstatus_vcr_starting'			=> 'starting',
	'dbstatus_vcr_recording'		=> 'recording',
	'dbstatus_vcr_disc'				=> 'disconnect',
	'dbstatus_vcr_discing'			=> 'disconnecting',
	'dbstatus_vcr_upload'			=> 'upload',
	'dbstatus_vcr_ready'			=> 'ready',
	'dbstatus_vcr_starting_err'		=> 'startingerror',
	'dbstatus_vcr_discing_err'		=> 'disconnectingerror',

	// VCR options
	'vcr_server'					=> 'tcs.streamnet.hu',
	'vcr_user'						=> 'admin',
	'vcr_password'					=> 'BoRoKaBoGYo1980',

	// FFMpeg related
	'ffmpeg_loglevel'				=> 0,								// Loglevel
	'ffmpeg_threads'				=> 0,								// Threads to use (0 - automatic)
	'ffmpeg_async_frames'			=> 10,								// Max. frames to skip when audio and video is out of sync
	'ffmpeg_h264_passes'			=> 1,								// FFMpeg passes for H.264 (not operational!)
	'ffmpeg_video_codec'			=> 'h264',
	'ffmpeg_flags'					=> '-strict experimental',
	'ffmpeg_audio_codec'			=> 'libfaac',						// Audio codec (libmp3lame or libfaac)

	// Thumbnails
	'thumb_video_small'				=> '220x130',						// Resolution of normal video thumbnails
	'thumb_video_medium'			=> '300x168',						// Resolution of wide video thumbnails
	'thumb_video_large'				=> '618x348',						// Resolution of wide video thumbnails
	'thumb_video_numframes'			=> 20,								// Number of video thumbnails generated per recording

	// Constraints
	'video_min_length'				=> 3,			// Min. media length in seconds
	'video_res_modulo'				=> 8,			// Rescaled video X/Y resolution modulo 0 divider (16 = F4V!)
	'video_max_bw'					=> 3000000,		// Maximum of video bandwidth (absolute limit)
	'video_max_res'					=> '1920x1080',	// Max. resolution for uploaded video (otherwise fraud upload)
	'video_max_fps'					=> 60,			// Max. video FPS
	
	// Media conversion profiles

	// Video profiles
	// Bits per pixel (BPP): 0.08 = low quality, 0.12 = good quality with moderate motion, 0.15 = waste

	// Audio only profile
	'profile_audio' => array(
		'name'				=> "Audio only version",
		'type'				=> "audio",
		'video_codec'		=> null,
		'passes'			=> null,
		'codec_profile'		=> null,
		'format'			=> "mp3",
		'file_suffix'		=> "_audio",		// recID_<suffix>.<format>
		'video_bbox'		=> null,
		'video_bpp'			=> null,
		'audio_codec'		=> "libmp3lame",	// AAC
		'audio_ch'			=> 2,				// Max. number of audio channels
		'audio_bw_ch'		=> 64,				// Kbps per audio channel
		'audio_mode'		=> "cbr"
	),

	// Mobile LQ version
	'profile_mobile_lq' => array(
		'name'				=> "Mobile normal quality",
		'type'				=> "video",
		'video_codec'		=> "h264",
		'passes'			=> 1,				// Conversion passes
		'codec_profile'		=> H264_PROFILE_MOBILE,
		'format'			=> "mp4",
		'file_suffix'		=> "_mobile_lq",	// recID_<suffix>.<format>
		'video_bbox'		=> "480x320",		// Bounding box
		'video_bpp'			=> 0.078,			// resx * resy * fps * bpp = video codec bandwidth
		'audio_codec'		=> "libfaac",		// AAC
		'audio_ch'			=> 1,				// Max. number of audio channels
		'audio_bw_ch'		=> 64,				// Kbps per audio channel
		'audio_mode'		=> "cbr",
		'pip_wcontent'		=> "enabled",
		'pip_codec_profile'	=> "baseline",		// H.264 profile for VideoLAN encoding
		'pip_posx'			=> "left",			// Left or right
		'pip_posy'			=> "up",			// Up or down
		'pip_align'			=> "0.03",			// 2% alignment from sides
		'pip_resize'		=> "0.2"			// 10 * % of original master media as PiP small
	),

	// Mobile HQ version
// iPad: H.264 up to 720p@30, Main Profile level 3.1 w/ AAC-LC audio up to 160 Kbps per channel, 48kHz, stereo
// iPad2: H.264 video up to 1080p@30 w/ High Profile level 4.1 + AAC-LC audio up to 160 Kbps, 48kHz, stereo audio in .m4v, .mp4, and .mov file formats;
	'profile_mobile_hq' => array(
		'name'				=> "Mobile high quality",
		'type'				=> "video",
		'video_codec'		=> "h264",
		'passes'			=> 1,				// Conversion passes
		'codec_profile'		=> H264_PROFILE_MOBILE,
		'format'			=> "mp4",
		'file_suffix'		=> "_mobile_hq",	// recID_<suffix>.<format>
		'video_bbox'		=> "1280x720",		// Bounding box
		'video_bpp'			=> 0.078,			// resx * resy * fps * bpp = video codec bandwidth
		'audio_codec'		=> "libfaac",		// AAC
		'audio_ch'			=> 2,				// Max. number of audio channels
		'audio_bw_ch'		=> 64,				// Kbps per audio channel
		'audio_mode'		=> "cbr",
		'pip_wcontent'		=> "enabled",
		'pip_codec_profile'	=> "baseline",		// H.264 profile for VideoLAN encoding
		'pip_posx'			=> "left",			// left or right
		'pip_posy'			=> "up",			// up or down
		'pip_align'			=> "0.03",			// 2% alignment from sides
		'pip_resize'		=> "0.2"			// 10% of original master media as PiP small
	),

	// Normal quality
	'profile_video_lq' => array(
		'name'				=> "Video normal quality",
		'type'				=> "video",
		'video_codec'		=> "h264",
		'passes'			=> 1,				// Conversion passes
		'codec_profile'		=> H264_PROFILE,
		'format'			=> "mp4",
		'file_suffix'		=> "_video_lq",		// recID_<suffix>.<format>
		'video_bbox'		=> "640x360",		// Bounding box
		'video_bpp'			=> 0.1,				// resx * resy * fps * bpp = video codec bandwidth
		'audio_codec'		=> "libfaac",		// AAC
		'audio_ch'			=> 1,				// Max. number of audio channels
		'audio_bw_ch'		=> 64,				// Kbps per audio channel
		'audio_mode'		=> "cbr"
	),

	// High quality
	'profile_video_hq' => array(
		'name'				=> "Video high quality",
		'type'				=> "video",
		'video_codec'		=> "h264",
		'passes'			=> 1,				// Conversion passes
		'codec_profile'		=> H264_PROFILE,
		'format'			=> "mp4",
		'file_suffix'		=> "_video_hq",		// recID_<suffix>.<format>
		'video_bbox'		=> "1280x720",		// Bounding box
		'video_bpp'			=> 0.1,				// resx * resy * fps * bpp = video codec bandwidth
		'audio_codec'		=> "libfaac",		// AAC
		'audio_ch'			=> 2,				// Max. number of audio channels
		'audio_bw_ch'		=> 64,				// Kbps per audio channel
		'audio_mode'		=> "cbr"
	),

	// Content video profiles

	// Normal quality
	'profile_content_lq' => array(
		'name'				=> "Content normal quality",
		'type'				=> "content",
		'video_codec'		=> "h264",
		'passes'			=> 1,				// Conversion passes
		'codec_profile'		=> H264_PROFILE,
		'format'			=> "mp4",
		'file_suffix'		=> "_content_lq",	// recID_<suffix>.<format>
		'video_bbox'		=> "640x480",		// Video bounding box
		'video_bpp'			=> 0.033,			// resx * resy * fps * bpp = video codec bandwidth
		'audio_codec'		=> "libfaac",		// AAC
		'audio_ch'			=> 1,				// Max. number of audio channels
		'audio_bw_ch'		=> 64,				// Kbps per audio channel
		'audio_mode'		=> "cbr"
	),

	// High quality
	'profile_content_hq' => array(
		'name'				=> "Content high quality",
		'type'				=> "content",
		'video_codec'		=> "h264",
		'passes'			=> 1,				// Conversion passes
		'codec_profile'		=> H264_PROFILE,
		'format'			=> "mp4",
		'file_suffix'		=> "_content_hq",	// recID_<suffix>.<format>
		'video_bbox'		=> "1280x720",		// Video bounding box
		'video_bpp'			=> 0.020,			// resx * resy * fps * bpp = video codec bandwidth
		'audio_codec'		=> "libfaac",		// AAC
		'audio_ch'			=> 2,				// Max. number of audio channels
		'audio_bw_ch'		=> 64,				// Kbps per audio channel
		'audio_mode'		=> "cbr"
	),

));

?>