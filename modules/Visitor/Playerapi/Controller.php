<?php
namespace Visitor\Playerapi;

class Controller extends \Visitor\Api\Controller {
  public $parameters = array();

  public function init() {
    $parameters = $this->application->getParameter('parameters');

    if ( !$parameters )
      $this->jsonOutput( array(
          'result' => 'ERR',
          'data'   => 'no parameters',
        )
      );

    $this->parameters = json_decode( $parameters, true );
    if ( !$this->parameters )
      $this->jsonOutput( array(
          'result' => 'ERR',
          'data'   => 'invalid parameters',
        )
      );
  }

  public function getParameter( $key, $defaultvalue = null ) {
    if ( $this->parameters and isset( $this->parameters[ $key ] ) )
      return $this->parameters[ $key ];

    return $defaultvalue;
  }

  public function getNumericParameter( $key, $defaultvalue = null, $isfloat = false ) {
    if ( $isfloat )
      return floatval( $this->getParameter( $key, $defaultvalue ) );
    else
      return intval( $this->getParameter( $key, $defaultvalue ) );
  }

  public function getModule( $module = null ) {
    $module = $this->getParameter('module');
    return parent::getModule( $module );
  }
}
