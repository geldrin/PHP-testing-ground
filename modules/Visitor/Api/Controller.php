<?php
namespace Visitor\Api;

class Controller extends \Springboard\Controller\Visitor {
  public $formats = array('json');
  public $format  = 'json';
  public $layers  = array( 'model', 'controller' );
  public $layer;
  public $module;
  public $data;
  
  /*
  /api?format=json&layer=model&module=recordings&method=getRow&id=12
  /api?format=json&layer=model&module=recordings&method=upload&title=title&filepath=filepath
  */
  public function route() {
    
    $this->format = $this->validateParameter('format', $this->formats );
    $this->layer  = $this->validateParameter('layer', $this->layers );
    $this->module = $this->getModule();
    
    $this->callMethod();
    
  }
  
  public function validateParameter( $name, $possiblevalues ) {
    
    $value = $this->application->getParameter( $name );
    if ( !in_array( $value, $possiblevalues ) )
      throw new \Exception(
        'Invalid parameter: ' . $name . ', possible values: "' .
        implode('", "', $possiblevalues ) . '"'
      );
    
    return $value;
    
  }
  
  public function getModule() {
    
    $module = $this->application->getParameter('apimodule');
    $ret    = null;
    
    if ( $module and $this->layer == 'model' ) {
      
      $ret = $this->bootstrap->getModel( $module );
      if ( !isset( $ret->apisignature ) )
        $ret = null;
      
    }
    
    if ( !$ret or !$module )
      throw new \Exception('Invalid parameter: module, no such module');
    
    return $ret;
    
  }
  
  public function callMethod() {
    
    $method = $this->application->getParameter('method');
    
    if ( !$method )
      throw new \Exception('No method specified');
    
    if ( !array_key_exists( $method, $this->module->apisignature ) )
      throw new \Exception('No such method found');
    
    $parameters = array();
    foreach( $this->module->apisignature[ $method ] as $parameter => $validator ) {
      
      $validatormethod = strtolower( $validator['type'] ) . 'Validator';
      $parameters[]    = $this->$validatormethod( $parameter, $validator );
      
    }
    
    $data = call_user_func_array( array( $this->module, $method ), $parameters );
    $this->display( $data );
    
  }
  
  public function idValidator( $parameter, $configuration ) {
    
    $id = $this->application->getNumericParameter( $parameter );
    
    if ( $id <= 0 )
      throw new \Exception('Invalid parameter: ' . $parameter );
    
    return $id;
    
  }
  
  public function stringValidator( $parameter, $configuration ) {
    
    $defaults = array( 'value' => '', 'required' => true );
    $configuration = array_merge( $defaults, $configuration );
    
    $value = $this->application->getParameter( $parameter, $configuration['value'] );
    $value = trim( $value );
    
    if ( !$value and !$configuration['required'] )
      return $value;
    elseif ( !$value )
      throw new \Exception('Empty parameter: ' . $parameter );
    
    return $value;
    
  }
  
  public function display( $data ) {
    
    if ( $this->format == 'json' )
      $this->jsonoutput( $data );
    
    echo "<pre>";
    var_dump( $data );
    
  }
  
}
