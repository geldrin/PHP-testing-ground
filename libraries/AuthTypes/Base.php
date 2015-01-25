<?php
namespace AuthTypes;

abstract class Base {
  protected $bootstrap;
  protected $organization;
  protected $ipaddresses;
  protected $directory; // az AuthDirectory ami kotodik az adott AuthTypehoz

  protected $skip = array(
    'users' => array(
      'login'  => true,
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
  }

  protected function shouldSkip( $type, $module, $action ) {
    foreach( $this->skip as $currmodule => $curractions ) {

      if ( $module == $currmodule and isset( $curractions[ $action ] ) )
        return true;

      // wildcard
      if ( $module == $currmodule and isset( $curractions['*'] ) )
        return true;
    }

    $user = $this->bootstrap->getSession('user');

    // ha be van lepve a user, de nem az adott tipus leptette be akkor valoszinu
    // hogy a fallback metodus leptette be, engedjuk
    if ( $user['id'] and !$user[ $type . 'login' ] )
      return true;

    return false;
  }

  protected function markUser( $authtype ) {
    $user = $this->bootstrap->getSession('user');
    $user[ $authtype['type'] . 'login' ] = time();
  }

  protected function shouldReauth() {
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
  public function handleType($authtype, $module, $action) {
    if ( $this->shouldSkip( $authtype['type'], $module, $action ) )
      return false;

    return $this->handle( $authtype );
  }

  protected function getDirectory( $directory ) {
    $class = "\\AuthDirectories\\" . ucfirst( $directory['type'] );
    return new $class( $this->bootstrap, $this->organization, $directory );
  }

  abstract public function handle($authtype);
  abstract protected function handleAuthDirectory($externalid);

}
