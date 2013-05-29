<?php
if ( !defined('BASE_PATH') )
  include('../Bootstrap.php');

setupAutoloader();

class RouteTest extends \PHPUnit_Framework_TestCase {
  
  public function setUp() {
    $this->application = new Springboard\Application( BASE_PATH, false );
  }
  
  public function tearDown() {
    $this->application = null;
  }
  
  public function testRouteParameters() {
    
    $params      = $this->application->getRouteParameters();
    $this->assertEquals( $params['module'], 'index' );
    $this->assertEquals( $params['action'], 'index' );
    
    $this->application->injectParameters( array(
        'module' => 'Asi4gfaf',
        'action' => '84JKIK<_>"\\/../',
      ),
      false
    );
    
    $params      = $this->application->getRouteParameters();
    $this->assertEquals( $params['module'], 'asi4gfaf' );
    $this->assertEquals( $params['action'], '84jkik_' );
    
  }
  
  public function testRoute() {
    
    $bootstrap = new stdClass();
    $bootstrap->application = $this->application;
    $bootstrap->config      = array(
      'baseuri' => 'http://testhost',
    );
    
    $controller = $this->getMock(
      'Springboard\\Controller',
      array('route'),
      array( $bootstrap )
    );
    $controller->expects( $this->once() )->method('route');
    unset( $bootstrap );
    
    include_once( BASE_PATH . 'Bootstrap.php' );
    $bootstrap = $this->getMock(
      'Bootstrap',
      array('getController'),
      array( $this->application ),
      '',
      false // dont call original constructor
    );
    $bootstrap->expects( $this->once() )
              ->method('getController')
              ->will( $this->returnValue( $controller ) );
    
    $this->application->bootstrap( $bootstrap );
    $this->application->route();
    
  }
  
}
