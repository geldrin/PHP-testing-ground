<?php
namespace Visitor\Jsonapi;

class Controller extends \Visitor\Api\Controller {
  public $parameters = array();

  /*
  /jsonapi?parameters={"format":"json","layer":"model","module":"recordings","method":"getRow","id":"12"}&hash=123567890abcdef
  */

  public function init() {

    $parameters = $this->application->getParameter('parameters');
    $hash       = $this->application->getParameter('hash');

    if ( $hash and $parameters and !$this->checkPlayerSignature( $parameters, $hash ) )
      $this->jsonOutput( array(
          'result' => 'ERR',
          'data'   => 'invalid hash specified',
        )
      );

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

  public function tryAuthenticate() {

    $method = $this->getParameter('method');

    if (
         isset( $this->module->apisignature[ $method ] ) and
         isset( $this->module->apisignature[ $method ]['hashrequired'] ) and
         !$this->module->apisignature[ $method ]['hashrequired']
       )
      return;

    if ( !$this->application->getParameter('hash') )
      throw new \Visitor\Api\ApiException('Hash required!', true, false );

    parent::tryAuthenticate();

  }

  public function getModule( $module = null ) {
    $module = $this->getParameter('module');
    return parent::getModule( $module );
  }

}
