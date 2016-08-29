<?php
namespace AuthDirectories;

abstract class Base {
  protected $bootstrap;
  protected $organization;
  protected $directory;
  protected $directoryuser = array();
  protected $debug = false;
  protected $d;

  public function __construct( $bootstrap, $organization, $directory ) {
    $this->bootstrap    = $bootstrap;
    $this->organization = $organization;
    $this->directory    = $directory;

    if ( $bootstrap->config['debugauth'] ) {
      $this->debug = true;
      $this->d = \Springboard\Debug::getInstance();
    }
  }

  protected function l( $line ) {
    if ( !$this->debug )
      return;

    $this->d->log(
      false,
      'authdebug.txt',
      $line,
      false,
      true,
      true
    );
  }

  public function syncWithUser( $user ) {
    if ( !$this->directoryuser or !$user or !$user['id'] )
      return;

    $userModel = $this->bootstrap->getModel('users');
    $userModel->id = $user['id'];
    $userModel->updateRow( $this->directoryuser );
    $userModel->registerForSession();
  }

  abstract public function handle($directory);
  abstract public function handleLogin( $user, $password );

}
