<?php
namespace Visitor\Api;
class ApiException extends \Exception {
  public $shouldlog   = true;
  public $shouldemail = true;

  public function __construct( $message, $shouldlog = true, $shouldemail = true ) {

    $this->shouldlog   = $shouldlog;
    $this->shouldemail = $shouldemail;
    parent::__construct( $message );

  }

}

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

    $debug  = \Springboard\Debug::getInstance();
    if ( $this->bootstrap->config['apidebuglog'] ) {
      $debug->log(
        false, 'api.txt',
        "API " . $_SERVER['REQUEST_METHOD'] . " REQUEST: " . $_SERVER['REQUEST_URI'] .
       "\n  Info:\n" . \Springboard\Debug::getRequestInformation( 2 )
      );
    }

    $result = array(
      'result' => 'OK'
    );

    try {

      $this->format = $this->validateParameter('format', $this->formats );
      $this->layer  = $this->validateParameter('layer', $this->layers );
      $this->module = $this->getModule();
      $this->tryAuthenticate();
      $this->callMethod();

      $result['data'] = $this->data;

    } catch( \Exception $e ) {

      $result['result'] = 'ERR';
      $result['data']   = $e->getMessage();

      $shouldlog        = true;
      if ( property_exists( $e, 'shouldlog' ) )
        $shouldlog      = $e->shouldlog;

      if ( $shouldlog ) {

        $message   =
          "API exception caught: " . $e->getMessage() . " --- '" . get_class( $e ) . "'\n" .
          "  Backtrace:\n" . \Springboard\Debug::formatBacktrace( $e->getTrace() ) .
          "\n  Info:\n" . \Springboard\Debug::getRequestInformation( 2 )
        ;

        $sendemail = true;
        if ( property_exists( $e, 'shouldemail' ) )
          $sendemail = $e->shouldemail;

        $debug->log( false, 'api.txt', $message, $sendemail );

      }

    }

    if ( $this->format == 'json' )
      $this->jsonoutput( $result );

  }

  public function validateParameter( $name, $possiblevalues ) {

    $value = $this->getParameter( $name );
    if ( !in_array( $value, $possiblevalues ) )
      throw new ApiException(
        'Invalid parameter: ' . $name . ', possible values: "' .
        implode('", "', $possiblevalues ) . '"',
        false,
        false
      );

    return $value;

  }

  public function getModule( $module = null ) {

    if ( $module === null )
      $module = $this->getParameter('_module');

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
      throw new ApiException('Invalid parameter: module, no such module', false, false );

    return $ret;

  }

  public function callMethod() {

    $method = $this->getParameter('method');

    if ( !$method )
      throw new ApiException('No method specified', false, false );

    if ( !array_key_exists( $method, $this->module->apisignature ) )
      throw new ApiException('No such method found', false, false );

    $parameters = array();
    foreach( $this->module->apisignature[ $method ] as $parameter => $validator ) {

      if ( !is_array( $validator ) )
        continue;

      $validatormethod = strtolower( $validator['type'] ) . 'Validator';
      $parameters[]    = $this->$validatormethod( $parameter, $validator );

    }

    if ( $this->layer == 'controller' )
      $method .= 'Action';

    return $this->data = call_user_func_array( array( $this->module, $method ), $parameters );

  }

  public function idValidator( $parameter, $configuration ) {

    $id            = $this->getNumericParameter( $parameter );
    $defaults      = array(
      'required'    => true,
      'shouldlog'   => true,
      'shouldemail' => true,
    );
    $configuration = array_merge( $defaults, $configuration );

    if ( $id <= 0 and $configuration['required'] )
      throw new ApiException(
        'Invalid parameter: ' . $parameter,
        $configuration['shouldlog'],
        $configuration['shouldemail']
      );

    return $id;

  }

  public function stringValidator( $parameter, $configuration ) {

    $defaults = array(
      'value'       => '',
      'required'    => true,
      'shouldlog'   => true,
      'shouldemail' => true,
    );
    $configuration = array_merge( $defaults, $configuration );

    $value = $this->getParameter( $parameter, $configuration['value'] );
    $value = trim( $value );

    if ( !$value and !$configuration['required'] )
      return $value;
    elseif ( !$value )
      throw new ApiException(
        'Empty parameter: ' . $parameter,
        $configuration['shouldlog'],
        $configuration['shouldemail']
      );

    return $value;

  }

  public function fileValidator( $parameter, $configuration ) {

    $defaults = array(
      'shouldlog'   => true,
      'shouldemail' => true,
    );
    $configuration = array_merge( $defaults, $configuration );

    if ( !isset( $_FILES[ $parameter ] ) or $_FILES[ $parameter ]['error'] != 0 )
      throw new ApiException(
        'Upload failed. Information: ' . var_export( @$_FILES[ $parameter ], true ),
        $configuration['shouldlog'],
        $configuration['shouldemail']
      );

    return $_FILES[ $parameter ];

  }

  public function userValidator( $parameter, $configuration ) {

    $defaults = array(
      'shouldlog'   => true,
      'shouldemail' => true,
    );
    $configuration = array_merge( $defaults, $configuration );

    if ( !isset( $configuration['permission'] ) )
      throw new ApiException(
        'Undefined permission for user validation!',
        $configuration['shouldlog'],
        $configuration['shouldemail']
      );

    $user = $this->bootstrap->getSession('user');

    if (
         isset( $configuration['privilege'] ) and
         $this->bootstrap->config['usedynamicprivileges']
       )
      $this->userHasPrivilege( $configuration );
    else if ( isset( $configuration['permission'] ) ) {
      $ret = $this->userHasPermission( $configuration, $user );
      // csak akkor engedjuk tovabb a kodot ha a permission nem
      // public vagy member, az impersonatehez magasabb perm kell
      if ( $ret !== false )
        return $ret;
    }

    if ( isset( $configuration['impersonatefromparameter'] ) ) {

      $id        = $this->getNumericParameter(
        $configuration['impersonatefromparameter']
      );
      $userModel = $this->modelIDCheck('users', $id, false );

      if ( !$userModel )
        throw new ApiException(
          'No user found with id: ' . $id,
          $configuration['shouldlog'],
          $configuration['shouldemail']
        );

      $userModel->registerForSession();
      $this->logUserLogin('IMPERSONATE APILOGIN');

    }

    return $user;
  }

  protected function userHasPermission( $configuration, $user ) {
    if ( $configuration['permission'] === 'public' )
      return true;

    if (
         $configuration['permission'] === 'member' and
         $user['id']
       )
      return $user;

    if ( !$user['is' . $configuration['permission'] ] )
      throw new ApiException(
        'Access denied, not enough permission',
        $configuration['shouldlog'],
        $configuration['shouldemail']
      );

    return false;
  }

  protected function userHasPrivilege( $configuration ) {
    if ( !\Model\Userroles::userHasPrivilege( $configuration['privilege'] ) )
      throw new ApiException(
        'Access denied, privilege not found: ' . $configuration['privilege'],
        $configuration['shouldlog'],
        $configuration['shouldemail']
      );
  }

  public function getParameter( $key, $defaultvalue = null ) {
    return $this->application->getParameter( $key, $defaultvalue );
  }

  public function getNumericParameter( $key, $defaultvalue = null, $isfloat = false ) {
    return $this->application->getNumericParameter( $key, $defaultvalue, $isfloat );
  }

  public function tryAuthenticate() {

    $email    = $this->getParameter('email');
    $password = $this->getParameter('password');
    $method   = $this->getParameter('method');

    if (
         isset( $this->module->apisignature[ $method ] ) and
         isset( $this->module->apisignature[ $method ]['loginrequired'] ) and
         !$this->module->apisignature[ $method ]['loginrequired']
       )
      return;

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
      throw new ApiException('Invalid user!', true, false );

  }

}
