<?php
namespace AuthDirectories;

abstract class Base {
  protected $bootstrap;
  protected $organization;
  protected $directory;
  protected $directoryuser = array();

  public function __construct( $bootstrap, $organization, $directory ) {
    $this->bootstrap    = $bootstrap;
    $this->organization = $organization;
    $this->directory    = $directory;
  }

  public function syncWithUser( $user ) {
    if ( !$this->directoryuser or !$user )
      return;

    $userModel = $this->bootstrap->getModel('users');
    $userModel->id = $user['id'];
    $userModel->updateRow( $this->directoryuser );
    $userModel->registerForSession();
  }

  abstract public function handle($directory);

}
