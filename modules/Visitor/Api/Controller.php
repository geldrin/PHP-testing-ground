<?php
namespace Visitor\Api;

class Controller extends \Visitor\Controller {
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
    
    $result = array(
      'result' => 'OK'
    );
    
    try {
      
      $email    = $this->application->getParameter('email');
      $password = $this->application->getParameter('password');
      $module   = $this->application->getParameter('_module');
      $method   = $this->application->getParameter('method');
      
      if ( $module != 'users' and $method != 'authenticate' ) {
        
        $loggedin = call_user_func_array( array(
            $this->bootstrap->getController('users'),
            'authenticateAction'
          ),
          array(
            $email,
            $password
          )
        );
        
        if ( !$loggedin )
          throw new \Exception('Invalid user!');
        
      }
      
      $this->format = $this->validateParameter('format', $this->formats );
      $this->layer  = $this->validateParameter('layer', $this->layers );
      $this->module = $this->getModule();
      $this->callMethod();
      
      $result['data'] = $this->data;
      
    } catch( \Exception $e ) {
      
      $result['result'] = 'ERR';
      $result['data']   = $e->getMessage();
      $debug            = \Springboard\Debug::getInstance();
      
      $message =
        "API exception caught: " . $e->getMessage() . " --- '" . get_class( $e ) . "'\n" .
        "  Backtrace:\n" . \Springboard\Debug::formatBacktrace( $e->getTrace() ) .
        "\n  Info:\n" . \Springboard\Debug::getRequestInformation( 2 )
      ;
      
      $debug->log( false, 'api.txt', $message, true );
      
    }
    
    if ( $this->format == 'json' )
      $this->jsonoutput( $result );
    
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
    
    $module = $this->application->getParameter('_module');
    $ret    = null;
    
    if ( $module and $this->layer == 'model' ) {
      
      $ret = $this->bootstrap->getModel( $module );
      if ( !isset( $ret->apisignature ) )
        $ret = null;
      
    } elseif ( $module and $this->layer == 'controller' ) {
      
      $ret = $this->bootstrap->getController( $module );
      
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
    
    if ( $this->layer == 'controller' )
      $method .= 'Action';
    
    return $this->data = call_user_func_array( array( $this->module, $method ), $parameters );
    
  }
  
  public function idValidator( $parameter, $configuration ) {
    
    $id            = $this->application->getNumericParameter( $parameter );
    $defaults      = array('required' => true );
    $configuration = array_merge( $defaults, $configuration );
    
    if ( $id <= 0 and $configuration['required'] )
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
  
  public function fileValidator( $parameter, $configuration ) {
    
    if ( !isset( $_FILES[ $parameter ] ) or $_FILES[ $parameter ]['error'] != 0 )
      throw new \Exception(
        'Upload failed. Information: ' . var_export( @$_FILES[ $parameter ], true )
      );
    
    return $_FILES[ $parameter ];
    
  }
  
  public function userValidator( $parameter, $configuration ) {
    
    if ( !isset( $configuration['permission'] ) )
      throw new \Exception('Undefined permission for user validation!');
    
    if ( $configuration['permission'] == 'public' )
      return true;
    
    $user = $this->bootstrap->getSession('user');
    
    if ( $configuration['permission'] == 'member' and $user['id'] )
      return $user;
    
    if ( !$user['is' . strtolower( $configuration['permission'] ) ] )
      throw new \Exception('Access denied, not enough permission');
    
    if ( isset( $configuration['impersonatefromparameter'] ) ) {
      
      $id        = $this->application->getNumericParameter(
        $configuration['impersonatefromparameter']
      );
      $userModel = $this->modelIDCheck('users', $id, false );
      
      if ( !$userModel )
        throw new \Exception('No user found with id: ' . $id );
      
      $userModel->registerForSession();
      
    }
    
    return $user;
    
  }
  
}
