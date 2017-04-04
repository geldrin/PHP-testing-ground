<?php

/* 
 * Use this script to resize live and vod index photos en masse.
 * Pass "--help" or "-?" option for more information regarding usage.
 * 
 */

define('BASE_PATH', realpath( __DIR__ . '/../..' ) . '/' );
define('PRODUCTION', false );
define('DEBUG', false );

include_once(BASE_PATH . 'libraries/Springboard/Application/Cli.php');
include_once(BASE_PATH . 'libraries/Videosquare/Modules/RunExt.php');
include_once(BASE_PATH . 'modules/Jobs/job_utils_media2.php');
include_once(BASE_PATH . 'modules/Jobs/job_utils_base.php');

use Videosquare\Job\RunExt as RunExt;

// ------------------------------------------------------------------------------------------------

die( Main::Main($argv, $argc) );

// ------------------------------------------------------------------------------------------------

class Main {
  private static $app, $db, $ldir, $lfile, $jconf;
  private static $path, $sizes;
  private static $debug         = false;
  private static $dochannels    = false;
  private static $doVOD         = false;
  private static $doOCR         = false;
  private static $dolivefeeds   = false;
  private static $forceOverride = false;
  private static $isdryrun      = false;
  private static $isverbose     = false;
  private static $isinteractive = false;

  private static $initialized   = false;
    
  private function __construct() { /* Constructor disabled */ }

  /**
   * Main function
   * 
   * @return int
   */
  public static function Main($argv, $argc) {
    self::init();
    
    if (self::parseOptions($argc, $argv) == false) { return(1); }
    
    self::log(sprintf("Indexphoto resize tool started with the following arguments:\n- process VOD = %s\n- process LIVE = %s\n- process OCR = %s\n- is dry-run = %s\n- is verbose = %s\n",
      var_export(self::$doVOD, 1),
      var_export(self::$dolivefeeds, 1),
      var_export(self::$doOCR, 1),
      var_export(self::$isdryrun, 1),
      var_export(self::$isverbose, 1)));
    
    self::doProcess();
  }
  
  /**
   * 
   * 
   * @return int Return value, serves as an exitcode (0 if no problems occured).
   */
  private static function doProcess() {
    $vod_dirs  = [];
    $live_dirs = [];
    $ocr_dirs  = [];
    
    $numvoderr = $numliveerr = $numocrerr = 0;
    
    // on demand videos //
    if (self::$doVOD) {
      $vod_dirs = self::getWorkDirectories();
      
      foreach ($vod_dirs as $vod) {
        // do vod resize
        
        self::prepareDirectories(array_column($vod['dest'], 'path'));
        foreach ($vod['files'] as $indexphoto) {
          if (self::$isverbose) { print_r("Resizing: \"{$indexphoto}\"\n"); }
          if (self::doResize($indexphoto, $vod['dest']) === false) { $numvoderr++; }
        }
      }
    }
    
    // OCR frames //
    if (self::$doOCR) {
      $ocr_dirs = self::getWorkDirectories('OCR');
      
      foreach ($ocr_dirs as $ocr) {
        // do vod resize
        
        self::prepareDirectories(array_column($ocr['dest'], 'path'));
        foreach ($ocr['files'] as $indexphoto) {
          if (self::$isverbose) { print_r("Resizing: \"{$indexphoto}\"\n"); }
          if (self::doResize($indexphoto, $ocr['dest']) === false) { $numocrerr++; }
        }
      }
    }
    
    if (self::$dochannels) {
    }
    
    // livefeeds //
    if (self::$dolivefeeds) {
      $live_dirs = self::getWorkDirectories('LIVE');
      
      foreach ($live_dirs as $live) {
        // do live snapshot resize
        
        self::prepareDirectories(array_column($live['dest'], 'path'));
        foreach ($live['files'] as $indexphoto) {
          if (self::$isverbose) { print_r("Resizing: \"{$indexphoto}\"\n"); }
          if (self::doResize($indexphoto, $live['dest']) === false) { $numliveerr++; }
        }
      }
    }
  }
  
  //-----------------------------------------------------------------------------------------------
  
  /**
   * Get work directory structures and file lists on which we can work later on.
   * 
   * @param type $type
   * @return boolean
   */
  private static function getWorkDirectories($type = 'VOD') {
    $workdirs = [];
    $pattern  = [];
    $basepath = self::$app->config['storagepath'];
    //$basepath = '/srv/vsq/videosquare.eu/'; // for debuggin'
    
    if ($basepath === false) {
      print_r("ERROR: path ". self::$path ." doesn't exists!\n");
      return false;
    }
    
    switch ($type) {
      case "VOD":
        $pattern['storagepath'] = "%srecordings/";
        $pattern['masterpath' ] = "%s/indexpics/";
        $pattern['destpath'   ] = "%s/indexpics/%s/";
        break;
      
      case "LIVE":
        $pattern['storagepath'] = "%slivestreams/";
        $pattern['masterpath' ] = "%s";
        $pattern['destpath'   ] = "%s/%s/";
        break;
      
      case "OCR":
        $pattern['storagepath'] = "%srecordings/";
        $pattern['masterpath' ] = "%s/ocr/";
        $pattern['destpath'   ] = "%s/ocr/%s/";
        break;
    }

    // Lambda function _fill() - returns directory struct array with masterpath, file list, etc.
    $_fill = function($current, $pattern) {
      if ($current->isDir()) {
        clearstatcache();
        
        $directory_struct = [
          'main'   => null, // root directory of channel index images
          'master' => null, // path of master files (index pics with original or the biggest resolution)
          'dest'   => null, // destination directories
          'files'  => null,
        ];
        
        $directory_struct['main'  ] = $current->getBasename();
        $directory_struct['master'] = sprintf($pattern['masterpath'], $current->getPathname());
        
        if (!file_exists($directory_struct['master'])) { return false; }
        
        $subdir_iterator = new FilesystemIterator($directory_struct['master'], FilesystemIterator::SKIP_DOTS);
        $subdir_iterator->rewind();
        
        if (iterator_count($subdir_iterator) == 0) {
          if (self::$isverbose) self::log("[WARN] Directory empty: {$current->getPathname()}.");
          return false;
        }
        
        $tmp = [];
        foreach ($subdir_iterator as $f) {
          //if ($f->isDir() && !is_dir_empty($f->getPathname())) {
          if ($f->isDir() && array_search(pathinfo($f->getPathname(), PATHINFO_BASENAME), self::$sizes) === false) { // omit new directories
            $tmp[] = $f->getPathname();
          }
        }
        sort($tmp);
        
        $directory_struct['files'] = self::getFilesFrom(end($tmp));
        $directory_struct['dest' ] = [];
        
        foreach (self::$sizes as $s) {
          //$directory_struct['dest']["$s"] = sprintf($pattern['destpath'], $current->getPathname(), $s);
          $directory_struct['dest'][] = [
            'size' => $s,
            'path' => sprintf($pattern['destpath'], $current->getPathname(), $s)
          ];
        }
        
        return [$current->getBasename() => $directory_struct];
      }
    };
    
    try {
      $rdi = new FilesystemIterator(sprintf($pattern['storagepath'], $basepath), FilesystemIterator::SKIP_DOTS);
    } catch (Exception $e) {
      if ($e instanceof UnexpectedValueException) {
        print_r("Storage path cannot be found at: '". sprintf($pattern['storagepath'], $basepath) ."'!\n");
        return false;
      } else {
        throw new $e;
      }
    }
    
    while ($rdi->valid()) {
      $current = $rdi->current();
      if ($type === 'VOD' || $type === 'OCR') {
        // If we're working with VOD, we need to parse directories one level deeper
        if ($current->isDir()) {
          $sub_subdir_iterator = new FilesystemIterator($current->getPathname(), FilesystemIterator::SKIP_DOTS);
          if (iterator_count($sub_subdir_iterator) < 1) { $rdi->next(); continue; }
          
          $sub_subdir_iterator->rewind();
          while ($sub_subdir_iterator->valid()) {
            $data = $_fill($sub_subdir_iterator->current(), $pattern);
            if (!empty($data)) { $workdirs += $data; }
            $sub_subdir_iterator->next();
          }
        }
      } else {
        $data = $_fill($current, $pattern);
        if (!empty($data)) { $workdirs += $data; }
      }
      
      $rdi->next();
    }
    
    return $workdirs;
  }
  
  private static function translateFileName($filename, $newsize) {
    // livestreams/69/220x130/69_20151008144502.jpg
    $parts = null;    
    $parts = explode(DIRECTORY_SEPARATOR, $filename);
    
    if (!empty($parts)) {
      return implode(
        DIRECTORY_SEPARATOR,
        array_merge(
          array_slice($parts, 0, count($parts) - 1),
          [$newsize],
          (array) end($parts)
         )
      );
    }
  }
  
  /**
   * Resize imagefile
   * 
   * @param type $file path of the input file
   * @param type $resize_data
   * @return type
   */
  public static function doResize($file, $resize_data = null) {
    $cmdresize = null;
    $cmdparts  = array();
    
    if ($resize_data === null) { return false; }
    
    $srcpath  = pathinfo($file, PATHINFO_DIRNAME) . DIRECTORY_SEPARATOR;
    $filename = pathinfo($file, PATHINFO_BASENAME);
    
    $cmdparts[] = "convert \"{$srcpath}{$filename}\"";
    
    for ( $i = 0; $i < count($resize_data); $i++ ) {
      $dim    = $resize_data[$i]['size'];
      $size   = "{$dim->w}x{$dim->h}";
      $output = $resize_data[$i]['path'] . $filename;
      
      if (self::$forceOverride === false && file_exists($output)) {
        continue;
      }
      
      $cmdparts[] = "\( +clone -background black -resize {$size}^ -gravity center -extent {$size} -write \"{$output}\" +delete \)";
    }
    
    $cmdparts[] = "null:";
    $cmdresize = implode(' ', $cmdparts);
    
    if (self::$isverbose) { print_r("CMD -> {$cmdresize}"); }
    if (self::$isdryrun) {
      print_r(" - dry run, no execution.\n");
      return true;
    }
    
    $runExt = new RunExt();
    if ($runExt->run($cmdresize) === false) {
      // error
      $msg = "ERROR: command failed! (". $runExt->command .") ". $runExt->getMessage() ."\nOutput: ". $runExt->getOutput();
      self::log($msg);
      
      if (self::$isverbose) { print_r($msg . PHP_EOL); }
      
      return false;
    } else {
      // OK
      if (self::$isverbose) { print_r(" - OK.\n"); }
      
      return true;
    }
  }
  
  /**
   * Creates directories if not existing.
   * 
   * @param type $directory_list
   * @return boolean
   */
  private static function prepareDirectories($directory_list) {
    if (empty($directory_list)) { return false; }
    if (!is_array($directory_list)) { $directory_list = [$directory_list]; }
    
    $result = true;
    foreach ($directory_list as $dir) {
      if (!file_exists($dir) && !is_dir($dir)) {
        $result = mkdir($dir, 0755, true);
        if (self::$isverbose) {
          if ($result === true) { print_r("Directory created: '{$dir}'\n"); }
          else { print_r("Failed to create Directory: '{$dir}'\n"); }
        }
      }
    }
    return $result;
  }
  
  /**
   * Queries channels from databese.
   * 
   * @return boolean
   */
  private static function getChannels() {
    $channels = null;
    $sql = "SELECT id, title, subtitle, indexphotofilename, isliveevent FROM channels AS c WHERE c.isdeleted = 0";
    
    try {
      $channels = self::$db->Execute($sql);
    } catch (Exception $ex) {
      $msg = "ERROR: Failed to get channel list from DB!";
      
      if (self::$isverbose) { print_r($msg . PHP_EOL); }
      
      self::log($msg ."\n". $ex->getTraceAsString());
      return false;
    }
    
    return $channels;
  }
  
  /**
   * Queries recordings from database.
   * 
   * @return boolean
   */
  private static function getRecordings() {
    $recordings = null;
    $sql = "SELECT id, title, subtitle, status FROM recordings AS r WHERE ("
      . "r.status LIKE '". self::$jconf['dbstatus_copystorage_ok'] ."' OR "
      . "r.status NOT LIKE '". self::$jconf['dbstatus_markedfordeletion'] ."')";
    
    try {
      $recordings = self::$db->Execute($sql);
    } catch (Exception $ex) {
      $msg = "ERROR: Failed to get recordings from DB!";
      
      if (self::$isverbose) { print_r($msg . PHP_EOL); }
      
      self::log($msg ."\n". $ex->getTraceAsString());
      return false;
    }
    
    return $recordings;
  }
  
  private static function getLivefeeds() {
    $livefeeds = null;
    $sql = "SELECT id, channelid, name, indexphotofilename, recordinglinkid WHERE ("
      . "status NOT LIKE '". self::$jconf['dbstatus_markedfordeletion'] ."' OR "
      . "status NOT LIKE '". self::$jconf['dbstatus_deleted'] ."')";
    
    try {
      $livefeeds = self::$db->Execute($sql);
    } catch (Exception $ex) {
      $msg = "ERROR: Failed to get channel list from DB!";
      
      if (self::$isverbose) { print_r($msg . PHP_EOL); }
      
      self::log($msg ."\n". $ex->getTraceAsString());
      return false;
    }
  }
  
  private static function updateChannel() {
    
  }
  
  private static function updateRecording() {
    
  }
  
  private static function updateLivefeed() {
    
  }
  
  private static function printHelp() {
    print_r(" -- usage info --");
    print_r(PHP_EOL);
  }
  
  private static function printInvalidOpt($opt) {
    print_r("ERROR: option '{$opt}' cannot be recognized!\n");
  }
  
  private static function printEmptyOpt() {
    print_r("No options passed!\n");
  }
  
  private static function printInvalidSize($param) {
    print_r("WARN: '{$param}' cannot be parsed as size! Valid format is: <width>x<height>\n");
  }
  
  /**
   * Wrapper for SpringBoard logger for easier use.
   * 
   * @param string $message The message string to logged.
   * @param bool $sendmail TRUE to send a notification in email (default is FALSE)
   * @return NULL
   */
  private static function log($message, $sendmail = false) {
    $sendmail = (bool) $sendmail;
    if (!$message) { return; }
    self::$debug->log(self::$ldir, self::$lfile, $message, $sendmail);
  }
  
  /**
   * Searches for regular files in a given directory
   * 
   * @param string $directory directory path
   * @return boolean|\ArrayObject file list
   * @return boolean|Array list of files
   */
  private static function getFilesFrom($directory = '.') {
    if (!file_exists($directory)) { return false; }
    
    //$filelist = new ArrayObject();
    $filelist = [];
    foreach (new DirectoryIterator($directory) as $item) {
      if (!$item->isDot() && !$item->isDir()) {
        $filelist[] = $item->getPathname();
        //$filelist->append($item->getPathname());
      }
    }
    
    return $filelist;
  }
  
  /**
   * Parse command line options (pretty much hardcoded...)
   * 
   * @param type $argc
   * @param type $argv
   * @return int
   */
  private static function parseOptions($argc, $argv) {
    $error = false;
    
    if ($argc > 1) {
      
      // option parser regex (unescaped)
      // --?(?P<option>[\w\?]+)(?:\=(?:(?P<quote>[\"\']?)(?P<match>[\w\d\s\.\,]+)(?:\k<quote>)))?
      // 
      // examples:
      // "--arg=asd"   --> option="arg", quote="", match="asd"
      // "--opt"       --> option="opt", quote="", match=""
      // "-value='123' --> option="value", quote="'", match=123
      
      $re = "/--?(?P<option>[\\w\\?]+)(?:\\=(?:(?P<quote>[\\\"\\']?)(?P<match>[\\w\\d\\s\\.\\,]+)(?:\\k<quote>)))?/";
      
      for ($i = 1; $i < $argc; $i++) {
        $parse = $match = $option = $value = null;
        
        $arg = $argv[$i];
        $parse = preg_match($re, $arg, $match);
        
        if ($parse > 0 && isset($match['option'])) {
          $option = $match['option'];
          $value = isset($match['match']) ? $match['match'] : null;
        } elseif ($i == count($argv) - 1) {
          self::$path = $arg;
          break;
        }
        
        switch ($option) {

        case '?':
        case 'help':
          self::printHelp();
          return 0;
        
        case 'v':
        case 'verbose':
          self::$isverbose = true; // if true, print some nerdy stuff on console
          break;

        case 'i':
        case 'interactive':
          self::$isinteractive = true;
          break;
        
        case 'f':
        case 'force':
          self::$forceOverride = true;
          break;
        
        case 'dry':
        case 'dryrun':
          self::$isdryrun = true; // don't actually create folders and new files
          break;
        
        case 'channels':
          self::$dochannels = true; // update channels table?
          break;
        
        case 'live':  // update livefeeds table ('indexphotofilename')
          self::$dolivefeeds = true;
          break;
      
        case 'vod':
          self::$doVOD = true; // update recordings table? ('indexphotofilename')
          break;
        
        case 'o':
        case 'ocr':
          self::$doOCR = true;
          break;
        
        case 'size': // size for new indexphotos (can be used multiple times)
          $tmp = explode('x', strtolower($value));

          if (!empty($tmp)) {
            self::$sizes[] = new Size($tmp[0], $tmp[1]);
          } else {
            self::printInvalidSize($value);
            $error = true;
          }
          break;

        default:
          self::printInvalidOpt($option);
          self::printHelp();
          $error = true;
          break;
        }
        
        if ($error) break;
      }
    } else {
      self::printEmptyOpt();
      self::printHelp();
      $error = true;
    }
    
    return(!$error);
  }
  
  /**
   * Initializes framework if needed.
   * 
   * @return NULL
   */
  private static function init() {
    if (!function_exists("array_column")) {
      function array_column($array,$column_name) {
        return array_map( function($element) use($column_name) { return $element[$column_name]; }, $array);
      }
    }
    
    if (!function_exists('is_dir_empty')) {
      function is_dir_empty($dir) {
        if (!is_readable($dir)) { return null; }
        
        $handle = opendir($dir);
        while (false != ($item = readdir($handle))) {
          if ($item != '.' && $item != '..') { return false; }
        }
        return true;
      }
    }
    
    if (self::$initialized === true) { return; }
    
    self::$app = new Springboard\Application\Cli(BASE_PATH, DEBUG);
    
    self::$app->loadConfig('modules/Jobs/config_jobs.php');
    self::$debug = Springboard\Debug::getInstance();
    self::$jconf = self::$app->config['config_jobs'];
    self::$ldir  = self::$jconf['log_dir'];
    self::$lfile = 'indexresize.log';
    
    self::$db    = self::$app->bootstrap->getAdoDB(); // get DB connection
  }
}

/**
 * Frame resolution object
 */
class Size {
  private $w;
  private $h;
  
  function __construct($width = null, $height = null) {
    $this->w = $width;
    $this->h = $height;
  }
  
  public function __get($prop) {
    if (property_exists($this, $prop)) { return ((int) $this->$prop); }
    $this->trigg($prop);
  }
  
  public function __set($prop, $val) {
    if (!property_exists($this, $prop)) { $this->trigg($prop); }
    if (is_numeric($val)) { $this->$prop = (int) $val; }
  }
  
  public function __isset($prop) {
    if (property_exists($this, $prop)) { return isset($this->$prop); }
    $this->trigg($prop);
  }
  
  public function __toString() { return "{$this->w}x{$this->h}"; }
  
  private function trigg($name) { trigger_error("Property '{$name}' doesn't exists", E_USER_NOTICE); }
}
