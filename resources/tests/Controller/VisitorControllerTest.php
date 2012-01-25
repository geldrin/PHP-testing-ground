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
    
    $acl->expects( $this->once() )
        ->method('hasPermission')
        ->will( $this->returnValue( $aclshouldreturn ) );
    
    $bootstrap->expects( $this->once() )
              ->method('getAcl')
              ->will( $this->returnValue( $acl ) );
    
    $application = new Springboard\Application();
    $application->bootstrap( $bootstrap );
    $bootstrap->application = $application;
    
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
    
    $application = new Springboard\Application();
    $application->bootstrap( $bootstrap );
    $bootstrap->application = $application;
    
    $controller = $this->getMock(
      'Springboard\\Controller\\Visitor',
      array('displayNotFound'),
      array( $bootstrap )
    );
    $controller->expects( $this->once() )
               ->method('displayNotFound');
    
    $controller->route();
    
  }
  
  /**
   * @expectedException Springboard\Exception
   */
  public function testPermissionError() {
    
    $controller = $this->initController( false, array('indexAction', 'handleAccessFailure') );
    
    $controller->expects( $this->never() )
               ->method('indexAction');
    
    $controller->expects( $this->once() )
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
      array(),
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
  
}