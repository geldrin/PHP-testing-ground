<?php

class Bootstrap {
  protected static $instance;
  protected $instances      = array();
  protected $forms          = array();
  protected $objects        = array();
  protected $caches         = array();
  protected $headerssent    = false;
  
  public $sessionstarted    = false;
  public $debug             = false;
  public $application;
  public $config            = array();
  public $basepath;
  public $production;
  
  public function __construct( $application ) {
    
    self::$instance    = $this;
    $this->application = $application;
    $this->config      = $application->config;
    $this->basepath    = $application->basepath;
    $this->production  = $application->production;
    
    $this->setupAutoloader();
    $this->setupOutputBuffer();
    $this->setupDefault();
    $this->setupLanguage();
    
    $this->setupPHPSettings();
    $this->setupDebug();
    
    
  }
  
  public static function getInstance() {
    return self::$instance;
  }
  
  protected function setupOutputBuffer() {
    
    if ( ob_get_level() ) // kill that buffer
      ob_end_clean();
    
    ini_set('output_buffering', 0 );
    
  }
  
  protected function setupDefault() {
    
    if ( !defined('ISCLI') )
      define('ISCLI', php_sapi_name() == 'cli' );
    
    $ssl = false;
    
    if (
         @$_SERVER['HTTPS'] == 'on' or
         @$_SERVER['HTTPS'] == 1 or
         @$_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https' // reverse proxy-k hasznaljak
       )
      $ssl = true;
    
    define('SSL', $ssl );
    
    date_default_timezone_set( $this->config['timezone'] );
    
    mb_internal_encoding( $this->config['charset'] );
    mb_regex_encoding( $this->config['charset'] );
    
    setlocale( LC_ALL, $this->config['locales'][ Springboard\Language::get() ] );
    setlocale( LC_NUMERIC, 'C', 'english' );
    
  }
  
  protected function setupAutoloader() {
    
    include_once( $this->config['libpath'] . 'Springboard/Autoloader.php' );
    $loader = Springboard\Autoloader::getInstance( $this );
    $loader->register();
    
  }
  
  protected function setupPHPSettings() {
    
    foreach( @$this->config['phpsettings'] as $setting => $value )
      ini_set( $setting, $value );
    
  }
  
  protected function setupLanguage() {
    
    Springboard\Language::$defaultlanguage = $this->config['defaultlanguage'];
    Springboard\Language::$languages       = $this->config['languages'];
    
  }
  
  public function setupSession( $allowoverride = false ) {
    
    if ( $this->sessionstarted )
      return;
    
    $cookiedomain = $this->config['cookiedomain'];
    // egy dinamikus cookie domain a host alapjan amibe a static. aldomain
    // nem tartozik bele
    if ( isset( $_SERVER['SERVER_NAME'] ) )
      $cookiedomain = '.' . str_replace( 'static.', '', $_SERVER['SERVER_NAME'] );
    
    ini_set('session.cookie_domain',    $cookiedomain );
    session_set_cookie_params( 0 , '/', $cookiedomain );
    
    if ( $allowoverride and isset( $_REQUEST['PHPSESSID'] ) )
      session_id( $_REQUEST['PHPSESSID'] );
    
    $this->sessionstarted = session_start();
    
    $smarty = $this->getSmarty();
    $smarty->assign('sessionid', session_id() );
    return $this->sessionstarted;
    
  }
  
  public function getAdoDB( $errorhandler = true ) {
    
    if ( isset( $this->instances['adodb'] ) )
      return $this->instances['adodb'];
    
    define('ADODB_OUTP', 'Springboard\\adoDBDebugPrint'); // adodb debug print func( $msg, $newline )
    if ( !defined('DISABLE_DB_ERRORLOG') ) {
      define('ADODB_ERROR_LOG_DEST', $this->config['logpath'] . date("Y-m-" ) . 'database.txt' );
      define('ADODB_ERROR_LOG_TYPE', 3 /* 0-syslog, 1-email, 2-debugger, 3-file */ );
    }
    define('ADODB_FORCE_NULLS', 1 );
    $GLOBALS['ADODB_CACHE_DIR']  = $this->config['cachepath'];
    $GLOBALS['ADODB_COUNTRECS']  = false;
    $GLOBALS['ADODB_FORCE_TYPE'] = 1; // force null
    
    if ( $errorhandler ) {

      // when adodb errorhandler needed
      if ( !( @include_once( $this->config['libpath'] . 'adodb.515/adodb-exceptions.inc.php') ) ) {
        // adodb not found under LIBPATH - try include_path location
        include_once('adodb.515/adodb-exceptions.inc.php');
        include_once('adodb.515/adodb-errorhandler.inc.php');
        include_once('adodb.515/adodb.inc.php');
      }
      else {
        // adodb found under LIBPATH - continue this way
        include_once( $this->config['libpath'] . 'adodb.515/adodb-errorhandler.inc.php');
        include_once( $this->config['libpath'] . 'adodb.515/adodb.inc.php');
      }
    }
    else {

      // adodb errorhandler unnecessary

      if ( !( @include_once( $this->config['libpath'] . 'adodb.515/adodb-exceptions.inc.php') ) ) {
        // adodb not found under LIBPATH - try include_path location
        include_once('adodb.515/adodb-exceptions.inc.php');
        include_once('adodb.515/adodb.inc.php');
      }
      else {
        // adodb found under LIBPATH - continue this way
        include_once( $this->config['libpath'] . 'adodb.515/adodb.inc.php');
      }
    }

    try {
      
      $i = $this->config['database']['maxretries'];
      while ( $i ) {
        
        $db = ADONewConnection( $this->config['database']['type'] );
        
        if ( $this->debug )
          $db->debug = 1;
        
        if ( isset( $_REQUEST['logsql'] ) and $this->debug ) {

          $db->LogSQL( true );
          $GLOBALS['ADODB_PERF_MIN'] = 2;

        }

        $rs = @$db->Connect(
          $this->config['database']['host'],
          $this->config['database']['username'],
          $this->config['database']['password'],
          $this->config['database']['database']
        );
        
        if ( $rs )
          break;
        elseif ( $db->ErrorMsg() == 'Too many connections' and $this->config['database']['reconnectonbusy'] ) {
          
          $i--;
          if ( !$i )
            throw new Exception( $db->ErrorMsg() );
          
          sleep(1);
          
        }
        else
          throw new Exception( $db->ErrorMsg() );
        
      }
      
    } catch ( Exception $e ) {
      
      $queue = $this->getMailQueue( true );
      $queue->instant = 1;
      
      foreach ( $this->config['logemails'] as $email )
        $queue->put(
          $email,
          $email,
          '[' . $this->config['siteid'] . '] DB error: ' . $e->getMessage(),
          $e->getMessage(),
          false,
          'text/plain'
        );
      
      if ( !ISCLI ) {
        
        $smarty = $this->getSmarty();

        $smarty->assign('error',      $e->getMessage() );
        $smarty->assign('BASE_URI',   $this->config['baseuri'] );
        $smarty->assign('STATIC_URI', $this->config['staticuri'] );
        $smarty->display('errorpage.tpl');
        die();
        
      } else
        throw $e; // rethrow, commonerrorhandler megjeleniti szepen
      
    }
    
    $db->query("SET NAMES " . str_replace( '-', '', $this->config['charset'] ) );
    $db->SetFetchMode( ADODB_FETCH_ASSOC );
    
    return $this->instances['adodb'] = $db;
    
  }
  
  public function getSmarty() {
    
    if ( isset( $this->instances['smarty'] ) )
      return $this->instances['smarty'];
    
    if ( !( @include_once( $this->config['libpath'] . 'smarty.2620/Smarty.class.php') ) )
      // smarty not found under LIBPATH - try include_path location
      include_once( 'smarty.2620/Smarty.class.php');
    
    $this->instances['smarty'] = $smarty = new \Smarty();
    
    if ( $this->debug )
      $smarty->debugging = true;
    
    $smarty->_config[0]['vars'] = new \Springboard\SmartyLocalization( $this );
    $smarty->template_dir = $this->config['templatepath'];
    $smarty->compile_dir  = $this->config['cachepath'] . 'smarty';
    $smarty->plugins_dir  = array( 'plugins', $this->config['templatepath'] . 'Plugins' );
    $smarty->assign('bootstrap',    $this );
    
    $smarty->assign('ssl', SSL );
    
    if ( SSL ) {
      
      $smarty->assign('BASE_URI',   'https://' . $this->config['baseuri'] );
      $smarty->assign('STATIC_URI', 'https://' . $this->config['staticuri'] );
      $smarty->assign('FULL_URI',   'https://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'] );
      
    } else {
      
      $smarty->assign('BASE_URI',   'http://' . $this->config['baseuri'] );
      $smarty->assign('STATIC_URI', 'http://' . $this->config['staticuri'] );
      
      if ( !ISCLI )
        $smarty->assign('FULL_URI',   'http://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'] );
      
    }
    
    $smarty->assign('CURRENT_URI',      @$_SERVER['REQUEST_URI'] );
    $smarty->assign('VERSION',          $this->config['version'] );
    $smarty->assign('REQUEST_URI',      @$_SERVER['REQUEST_URI'] );
    
    $smarty->assign('language',         Springboard\Language::get() );
    $smarty->assign('module',           @$_REQUEST['module'] );
    
    if ( !ISCLI ) {
      
      $smarty->assign('sessionmessage', $this->getSession('message')->get('message') );
      $user = $this->getSession('user');
      if ( $user['id'] )
        $smarty->assign('member', $user );
      
    }
    
    return $smarty;
    
  }
  
  public function getLocalization() {
    
    if ( isset( $this->instances['localization'] ) )
      return $this->instances['localization'];
    
    return $this->instances['localization'] = new Springboard\Localization( $this );
    
  }
  
  public function getModel( $model ) {
    
    $db     = $this->getAdoDB();
    $loader = Springboard\Autoloader::getInstance();
    $class  = $loader->findExistingClass(
      'Model\\' . ucfirst( $model ),
      'Springboard\\Model'
    );
    
    return new $class( $this, $model );
    
  }
  
  public function getMailqueue( $nodb = false ) {
    
    $queue = new Springboard\Mailqueue( $this, $nodb );
    return $queue;
    
  }
  
  public function setupListing() {
    
    include_once( $this->config['libpath'] . 'listing/listing.php');
    include_once( $this->config['libpath'] . 'listing/listingdb.php');
    include_once( $this->config['libpath'] . 'listing/listingdb_adodb.php');
    include_once( $this->config['libpath'] . 'listing/listing_messages_' . Springboard\Language::get() . '.php');
    
  }
  
  public function setupHeaders() {
    
    if ( $this->headerssent or headers_sent() )
      return;
    
    header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");              // Date in the p
    header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); // always modifi
    header("Cache-Control: no-store, no-cache, must-revalidate");  // HTTP/1.1
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");                                    // HTTP/1.0
    header("Content-type: text/html; charset=utf-8");
    //header('X-UA-Compatible: IE=EmulateIE7');
    header('X-UA-Compatible: chrome=1', false ); // enable chrome frame, one can only dream its installed
    header('P3P: CP="CAO PSA OUR"');
    
    $this->headerssent = true;
    
  }
  
  protected function setupDebug() {
    
    if ( isset( $this->instances['debug'] ) )
      return $this->instances['debug'];
    
    if ( !$this->production and isset( $_REQUEST['d'] ) )
      $this->debug = true;
    elseif( $this->production and @$_REQUEST['d'] == 'damdebug' . $this->config['siteid'] )
      $this->debug = true;
    
    error_reporting( E_ALL );
    
    $debug = new Springboard\Debug( $this );
    $debug->setupErrorHandler();
    
    return $this->instances['debug'] = $debug;
    
  }
  
  public function getForm( $name, $target = null, $method = 'post', $db = null, $dbtype = null ) {
    
    if ( isset( $this->forms[ $name ] ) )
      return $this->forms[ $name ];
    
    if ( $this->debug )
      \Springboard\Debug::d(__METHOD__);
    
    if ( !class_exists('clonefish', false ) ) {
      
      include_once( $this->config['libpath'] . 'clonefish/constants.php');
      include_once( $this->config['libpath'] . 'clonefish/clonefish.php');
      include_once( $this->config['libpath'] . 'clonefish/messages_' . Springboard\Language::get() . '.php');
      
    }
    
    if ( $target === null )
      $target = @$_SERVER['REQUEST_URI'];
    
    $form = new clonefish( $name, $target, $method, $db, $dbtype );
    $form->jspath       = ( ( defined('SSL') and SSL )? 'https://': 'http://' ) . $this->config['staticuri'] . 'js/clonefish.js';
    $form->multibytesupport = "multibyteutf8";
    $form->codepage = "utf-8";
    
    //$form->configfilter = 'formconfigfilter'; // TODO
    //$form->loadConfig( CONFIGPATH . 'formdefault.ini' );
    
    //$form->submit       = l( 'sitewide', 'sitewide_ok' );
    
    return $this->forms[ $name ] = $form;
    
  }
  
  public function getSession( $namespace = 'default' ) {
    
    $this->setupSession();
    $basenamespace = $this->config['siteid'];
    return new Springboard\Session( $basenamespace, $namespace );
    
  }
  
  public function getAcl() {
    return new Springboard\Acl( $this );
  }
  
  public function getController( $module ) {
    
    if ( $this->debug )
      \Springboard\Debug::d(__METHOD__);
    
    $module       = ucfirst( $module );
    $loader       = Springboard\Autoloader::getInstance();
    $class        = $loader->findExistingClass(
      'Visitor\\' . $module . '\\Controller',
      'Visitor\\Controller',
      'Springboard\\Controller\\Visitor'
    );
    
    $controller = new $class( $this );
    
    return $controller;
    
  }
  
  public function getAdminController( $module ) {
    
    if ( $this->debug )
      \Springboard\Debug::d(__METHOD__);
    
    $module       = ucfirst( $module );
    $loader       = Springboard\Autoloader::getInstance();
    $class        = $loader->findExistingClass(
      'Admin\\' . $module,
      'Admin\\Controller',
      'Springboard\\Controller\\Admin'
    );
    
    $controller = new $class( $this );
    
    return $controller;
    
  }
  
  public function getFormController( $module, $target ) {
    
    if ( $this->debug )
      \Springboard\Debug::d(__METHOD__);
    
    // Visitor\Users\Form\Register
    $class = 'Visitor\\' . ucfirst( $module ) . '\\Form\\' . ucfirst( $target );
    return new $class( $this );
    
  }
  
  public function getAdminFormController( $module, $controller ) {
    
    if ( $this->debug )
      \Springboard\Debug::d(__METHOD__);
    
    $loader       = Springboard\Autoloader::getInstance();
    $class        = $loader->findExistingClass(
      'Admin\\' . ucfirst( $module ) . '\\Form',
      'Admin\\Form',
      'Springboard\\Controller\\Admin\\Form'
    );
    
    $controller = new $class( $this, $controller );
    
    return $controller;
    
  }
  
  public function getEncryption() {
    return new Springboard\Encryption( $this );
  }
  
  public function getCache( $key, $expireseconds = null, $ignorelanguage = false ) {
    
    $language = '';
    
    if ( $expireseconds === null )
      $expireseconds = $this->config['cacheseconds'];
    
    if ( !$ignorelanguage )
      $language = \Springboard\Language::get() . '-';
    
    $key = $language . $key;
    
    if ( isset( $this->caches[ $key ] ) )
      return $this->caches[ $key ];
    else {
      
      switch( $this->config['cache']['type'] ) {
        
        case 'file':
          $class = '\\Springboard\\Cache\\File';
          break;
        
        case 'redis':
          $class = '\\Springboard\\Cache\\Redis';
          break;
        
        case 'memcache':
          $class = '\\Springboard\\Cache\\Memcached';
          break;
        
        default:
          throw new \Exception('No such cache type known');
          break;
        
      }
      
      return $this->caches[ $key ] =
        new $class( $this, $key, $expireseconds )
      ;
      
    }
    
  }
  
}
