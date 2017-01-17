<?php
// Videosquare live thumbnail job

define('BASE_PATH', realpath( __DIR__ . '/../..' ) . '/' );
define('PRODUCTION', false );
define('DEBUG', false );

include_once( BASE_PATH . 'libraries/Springboard/Application/Cli.php');

// Utils
include_once('job_utils_base.php');
include_once('job_utils_log.php');
include_once('job_utils_status.php');
include_once('job_utils_media2.php');

set_time_limit(0);

// Init
$app = new Springboard\Application\Cli(BASE_PATH, PRODUCTION);

// Load jobs configuration file
$app->loadConfig('modules/Jobs/config_jobs.php');
$jconf   = $app->config['config_jobs'];
$myjobid = $jconf['jobid_live_thumb'];
$logdir  = $jconf['log_dir'];
$logfile = $myjobid .'.log';

// Log init
$debug = Springboard\Debug::getInstance();

$debug_mode = false;
if (isset($app->config['jobs']['converter']['job_live_thumbnail']['debug_mode'])) {
  $debug_mode = (bool) $app->config['jobs']['converter']['job_live_thumbnail']['debug_mode'];
}

// Exit if any STOP file is present
$stopfile = $app->config['datapath'] . 'jobs/' . $myjobid . '.stop';
$globalstopfile = $app->config['datapath'] . 'jobs/all.stop';
if (is_file($stopfile) || is_file($globalstopfile)) { exit(); }

// Runover check. Is this process already running? If yes, report and exit
if (!runOverControl($myjobid)) { exit(); }

// Run main loop
$myexitcode = Main();
die($myexitcode);
//-----------------------------------------------

/**
 * Main function.
 * 
 * @global Springboard\Application\Cli $app
 * @global Springboard\Debug $debug
 * @global boolean $debug_mode
 * @global array $jconf
 * @global string $logdir
 * @global string $logfile
 * @return boolean
 */
function Main() {
  global $app, $debug, $debug_mode, $jconf, $logdir, $logfile;

  if ( !is_writable($jconf['livestreams_dir']) ) {
    $debug->log($logdir, $logfile, "[ERROR] Temp directory " . $jconf['livestreams_dir'] . " is not writeable.", $sendmail = false);
    return (1);
  }

  clearstatcache();

  // Watchdog
  $app->watchdog();

  // Query active channels
  $channels = getActiveChannels();
  if ( $channels === false ) { return false; }

  for ( $i = 0; $i < count($channels); $i++ ) {

    $filename = $streamURL = $ffmpeg_output = $ffmpeg_filter = $ffmpeg_globals = $ffmpeg_load = null;

    // Temp directory
    $temp_dir = $jconf['livestreams_dir'] . $channels[$i]['livefeedstreamid'] . "/";

    // RTMP URL - Use fallback server always
    $rtmp_server = $app->config['fallbackstreamingserver']['server'];

    $wowza_app = "vsqlive";
    if ( isset($app->config['production']) && $app->config['production'] === false ) { $wowza_app = "dev". $wowza_app; }

    $streamURL      = sprintf("rtmp://%s/{$wowza_app}/", $rtmp_server) . $channels[$i]['streamid'];
    $filename       = "{$channels[$i]['livefeedstreamid']}_" . date("YmdHis") . ".jpg";
    $ffmpeg_globals = $app->config['ffmpeg_alt'] . ($debug_mode ? null : " -hide_banner") ." -y";
    $ffmpeg_load    = " -i {$streamURL}";

    if (isFFprobeAvailable($app)) {
      // FFprobe is not a vital component, we can skip the pre-check if it's unavailable.
      $testresult = isStreamLive($streamURL);
      if ($testresult !== true) {
        if (strpos($testresult, 'NetStream.Play.StreamNotFound') !== false && !$debug_mode) { continue; }
        $debug->log($logdir, $logfile, "[INFO] Livefed#{$channels[$i]['locationid']} is unavailable: {$streamURL} / ". trim($testresult), false);
        continue; // no livefeed available, skip processing
      }
    }

    $thumbnail_obj = array(
      'res'    => null,
      'local'  => null,
      'remote' => null,
      'filter' => null,
      'output' => null,
    );

    $resolutions = array_intersect_key($app->config['videothumbnailresolutions'], array_flip(array('4:3','wide','player','live')));
    foreach (array_values($resolutions) as $j => $res) {
      $dim = explode('x', $res);
      $dst = $temp_dir . $res . DIRECTORY_SEPARATOR;

      $thumbnail_obj['res'   ][] = $res;
      $thumbnail_obj['local' ][] = $dst;
      $thumbnail_obj['remote'][] = "{$app->config['livestreampath']}{$channels[$i]['livefeedstreamid']}/{$res}/{$filename}";
      $thumbnail_obj['filter'][] = "[{$j}] scale=w={$dim[0]}:h={$dim[1]}:force_original_aspect_ratio=increase, crop={$dim[0]}:{$dim[1]} [{$j}_out]";
      $thumbnail_obj['output'][] = " -map [{$j}_out] -frames:v 1 -an -f image2 {$dst}{$filename}";

      unset($dim, $dst);
    }

    $n = count($thumbnail_obj['res']);
    $thumbnail_obj['res'   ][] = null;
    $thumbnail_obj['local' ][] = $temp_dir .'original'. DIRECTORY_SEPARATOR;
    $thumbnail_obj['remote'][] = "{$app->config['livestreampath']}{$channels[$i]['livefeedstreamid']}/original/{$filename}";
    $thumbnail_obj['filter'][] = "[{$n}] crop=iw:ih [{$n}]";
    $thumbnail_obj['output'][] = " -map [{$n}] -frames:v 1 -an -f image2 {$temp_dir}original/{$filename}";

    $filter_bits = array("fps=fps=1, split=". count($thumbnail_obj['res']) ."[". implode('][', array_keys($thumbnail_obj['res'])) ."]");
    $filter_bits = array_merge($filter_bits, $thumbnail_obj['filter']);

    $ffmpeg_filter = " -filter_complex '". implode(';', $filter_bits) ."'";
    $ffmpeg_output = implode(null, $thumbnail_obj['output']);
    $ffmpeg_command = $ffmpeg_globals . $ffmpeg_load . $ffmpeg_filter . $ffmpeg_output;

    unset($n, $filter_bits);

    // init runExternal object
    $wrkr = new runExt();

    try {
      $cmd = $code = null;

      // Prepare working directories
      $directories = array();
      $directories = array_merge(array($temp_dir), $thumbnail_obj['local']);

      foreach($directories as $d) {
        $err = create_remove_directory($d);
        if ( !$err['code'] ) {
          $cmd  = $err['command'];
          $code = $err['result'] ? 1 : 0;
          throw new Exception($err['message']);
        }
      }

      // Chmod local directory recursively
      $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($temp_dir));
      foreach($iterator as $item) {
        chmod($item, octdec($jconf['directory_access']));
      }
      unset($iterator);

      // Run ffmpeg
      if (!$wrkr->run($ffmpeg_command)) {
        if (strpos($wrkr->getOutput(), 'NetStream.Play.StreamNotFound') !== false) {
          // there's no live stream available, don't complain about it unless we're debugging
          if ($debug_mode) { throw new Exception("Stream not found!"); }
          continue;
        } elseif(strpos($wrkr->getOutput(), 'NetStream.Play.Failed') !== false) {
          // rtmp playback has been disabled
          throw new Exception('Read access has been disabled!');
        } else {
          // ffmpeg error
          throw new Exception("FFmpeg cannot get live thumbnail. (". $wrkr->getOutput() .")");
        }
      } elseif (!is_readable(reset($thumbnail_obj['local']) . $filename) || !(reset($thumbnail_obj['local']) . $filename) > 0) {
        throw new Exception("File is not readable.");
      }

      $msg  = "[INFO] FFmpeg live thumbnail attempt for feed#". $channels[$i]['locationid'];
      $msg .= " / stream#{$channels[$i]['livefeedstreamid']} - SUCCESS! / ";
      $msg .= "return code: ". $wrkr->getCode() ."\n". ($debug_mode ? "command: '{$wrkr->command}'\n" : null);
      $debug->log($logdir, $logfile, $msg, false);
      unset($cmd, $code, $err, $msg, $wrkr);

    } catch (Exception $e) {
      if ($wrkr->getCode() !== 0) {
        $cmd  = $wrkr->command;
        $code = $wrkr->getCode();
      }
      $msg  = "[ERROR] FFmpeg live thumbnail attempt for feed#{$channels[$i]['locationid']} / ";
      $msg .= "stream#{$channels[$i]['livefeedstreamid']} - Failed!(". $e->getMessage() .")\n";
      $msg .= "command: '{$cmd}'\nreturn code: {$code}\n";
      $debug->log($logdir, $logfile, $msg, false);
      unset($cmd, $err, $msg, $wrkr);

      continue;
    }

    try {
      // Copy images to server
      $remote_path = $app->config['livestreampath'];
      $err = ssh_filecopy2($app->config['fallbackstreamingserver']['server'], $temp_dir, $remote_path, false);

      if ($debug_mode) { $debug->log($logdir, $logfile, "[INFO] Copying folder '". $temp_dir ."' > '". $remote_path ."'", false); }
      if ($err['code'] === false) { throw new Exception($err['message'] ."\nCommand: ". $err['command'] ."\nResult: ". $err['result']); }

      // Chmod remote files
      foreach ($thumbnail_obj['remote'] as $r) {
        if ($debug_mode) {
          $msg = "[INFO] Setting file permission (". $jconf['file_owner'] ."/". $jconf['file_access'] .") on '". $r ."'";
          $debug->log($logdir, $logfile, $msg, false);
        }

        $err = sshMakeChmodChown($app->config['fallbackstreamingserver']['server'], $r, false);

        if ($err['code'] === false)
          throw new Exception("[ERROR] Failed to set permissions on: '{$r}'\nMSG: {$err['message']}");
      }

    } catch (Exception $e) {
      $debug->log($logdir, $logfile, $e->getMessage(), false);
      continue;
    }

    // Update index photo filename
    $tmp = explode("/", $app->config['livestreampath']);
    $indexphotofilename = $tmp[count($tmp) - 2] ."/". $channels[$i]['livefeedstreamid'] ."/". $app->config['videothumbnailresolutions']['4:3'] ."/". $filename;
    $err = @updateLiveFeedStreamIndexPhoto($channels[$i]['livefeedstreamid'], $indexphotofilename);

    if ($debug_mode) {
      $msg  = "[OK] Updated live thumbs published for livefeed_stream.id = ". $channels[$i]['livefeedstreamid'] ." at ". $app->config['fallbackstreamingserver']['server'] .":". $indexphotofilename ."\n";
      $debug->log($logdir, $logfile, $msg, $sendmail = false);
    }

    $app->watchdog();
  } // END OF MAIN LOOP //
}

/**
 * Checks livestream is online.
 * Returns TRUE if the stream available or an error message if the command failed.
 *
 * @uses ffprobe
 *
 * @global Springboard\Application\Cli $app
 * @param type $URL
 * @return boolean|string
 */
function isStreamLive($URL) {
  global $app;

  if (empty($URL)) { return false; }

  $cmd = "{$app->config['ffprobe_alt']} -hide_banner {$URL}";

  $job = new runExt($cmd);
  $job->timeoutsec = 2.0;
  $job->setPollingRate(.5);

  if ($job->run() === false) { return $job->getOutput(); }

  return true;
}

/**
 * Checks if FFprobe available.
 *
 * @param Springboard/Application/Cli $app
 * @return boolean TRUE if binary exists and executable, FALSE otherwise
 */
function isFFprobeAvailable($app) {
  if (!array_key_exists('ffprobe_alt', $app->config)) { return false; }
  return (bool) (file_exists($app->config['ffprobe_alt']) & is_executable($app->config['ffprobe_alt']));
}

/**
 * Returns all current live channels' livestream IDs as an array, and FALSE when encountering an error or no livestreams available.
 *
 * @global Springboard\Debug $debug
 * @global string $logdir
 * @global string $logfile
 * @global Springboard\Application\Cli $app
 * @return array|bool
 */
function getActiveChannels() {
 global $debug, $logdir, $logfile, $app;

  $channels = null;
  $model = $app->bootstrap->getModel('channels');

  $now = date("Y-m-d H:i:s");
  $query = "SELECT"
    ." ch.id,"
    ." ch.starttimestamp,"
    ." ch.endtimestamp,"
    ." ch.title,"
    ." ch.isliveevent,"
    ." lf.id AS locationid,"
    ." lf.name AS locationname,"
    ." lf.issecurestreamingforced,"
    ." lf.indexphotofilename,"
    ." lfs.id AS livefeedstreamid,"
    ." lfs.qualitytag AS streamname,"
    ." lfs.keycode AS streamid,"
    ." lfs.contentkeycode AS contentstreamid"
    ." FROM"
    ." channels AS ch,"
    ." livefeeds AS lf,"
    ." livefeed_streams AS lfs"
    ." WHERE"
    ." ch.starttimestamp <= '" . $now . "' AND"
    ." ch.endtimestamp >= '" . $now . "' AND"
    ." ch.id = lf.channelid AND"
    ." lf.id = lfs.livefeedid AND"
    ." lf.issecurestreamingforced = 0"
    ." ORDER BY"
    ." ch.id";

  try {
    $rs_channels = $model->safeExecute($query);
    $channels = $rs_channels->GetArray();
  } catch (Exception $err) {
    $debug->log($logdir, $logfile, "[ERROR] SQL query failed (". $err->getTraceAsString() .")\nSQL QUERY:\n'". trim($query) ."'", false);
    return false;
  }

  // Check if any record returned
  if (count($channels) < 1) { return false; }

  return $channels;
}
