<?php
namespace AuthTypes;

abstract class Base {
  protected $bootstrap;
  protected $organization;
  protected $ipaddresses;
  protected $directory; // az AuthDirectory ami kotodik az adott AuthTypehoz
  protected $debug = false;
  protected $d;

  protected $skip = array(
    'users' => array(
      'logout' => true,
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
    'contents' => array(
      '*' => true,
    ),
  );

  public function __construct( $bootstrap, $organization, $ipaddresses ) {
    $this->bootstrap    = $bootstrap;
    $this->organization = $organization;
    $this->ipaddresses  = $ipaddresses;

    if ( $bootstrap->config['debugauth'] ) {
      $this->debug = true;
      $this->d = \Springboard\Debug::getInstance();
    }
  }

  protected function l( $line ) {
    if ( !$this->debug )
      return;

    $line .= "\nSID: " . session_id();
    $this->d->log(
      false,
      'authdebug.txt',
      $line,
      false,
      true,
      true
    );
  }

  protected function shouldSkip( $type, $module, $action ) {
    if ( isset( $this->skip[ $module ] ) ) {
      $actions = $this->skip[ $module ];

      // adott modul adott actionje, vagy wildcard minden action
      if ( isset( $actions[ $action ] ) or isset( $actions['*'] ) ) {
        $this->l("types/base::shouldSkip skipping $type for module: $module action: $action");
        return true;
      }

    }

    $user = $this->bootstrap->getSession('user');

    // ha be van lepve a user, de nem az adott tipus leptette be akkor valoszinu
    // hogy a fallback metodus leptette be, engedjuk
    if ( $user['id'] and !$user[ $type . 'login' ] ) {
      $this->l("types/base::shouldSkip user already logged in but not by type $type for module: $module action: $action");
      return true;
    }

    $this->l("types/base::shouldSkip not skipping for type $type for module: $module action: $action");

    return false;
  }

  protected function markUser( $authtype ) {
    $user = $this->bootstrap->getSession('user');
    $user[ $authtype['type'] . 'login' ] = time();
    $user['isuserinitiated'] = $authtype['isuserinitiated'];
  }

  protected function shouldReauth( $authtype ) {
    $user    = $this->bootstrap->getSession('user');
    $timeout = $this->bootstrap->config['directoryreauthminutes'] * 60;
    if (
         !$user[ $authtype['type'] . 'login' ] or
         $user[ $authtype['type'] . 'login' ] + $timeout < time()
       )
      return true;
    else
      return false;
  }

  protected function findAndMarkUser( $authtype, $externalid ) {
    $userModel = $this->bootstrap->getModel('users');
    $valid     = $userModel->loginFromExternalID(
      $externalid, $authtype['type'], $this->organization['id'], $this->ipaddresses
    );

    if ( $valid === null )
      return null;
    else
      return false;

    $this->markUser( $authtype );
    return true;
  }

  // viszateresi ertek azt akarja jelezni hogy tortent e beleptetes vagy nem
  public function handleType( $authtype, $module, $action ) {
    if ( $this->shouldSkip( $authtype['type'], $module, $action ) )
      return false;

    return $this->handle( $authtype, $module, $action );
  }

  // csak userinitiated authtype-ok eseten hivodik meg a
  // users/login form onComplete-ben
  // non-null return value -> handle-elve lett az authtype
  // true -> be lett leptetve
  public function handleForm( $authtype, $form ) {
    throw new \Exception("handleForm not implemented for $authtype");
  }

  protected function getDirectory( $directory ) {
    $class = "\\AuthDirectories\\" . ucfirst( $directory['type'] );
    return new $class( $this->bootstrap, $this->organization, $directory );
  }

  abstract public function handle( $authtype, $module, $action );
  abstract protected function handleAuthDirectory( $externalid );

}
