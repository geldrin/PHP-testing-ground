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
    'jobid_ldap_cache'      => 'job_ldap_cache',
    'jobid_live_counters'   => 'job_live_counters',
    'jobid_stats_recseg'    => 'job_stats_recsegments',
    'jobid_wowza_monitor'   => 'wowza_monitor',

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
    'dbstatus_update_err'        => 'failedupdating',
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
    
    // Document conversion
    'document_types_text'       => array("txt", "csv", "xml"),
    'document_types_doc'        => array("htm", "html", "doc", "docx", "odt", "ott", "sxw"),
    'document_types_pres'       => array("ppt", "pptx", "pps", "odp"),
    'document_types_pdf'        => array("pdf"),

));

?>
