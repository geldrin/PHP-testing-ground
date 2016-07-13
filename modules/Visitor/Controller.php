<?php
namespace Visitor;

class Controller extends \Springboard\Controller\Visitor {
  public $organization;
  protected $queue;

  public function init() {

    if ( $this->bootstrap->inMaintenance('site') ) {
      header('HTTP/1.1 500 Internal Server Error');
      $this->smartyOutput('Visitor/sitemaintenance.tpl');
      return;
    }

    // mert itt redirectelunk a megfelelo domainre, csak utana akarjuk
    // https-re forcolni a domaint
    // nem szabad hogy session induljon ez elott
    $this->setupOrganization();

    if (
         $this->bootstrap->ssl and
         $this->bootstrap->config['forcesecuremaxage']
       )
      $this->headeroptions['Strict-Transport-Security'] =
        'max-age=' . $this->bootstrap->config['forcesecuremaxage']
      ;

    if ( in_array( $this->module, array('api', 'jsonapi') ) ) // az api ->authenticate mindig kezeli
      return parent::init();

    $skipsinglelogincheck = array(
      'users' => array(
        'ping' => true,
      ),
      'recordings' => array(
        'checkstreamaccess'       => true,
        'securecheckstreamaccess' => true,
      ),
      'live' => array(
        'checkstreamaccess'       => true,
        'securecheckstreamaccess' => true,
      ),
      'combine' => array(
        'css' => true,
        'js'  => true,
      ),
    );

    foreach( $skipsinglelogincheck as $module => $actions ) {

      if ( $this->module == $module and isset( $actions[ $this->action ] ) )
        return parent::init();

    }

    $this->handleLogin();
    $this->handleAutologin();
    $this->debugLogUsers();
    $this->handleSingleLoginUsers();
    parent::init();

  }

  public function route() {

    $action  = str_replace('submit', '', $this->action );
    $method  = $action . 'Action';
    $found   = '';

    if ( method_exists( $this, $method ) )
      $found = 'method';
    elseif ( array_key_exists( $action, $this->forms ) )
      $found = 'form';
    elseif ( array_key_exists( $action, $this->paging ) )
      $found = 'paging';
    else
      return $this->redirectToController('contents', 'http404');

    if ( $found and !isset( $this->permissions[ $action ] ) )
      throw new \Springboard\Exception('No permission setting found for action: ' . $action );

    $this->toSmarty['module'] = $this->module;
    $this->toSmarty['action'] = $action;

    // TODO modositani uj privilegium rendszerhez
    if ( $this->bootstrap->config['usedynamicprivileges'] )
      $this->checkControllerPrivilege( $this->module, $action );
    else
      $this->checkAccess( $this->permissions[ $action ] );

    switch( $found ) {

      case 'method':

        if ( $this->bootstrap->debug )
          \Springboard\Debug::d('Routing in controller to method', $method );

        $this->$method();
        break;

      case 'form':

        if ( $this->bootstrap->debug )
          \Springboard\Debug::d('Routing in controller to form', $this->forms[ $action ] );

        $formcontroller = $this->forms[ $action ];
        if ( is_string( $formcontroller ) )
          $formcontroller = new $formcontroller(
            $this->bootstrap, $this
          );

        $formcontroller->route();
        break;

      case 'paging':

        if ( $this->bootstrap->debug )
          \Springboard\Debug::d('Routing in controller to paging', $this->paging[ $action ] );

        $pagingcontroller = $this->paging[ $action ];
        if ( is_string( $pagingcontroller ) )
          $pagingcontroller = new $pagingcontroller(
            $this->bootstrap, $this
          );

        $pagingcontroller->route();
        break;

    }

  }

  public function checkControllerPrivilege( $module, $action ) {
    if ( !\Springboard\Session::exists() ) {
      $roleid = $this->getPublicRoleID();
    } else {
      $user = $this->bootstrap->getSession('user');
      if ( $user['userroleid'] ) // be van lepve es van roleid
        $roleid = $user['userroleid'];
      elseif ( $user['id'] ) // be van lepve de nincs roleid
        throw new \Exception('invalid userroleid for user ' . $user['id'] );
      else // nincs belepve
        $roleid = $this->getPublicRoleID();
    }

    $privileges = \Model\Users::getPrivilegesForRoleID( $roleid );
    $key = $module . '_' . $action;
    if ( isset( $privileges[ $key ] ) )
      return true;

    // TODO eldonteni mi tortenjen ha nincs privilegium
    // hogy redirectelunk loginra?
  }

  private function getPublicRoleID() {
    $userModel = $this->bootstrap->getModel('users');
    $roleid = $userModel->getRoleIDByName('public');
    if ( !$roleid )
      throw new \Exception('no public role exists');
  }

  public function handleAutologin() {

    if ( !isset( $_COOKIE['autologin'] ) )
      return;

    $user = $this->bootstrap->getSession('user');
    if ( $user['id'] )
      return;

    $userModel   = $this->bootstrap->getModel('users');
    $ipaddresses = $this->getIPAddress(true);
    $valid       = $userModel->loginFromCookie(
      $this->organization['id'], $ipaddresses
    );

    if ( !$valid )
      return $userModel->unsetAutoLoginCookie( $this->bootstrap->ssl );

    $this->toSmarty['member'] = $userModel->row;
    $this->logUserLogin('AUTO-LOGIN');

  }

  public function handleLogin() {

    if ( empty( $this->organization['authtypes'] ) )
      return;

    $ipaddresses = $this->getIPAddress(true);
    foreach( $this->organization['authtypes'] as $authtype ) {
      if ( $authtype['type'] === 'local' or $authtype['isuserinitiated'] )
        continue;

      $class = "\\AuthTypes\\" . ucfirst( strtolower( $authtype['type'] ) );
      $auth = new $class( $this->bootstrap, $this->organization, $ipaddresses );

      try {

        $ret = $auth->handleType( $authtype, $this->module, $this->action );
        if ( $ret === true ) {
          $user = $this->bootstrap->getSession('user');
          $this->toSmarty['member'] = $user->toArray();
          $this->logUserLogin( $authtype['type'] . '-LOGIN');
        }

      } catch( \AuthTypes\Exception $e ) {

        $d    = \Springboard\Debug::getInstance();
        $line =
          $e->getMessage() . "\n" .
          var_export( $e->info, true ) . "\n" .
          \Springboard\Debug::formatBacktrace( $e->getTrace() ) . "\n"
        ;
        $d->log(false, 'ssologin.txt', $line);

        if ($e->redirectmessage)
          $this->redirectWithMessage(
            $e->redirecturl,
            $e->redirectmessage,
            $e->redirectparams
          );
        else
          $this->redirect(
            $e->redirecturl,
            $e->redirectparams
          );

      }

    }

  }

  public function handleSingleLoginUsers() {

    $user = $this->bootstrap->getSession('user');

    if ( $user['id'] and !$user['isadmin'] ) {

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
        $this->regenerateSessionID();

        $l = $this->bootstrap->getLocalization();
        $this->redirectWithMessage('users/login', $l('users', 'timestampdisabled') );

      }

      if ( $userModel->row['issingleloginenforced'] ) {

        if ( !$userModel->checkSingleLoginUsers() ) {

          $user->clear();
          $this->regenerateSessionID();

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

    $host         = $_SERVER['SERVER_NAME'];
    $orgModel     = $this->bootstrap->getModel('organizations');
    $organization = $orgModel->getOrganizationByDomain( $host, false );

    if ( !$organization ) {

      $fallbackurl = @$this->bootstrap->config['organizationfallbackurl'];

      if ( !$fallbackurl )
        die();
      else
        $this->redirect( $fallbackurl );

    }

    $this->impersonateOrganization( $organization );
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
    // azert nem hmac (mert amugy message authenticity-t nezunk) mert a flash
    // a kliens oldalan generalja, igy mindenfele keppen meg tudja hamisitani
    // a user ha nagyon akarja, _NAGYON_ fontos hogy itt kulon seed legyen
    // pont emiatt
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

  public function handleUserAccess( $access ) {

    if ( $access === true )
      return;

    $errors = array(
      'registrationrestricted',
      'departmentorgrouprestricted',
    );

    $user = $this->bootstrap->getSession('user');
    if ( $user['id'] or !in_array( $access, $errors, true ) )
      $this->redirectToController('contents', $access );

    $l = $this->bootstrap->getLocalization();
    $this->redirectWithMessage(
      'users/login',
      $l('', 'nopermission_message_' . $access ),
      array('forward' => $_SERVER['REQUEST_URI'] )
    );

  }

  public function logUserLogin( $ident, $ipaddress = null ) {

    if ( !$ipaddress ) {

      $ipaddresses = $this->getIPAddress(true);
      $ipaddress   = '';
      foreach( $ipaddresses as $key => $value )
        $ipaddress .= ' ' . $key . ': ' . $value;

    }

    $d = \Springboard\Debug::getInstance();
    $d->log(
      false,
      'login.txt',
      $ident . ' SESSIONID: ' . session_id() . ' IPADDRESS:' . $ipaddress
    );

  }

  public function sendOrganizationHTMLEmail( $email, $subject, $body, $values = array() ) {

    $olderrorsto = $this->bootstrap->config['mail']['errorsto'];
    $this->bootstrap->config['mail']['errorsto'] = $this->organization['mailerrorto'];
    if ( !$this->queue )
      $this->queue = $this->bootstrap->getMailqueue();

    $this->queue->sendHTMLEmail( $email, $subject, $body, $values );
    $this->bootstrap->config['mail']['errorsto'] = $olderrorsto;

  }

  public function regenerateSessionID() {

    if ( !$this->bootstrap->sessionstarted )
      return;

    /*
     * ha a session cookie domain egy olyan domainrol erkezik ami a subdomain
     * cookie domainjet is lefedi (peldaul a .conforg.videosquare.eu-t lefedi a
     * .videosquare.eu) akkor az elsonek letrejott cookie lesz az ami szamit
     * igy amikor a regenerate_id(false)-nel a PHP elkuldi a Set-Cookie headert
     * akkor arra a cookie domainre kuldi el amit nem hasznalunk es ineffektiv
     * igy nem lep ervenybe, erre nincs megoldas, arra viszont igen hogy az adatok
     * megsemmisuljenek legalabb, mert akkor a redisben tarolt session adatokat
     * dobjuk el konkretan, ezert kell a regenerate_id(true) hogy ezt jelezzuk
     * mert kulonben az adatok ott maradnak a regi sessionid alatt amit a
     * tovabbra is hasznal a browser
     * ezert van az hogy session_regenerate_id(false) hivas utan latszolag
     * nem valtoznak a session adatok a regi sessionid-ben
     * ketto megoldas van:
     *  - vagy mindig destroyolni a "regi" sessiont (session_regenerate_id(true))
     *  - vagy bezarni a sessiont, ezaltal elmentve a valtoztatasokat benne
     *    majd ujra megnyitni es akkor megprobalni rotalni az id-t
     *    igy a "regi" sessionid-ben ugyanaz lesz mint az ujban ami nem biztos
     *    hogy ervenybe lep
     *
     */

    if ( !session_regenerate_id(true) ) // logoljuk ha nem sikerul
      throw new \Exception("session_regenerate_id() returned false!");

  }

  public function impersonateOrganization( &$organization ) {

    $this->bootstrap->config['version'] = '_v' . md5(
      $this->bootstrap->config['version'] . $organization['lastmodifiedtimestamp']
    );

    $baseuri   = $this->bootstrap->scheme . $organization['domain'] . '/';
    $staticuri = $this->bootstrap->scheme . $organization['staticdomain'] . '/';

    $this->application->config['combine']['domains'][] = $organization['domain'];
    $this->application->config['combine']['domains'][] = $organization['staticdomain'];

    $this->toSmarty['supportemail'] = $this->bootstrap->config['mail']['fromemail'] =
      $this->application->config['mail']['fromemail'] = $organization['supportemail']
    ;

    $this->toSmarty['organization']   = $organization;
    $this->bootstrap->baseuri         =
    $this->toSmarty['BASE_URI']       = $organization['baseuri']   = $baseuri;
    $this->bootstrap->staticuri       =
    $this->toSmarty['STATIC_URI']     = $organization['staticuri'] = $staticuri;
    $this->bootstrap->validatesession = (bool)$organization['issessionvalidationenabled'];
    $this->bootstrap->config['cookiedomain'] = $organization['cookiedomain'];
    $this->bootstrap->config['sessionidentifier'] = $organization['domain'];

  }

  public function fetchSmarty( $template ) {
    if ( !isset( $this->toSmarty['layoutheader'] ) )
      $this->toSmarty['layoutheader'] = $this->getLayoutDefault('header');

    if ( !isset( $this->toSmarty['layoutfooter'] ) )
      $this->toSmarty['layoutfooter'] = $this->getLayoutDefault('footer');

    $smarty = $this->bootstrap->getSmarty();
    $smarty->assign( $this->toSmarty );
    return $smarty->fetch( $template );
  }

  private function getLayoutDefault( $type ) {
    if ( $this->organization[ 'layout' . $type ] )
      return $this->organization[ 'layout' . $type ];

    return 'Visitor/_layout_' . $type . '.tpl';
  }

  public function getHelp( $helpkey, $language = null ) {
    if ( $language === null )
      $language = \Springboard\Language::get();

    $helpModel = $this->bootstrap->getModel('help_contents');
    return $helpModel->selectByShortname(
      $helpkey,
      $language,
      $this->organization['id']
    );
  }
}
