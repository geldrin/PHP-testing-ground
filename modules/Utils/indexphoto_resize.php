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
include_once(BASE_PATH . 'modules/Jobs/job_utils_media2.php');
include_once(BASE_PATH . 'modules/Jobs/job_utils_base.php');

// ------------------------------------------------------------------------------------------------

die( Main::Main($argv, $argc) );

// ------------------------------------------------------------------------------------------------

class Main {
  private static $app, $db, $ldir, $lfile, $jconf;
  private static $path, $sizes;
  private static $debug         = false;
  private static $dochannels    = false;
  private static $doVOD         = false;
  private static $dolivefeeds   = false;
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
    
    // on demand videos //
    if (self::$doVOD) {
      $vod_dirs = self::getWorkDirectories();
      
      foreach ($vod_dirs as $vod) {
        // do vod resize
      }
    }
    
		// livefeeds //
    if (self::$dolivefeeds) {
      $live_dirs = self::getWorkDirectories('LIVE');
      
      foreach ($live_dirs as $live) {
        // do live snapshot resize
      }
    }
    
    var_dump($live_dirs); //debug
    var_dump($vod_dirs); //debug
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
    //$basepath = self::$app->config['storagepath'];
		$basepath = '/srv/vsq/videosquare.eu/'; // for debuggin'
		
		if ($basepath === false) {
			print_r("ERROR: path ". self::$path ." doesn't exists!\n");
			return false;
    }
    
    if ($type === 'VOD') {
      $pattern['storagepath'] = "%srecordings/";
      $pattern['masterpath' ] = "%s/indexpics/";
      $pattern['destpath'   ] = "%s/indexpics/%s/";
    } elseif ($type === 'LIVE') {
      $pattern['storagepath'] = "%slivestreams/";
      $pattern['masterpath' ] = "%s";
      $pattern['destpath'   ] = "%s/%s/";
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
        $subdir_iterator = new FilesystemIterator($directory_struct['master'], FilesystemIterator::SKIP_DOTS);
        $subdir_iterator->rewind();
        if (iterator_count($subdir_iterator) == 0) {
          if (self::$isverbose) self::log("[WARN] Directory empty: {$current->getPathname()}.");
          return false;
        }

        $tmp = [];
        foreach ($subdir_iterator as $f) {
          if ($f->isDir()) {
            $tmp[] = $f->getPathname();
          }
        }
        sort($tmp);
        
        $directory_struct['files' ] = self::getFilesFrom(end($tmp));
        $directory_struct['dest'  ] = [];
        
        foreach (self::$sizes as $s) {
          $directory_struct['dest']["$s"] = sprintf($pattern['destpath'], $current->getPathname(), $s);
        }
        
        return [$current->getBasename() => $directory_struct];
      }
    };
    
    $rdi = new FilesystemIterator(sprintf($pattern['storagepath'], $basepath), FilesystemIterator::SKIP_DOTS);
    while ($rdi->valid()) {
      $current = $rdi->current();
      if ($type === 'VOD') {
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
  

  /**
   * Resize file.
   * 
   * @param type $srcpath
   * @param type $filename
   * @param type $dstpath
   * @return type
   */
	public static function doResize($srcpath, $filename, $dstpath = null) {
		$ldir = self::$ldir;
		$lfile = self::$lfile;
		$cmdresize = null;
		$cmdparts  = array();
		
    if ($dstpath === null) { $dstpath = $srcpath; }
		
		$cmdparts[] = "convert \"{$srcpath}{$filename}\"";
		
		for ( $i = 0; $i < count(self::$sizes); $i++ ) {
			$dim    = self::$sizes[$i];
			$size   = $dim->w .'x'. $dim->h;
			$output = $dstpath . $filename;
			$cmdparts[] = "\( +clone -background black -resize {$size}^ -gravity center -extent {$size} -write \"{$output}\" +delete \)";
		}
		
		$cmdparts[] = "null:";
    $cmdresize = implode(' ', $cmdparts);
		
		if (self::$isdryrun) {
      if (self::$isverbose) { print_r("-> {$cmdresize}\n"); }
			return;
		}
		
		$runExt = new runExt();
		if ($runExt->run($cmdresize) === false) {
			// error
			$msg = "ERROR: command failed! (". $runExt->command .") ". $runExt->getMessage() ."\nOutput: ". $runExt->getOutput();
			self::log($msg);
			
      if (self::$isverbose) { print_r($msg . PHP_EOL); }
			
		} else {
			// OK
		}
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
		$sql = "SELECT id, title, subtitle, status FROM recordings AS r WHERE (".
		"r.status LIKE ". self::$jconf['dbstatus_copystorage_ok'] ." OR ".
	  "r.status LIKE ". self::$jconf['dbstatus_markedfordeletion'] .")";
		
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
	
	private static function updateChannel() {
		
	}
	
	private static function updateRecording() {
		
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
	 * @param type $message
	 * @param type $sendmail
	 * @return type
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
   */
  private static function getFilesFrom($directory = '.') {
    if (!file_exists($directory)) { return false; }
    
    $filelist = new ArrayObject();
    foreach (new DirectoryIterator($directory) as $item) {
      if (!$item->isDot() && !$item->isDir()) {
        $filelist->append($item->getPathname());
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
				
				case 'dry':
				case 'dryrun':
					self::$isdryrun = true; // don't actually create folders and new files
					break;
				
				case 'channels':
					self::$dochannels = true; // update channels table?
					break;
        
        case 'live':
          self::$dolivefeeds = true;
          break;
			
				case 'vod':
					self::$doVOD = true; // update recordings table?
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
  private function init() {
    if (self::$initialized === false) { return; }
    
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
