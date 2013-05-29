<?php
if ( !defined('BASE_PATH') )
  include('../Bootstrap.php');

setupAutoloader();
include_once( BASE_PATH . 'Bootstrap.php');

class VisitorControllerTest extends \PHPUnit_Framework_TestCase {
  
  protected function initController( $aclshouldreturn, $controllermethods ) {
    
    $bootstrap = $this->getMock(
      'Bootstrap',
      array('getAcl'),
      array(),
      '',
      false
    );
    
    $acl = $this->getMock(
      'Springboard\\Acl',
      array('hasPermission'),
      array(),
      '',
      false
    );
    
    $acl->expects( $this->any() )
        ->method('hasPermission')
        ->will( $this->returnValue( $aclshouldreturn ) );
    
    $bootstrap->expects( $this->once() )
              ->method('getAcl')
              ->will( $this->returnValue( $acl ) );
    
    $application = new Springboard\Application( BASE_PATH, false );
    $application->bootstrap( $bootstrap );
    $bootstrap->application = $application;
    $bootstrap->config      = array('baseuri' => 'http://testhost.tld');
    
    if ( empty( $controllermethods ) )
      return new Springboard\Controller\Visitor( $bootstrap );
    
    $controller = $this->getMock(
      'Springboard\\Controller\\Visitor',
      $controllermethods,
      array( $bootstrap )
    );
    
    return $controller;
    
  }
  
  public function testNotFound() {
    
    $bootstrap = $this->getMock(
      'Bootstrap',
      array('getController'),
      array(),
      '',
      false
    );
    
    $application = new Springboard\Application( BASE_PATH, false );
    $application->bootstrap( $bootstrap );
    $bootstrap->application = $application;
    $bootstrap->config      = array('baseuri' => 'http://testhost.tld');
    
    $controller = $this->getMock(
      'Springboard\\Controller\\Visitor',
      array('displayNotFound', 'redirectToMainDomain', 'redirectToController'),
      array( $bootstrap )
    );
    
    $controller->expects( $this->once() )
               ->method('redirectToController')
               ->with($this->equalTo('contents'), $this->equalTo('http404'));
    
    $controller->route();
    
  }
  
  /**
   * @expectedException Springboard\Exception
   */
  public function testPermissionError() {
    
    $controller = $this->initController( false, array('indexAction', 'handleAccessFailure') );
    
    $controller->expects( $this->never() )
               ->method('indexAction');
    
    $controller->expects( $this->exactly(2) )
               ->method('handleAccessFailure');
    
    $controller->permissions = array('index' => 'nopermission');
    $controller->route();
    
  }
  
  public function testMethodDispatch() {
    
    $controller = $this->initController( true, array('indexAction') );
    
    $controller->expects( $this->once() )
               ->method('indexAction');
    
    $controller->permissions = array('index' => 'haspermission');
    $controller->route();
    
  }
  
  public function testFormDispatch() {
    
    $controller = $this->initController( true, array() );
    
    $form = $this->getMock(
      'Springboard\\Controller\\Form',
      array('route'),
      array(),
      '',
      false
    );
    
    $form->expects( $this->once() )
         ->method('route');
    
    $controller->permissions = array('index' => 'haspermission');
    $controller->forms = array(
      'index' => $form
    );
    
    $controller->route();
    
  }
  
  public function testPagingDispatch() {
    
    $controller = $this->initController( true, array() );
    
    $paging = $this->getMock(
      'Springboard\\Controller\\Paging',
      array('route'),
      array( $controller->bootstrap, $controller ),
      '',
      false
    );
    
    $paging->expects( $this->once() )
           ->method('route');
    
    $controller->permissions = array('index' => 'haspermission');
    $controller->paging = array(
      'index' => $paging
    );
    
    $controller->route();
    
  }
  
  // abstract classok konkret metodusait akarjuk ezzel mockolni
  // http://stackoverflow.com/questions/8040296/mocking-concrete-method-in-abstract-class-using-phpunit
  public function getMock($originalClassName, $methods = array(), array $arguments = array(), $mockClassName = '', $callOriginalConstructor = TRUE, $callOriginalClone = TRUE, $callAutoload = TRUE) {
    if ($methods !== null) {
        $methods = array_unique(array_merge($methods,
                self::getAbstractMethods($originalClassName, $callAutoload)));
    }
    return parent::getMock($originalClassName, $methods, $arguments, $mockClassName, $callOriginalConstructor, $callOriginalClone, $callAutoload);
  }
  
  /**
   * Returns an array containing the names of the abstract methods in <code>$class</code>.
   *
   * @param string $class name of the class
   * @return array zero or more abstract methods names
   */
  public static function getAbstractMethods($class, $autoload=true) {
    $methods = array();
    if (class_exists($class, $autoload) || interface_exists($class, $autoload)) {
      $reflector = new ReflectionClass($class);
      foreach ($reflector->getMethods() as $method) {
        if ($method->isAbstract()) {
          $methods[] = $method->getName();
        }
      }
    }
    return $methods;
  }
  
}