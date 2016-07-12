<?php
namespace Visitor;
class Roles {
  protected $bootstrap;

  private $privileges = array();

  public function __construct( $bootstrap ) {
    $this->bootstrap = $bootstrap;
  }

  public function init( $privileges ) {
    $this->privileges = $privileges;
  }

  public function hasPrivilege( $privilege ) {
    return isset( $this->privileges[ $privilege ] );
  }

}
