<?php
namespace Visitor;

class Controller extends \Springboard\Controller\Visitor {
  public $organization;
  
  public function init() {
    $this->setupOrganization();
    $this->debugLogUsers();
    
    $skipsinglelogincheck = array(
      'users' => array(
        'ping',
      ),
      'recordings' => array(
        'checkstreamaccess',
        'securecheckstreamaccess',
      ),
      'live' => array(
        'checkstreamaccess',
        'securecheckstreamaccess',
      ),
    );
    
    if ( $this->module == 'api' ) // az api ->authenticate mindig kezeli
      return parent::init();
    
    foreach( $skipsinglelogincheck as $module => $actions ) {
      
      if ( $this->module == $module and in_array( $this->action, $actions ) )
        return parent::init();
      
    }
    
    $this->handleSingleLoginUsers();
    parent::init();
    
  }
  
  public function handleSingleLoginUsers() {
    
    $user = $this->bootstrap->getSession('user');

    if ( $user['id'] ) {

      // mindig adatbazisbol kerdezzuk le a usert, mivel
      // elofordulhat, hogy menetkozben akarjuk a usert
      // kitiltani, az pedig a session alapu ellenorzesnel
      // nem sikerulne
      $userModel = $this->bootstrap->getModel('users');
      $userModel->select( $user['id'] );
      
      if (
           $userModel->row['timestampdisabledafter'] and
           strtotime( $userModel->row['timestampdisabledafter'] ) < time()
         ) {
        
        $user->clear();
        $l = $this->bootstrap->getLocalization();
        $this->redirectWithMessage('users/login', $l('users', 'timestampdisabled') );
        
      }
      
      if ( $userModel->row['issingleloginenforced'] ) {

        if ( !$userModel->checkSingleLoginUsers() ) {
          $user->clear();
          $l = $this->bootstrap->getLocalization();
          $this->redirectWithMessage('users/login', sprintf(
            $l('users', 'loggedout_sessionexpired'),
            ceil( $this->bootstrap->config['sessiontimeout'] / 60 )
          ) );
        }
        else
          $userModel->updateSessionInformation();

      }

    }
  
  }

  public function redirectToMainDomain() {}
  
  public function setupOrganization() {
    
    $host = $_SERVER['SERVER_NAME'];
    
    $cache = $this->bootstrap->getCache( 'organizations-' . $host, null );
    if ( $cache->expired() ) {
      
      $orgModel = $this->bootstrap->getModel('organizations');
      if ( !$orgModel->checkDomain( $host ) ) {
        
        $fallbackurl = @$this->bootstrap->config['organizationfallbackurl'];
        
        if ( !$fallbackurl )
          die();
        else
          $this->redirect( $fallbackurl );
        
      }
      
      $organization = $orgModel->row;
      $l            = $this->bootstrap->getLocalization();
      $languages    = $l->getLov('languages');
      $languagekeys = explode(',', $organization['languages'] );
      $organization['languages'] = array();
      
      foreach( $languagekeys as $language )
        $organization['languages'][ $language ] = $languages[ $language ];
      
      $cache->put( $organization );
      $staticcache = $this->bootstrap->getCache(
        'organizations-' . $organization['staticdomain'],
        null
      );
      $staticcache->put( $organization );
      
    } else
      $organization = $cache->get();
    
    $baseuri   = $this->bootstrap->scheme . $organization['domain'] . '/';
    $staticuri = $this->bootstrap->scheme . $organization['staticdomain'] . '/';
    
    $this->application->config['combine']['domains'][] = $organization['domain'];
    $this->application->config['combine']['domains'][] = $organization['staticdomain'];
    
    $this->toSmarty['supportemail'] = $this->bootstrap->config['mail']['fromemail'] =
      $this->application->config['mail']['fromemail'] = $organization['supportemail']
    ;
    $this->toSmarty['organization']   = $this->organization        = $organization;
    $this->bootstrap->baseuri         =
    $this->toSmarty['BASE_URI']       = $organization['baseuri']   = $baseuri;
    $this->bootstrap->staticuri       =
    $this->toSmarty['STATIC_URI']     = $organization['staticuri'] = $staticuri;
    $this->bootstrap->validatesession = (bool)$organization['issessionvalidationenabled'];
    $this->bootstrap->config['cookiedomain'] = $organization['cookiedomain'];
    
    $this->organization = $organization;
    
  }
  
  public function handleAccessFailure( $permission ) {
    
    if ( $permission == 'member' )
      return parent::handleAccessFailure( $permission );
    
    $pos = strpos( $permission, '|' );
    if ( $pos !== false )
      $permission = substr( $permission, 0, $pos );
    
    header('HTTP/1.0 403 Forbidden');
    $this->redirectToController('contents', 'nopermission' . $permission );
    
  }
  
  public function modelOrganizationAndIDCheck( $table, $id, $redirectto = 'index' ) {
    
    if ( $id <= 0 ) {
      
      if ( $redirectto !== false )
        $this->redirect( $redirectto );
      else
        return false;
      
    }
    
    $model = $this->bootstrap->getModel( $table );
    $model->addFilter('id', $id );
    $model->addFilter('organizationid', $this->organization['id'] );
    
    $row = $model->getRow();
    
    if ( empty( $row ) and $redirectto !== false )
      $this->redirect( $redirectto );
    elseif ( empty( $row ) )
      return false;
    
    $model->id  = $row['id'];
    $model->row = $row;
    
    return $model;
    
  }
  
  public function modelOrganizationAndUserIDCheck( $table, $id, $redirectto = 'index' ) {
    
    $user = $this->bootstrap->getSession('user');
    
    if ( $id <= 0 or !isset( $user['id'] ) ) {
      
      if ( $redirectto !== false )
        $this->redirect( $redirectto );
      else
        return false;
      
    }
    
    $model = $this->bootstrap->getModel( $table );
    $model->addFilter('id', $id );
    
    if ( $user['iseditor'] or $user['isclientadmin'] )
      $model->addTextFilter("
        userid = '" . $user['id'] . "' OR
        organizationid = '" . $user['organizationid'] . "'
      ");
    else
      $model->addFilter('userid', $user['id'] );
    
    $row = $model->getRow();
    
    if ( empty( $row ) and $redirectto !== false )
      $this->redirect( $redirectto );
    elseif ( empty( $row ) )
      return false;
    
    $model->id  = $row['id'];
    $model->row = $row;
    
    return $model;
    
  }
  
  public function output( $string, $disablegzip = false, $disablekill = false ) {
    
    if ( $this->bootstrap->overridedisablegzip !== null )
      $disablegzip = $this->bootstrap->overridedisablegzip;
    
    parent::output( $string, $disablegzip, $disablekill );
    
  }
  
  protected function getBaseURI( $withschema = true ) {
    
    $url = $this->organization['domain'] . '/';
    
    if ( $withschema )
      $url = $this->bootstrap->scheme . $url;
    
    return $url;
    
  }
  
  public function getHashForFlash( $string ) {
    return md5( $string . $this->bootstrap->config['flashhashseed'] );
  }
  
  public function checkHashFromFlash( $string, $hash ) {
    $actualhash = $this->getHashForFlash( $string );
    return $hash == $actualhash;
  }
  
  public function getFlashParameters( $parameters ) {
    
    $ret = array(
      'parameters' => json_encode( $parameters, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ),
    );
    
    $ret['hash'] = $this->getHashForFlash( $ret['parameters'] );
    return $ret;
    
  }
  
  public function getIPAddress( $extended = null ) {
    
    if ( $extended ) {
      
      $ipaddresses = array(
        'REMOTE_ADDR' => $_SERVER['REMOTE_ADDR'],
      );
      
      if ( @$_SERVER['HTTP_VIA'] )
        $ipaddresses['VIA'] = $_SERVER['HTTP_VIA'];
      if ( @$_SERVER['HTTP_X_FORWARDED_FOR'] )
        $ipaddresses['FORWARDED_FOR'] = $_SERVER['HTTP_X_FORWARDED_FOR'];
      
      return $ipaddresses;
      
    }
    
    return $_SERVER['REMOTE_ADDR'];
    
  }
  
  public function debugLogUsers() {
    
    $user = $this->bootstrap->getSession('user');
    if ( !$user or !$user['id'] )
      return;
      
    foreach( $this->bootstrap->config['debugloguserids'] as $userid ) {
      
      if ( $user['id'] != $userid )
        continue;
      
      $d = \Springboard\Debug::getInstance();
      $d->log(
        false,
        'userdebuglog.txt',
        "USER DEBUG LOG FOR USERID $userid\n" .
        \Springboard\Debug::getRequestInformation(2)
      );
      break;
      
    }
    
  }
  
}
