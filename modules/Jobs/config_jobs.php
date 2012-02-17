<?php
// Job configuration file

//define('H264_PROFILE', '-vpre fast -vpre baseline');	// ffmpeg H.264 profile (default (=H.264 Main), baseline, normal, hq, max
define('H264_PROFILE', '-preset fast -profile baseline');	// for newer ffmpegs (2011: 0.8.2)

return array('config_jobs' => array(

	// Node
	'node'							=> 'stream.teleconnect.hu',

	// Directories
	'media_dir'						=> $this->config['datapath'] . 'temp/media/',		// Temporary dir for media conversion

	// Log path
	'log_dir'						=> $this->config['logpath'] . 'jobs/',
	
	// Job priority 
	'nice'							=> 'nice -n 19',
	
	// Sleep duration - number of seconds to sleep after an operation
	'sleep_media'					=> 60,

	// Job identifiers
	'jobid_media_convert'			=> 'job_media_convert',

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

	// FFMpeg related
	'ffmpeg_loglevel'				=> 0,								// Loglevel
	'ffmpeg_threads'				=> 0,								// Threads to use (0 - automatic)
	'ffmpeg_async_frames'			=> 10,								// Max. frames to skip when audio and video is out of sync
	'ffmpeg_h264_passes'			=> 1,								// FFMpeg passes for H.264 (not operational!)
	'ffmpeg_h264_profile'			=> '-vpre fast -vpre baseline',		// FFMpeg H.264 profile (default (main), baseline, normal, hq, max
//'ffmpeg_h264_profile'			=> '-preset fast -profile baseline',	// for newer ffmpegs (2011: 0.8.2)
	'ffmpeg_video_codec'			=> 'h264',
	'ffmpeg_flags'					=> '-strict experimental',
	'ffmpeg_audio_codec'			=> 'libfaac',						// Audio codec (libmp3lame or libfaac)


	// Thumbnails
	'thumb_video_res43'				=> '192x144',						// Resolution of normal video thumbnails
	'thumb_video_resw'				=> '300x140',						// Resolution of wide video thumbnails
	'thumb_video_resw_high'			=> '600x250',						// Resolution of wide video thumbnails
	'thumb_video_numframes'			=> 20,								// Number of video thumbnails generated per recording

	// Constraints
	
// Audio specific parameters
//define('AUDIO_MAX_BANDWIDTH',		192);			// Max. Kbps for audio track

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
		'name'			=> "Audio only version",
		'video_codec'	=> null,
		'passes'		=> null,				// Conversion passes
		'codec_profile'	=> null,
		'format'		=> "mp3",
		'file_suffix'	=> "_audio",		// recID_<suffix>.<format>
		'video_bbox'	=> null,			// Bounding box
		'video_bpp'		=> null,			// resx * resy * fps * bpp = video codec bandwidth
		'audio_codec'	=> "libmp3lame",	// AAC
		'audio_ch'		=> 2,				// Max. number of audio channels
		'audio_bw_ch'	=> 64,				// Kbps per audio channel
		'audio_mode'	=> "cbr"
	),

	// Normal quality
	'profile_video_lq' => array(
		'name'			=> "Video normal quality",
		'video_codec'	=> "h264",
		'passes'		=> 1,				// Conversion passes
		'codec_profile'	=> H264_PROFILE,
		'format'		=> "mp4",
		'file_suffix'	=> "_video_lq",		// recID_recelemID_<suffix>.<format>
		'video_bbox'	=> "640x360",		// Bounding box
		'video_bpp'		=> 0.1,				// resx * resy * fps * bpp = video codec bandwidth
		'audio_codec'	=> "libfaac",		// AAC
		'audio_ch'		=> 1,				// Max. number of audio channels
		'audio_bw_ch'	=> 64,				// Kbps per audio channel
		'audio_mode'	=> "cbr"
	),

	// High quality
	'profile_video_hq' => array(
		'name'			=> "Video high quality",
		'video_codec'	=> "h264",
		'passes'		=> 1,				// Conversion passes
		'codec_profile'	=> H264_PROFILE,
		'format'		=> "mp4",
		'file_suffix'	=> "_video_hq",		// recID_recelemID_<suffix>.<format>
		'video_bbox'	=> "1280x720",		// Bounding box
		'video_bpp'		=> 0.1,				// resx * resy * fps * bpp = video codec bandwidth
		'audio_codec'	=> "libfaac",		// AAC
		'audio_ch'		=> 2,				// Max. number of audio channels
		'audio_bw_ch'	=> 64,				// Kbps per audio channel
		'audio_mode'	=> "cbr"
	),

// Mobile with no content????
//define('VIDEO_MB_TARGET_BPP', 		0.078);			// Bits per pixel value for mobile: 480x320@25 -> 300Kbps
//define('VIDEO_MB_MAX_RESOLUTION',	'480x320');		// 16:9 bounding box for mobile video (iPhone/Android)
	
	// Content video profiles

	// Normal quality
	'profile_content_lq' => array(
		'name'			=> "Content normal quality",
		'video_codec'	=> "h264",
		'passes'		=> 1,				// Conversion passes
		'codec_profile'	=> H264_PROFILE,
		'format'		=> "mp4",
		'file_suffix'	=> "_content_lq",	// recID_recelemID_<suffix>.<format>
		'video_bbox'	=> "640x480",		// Video bounding box
		'video_bpp'		=> 0.033,			// resx * resy * fps * bpp = video codec bandwidth
		'audio_codec'	=> "libfaac",		// AAC
		'audio_ch'		=> 1,				// Max. number of audio channels
		'audio_bw_ch'	=> 64,				// Kbps per audio channel
		'audio_mode'	=> "cbr"
	),

	// High quality
	'profile_content_hq' => array(
		'name'			=>	"Content high quality",
		'video_codec'	=>	"h264",
		'passes'		=>	1,				// Conversion passes
		'codec_profile'	=>	H264_PROFILE,
		'format'		=>	"mp4",
		'file_suffix'	=>	"_content_hq",	// recID_recelemID_<suffix>.<format>
		'video_bbox'	=>	"1280x720",		// Video bounding box
		'video_bpp'		=>	0.020,			// resx * resy * fps * bpp = video codec bandwidth
		'audio_codec'	=>	"libfaac",		// AAC
		'audio_ch'		=>	2,				// Max. number of audio channels
		'audio_bw_ch'	=>	64,				// Kbps per audio channel
		'audio_mode'	=>	"cbr"
	),

));

?>