<?php
if ( !defined('BASE_PATH') )
  include('../Bootstrap.php');

include_once( BASE_PATH . 'libraries/Springboard/Autoloader.php' );

class AutoloaderTest extends \PHPUnit_Framework_TestCase {
  
  public function setUp() {
    
    $this->loaders = spl_autoload_functions();
    
    // spl_autoload_functions nem tombot ad am vissza ha nincs regisztralt
    // function, <3 php
    if ( !is_array( $this->loaders ) )
      $this->loaders = array();
    
    $bootstrap = new stdClass();
    $bootstrap->config = array(
      'libpath'    => __DIR__ . '/Assets/',
      'modulepath' => __DIR__ . '/Assets/',
      'modelpath'  => __DIR__ . '/Assets/Model/',
    );
    
    $this->loader = new Springboard\Autoloader( $bootstrap );
    
  }
  
  public function tearDown() {
    
    $loaders = spl_autoload_functions();
    
    if ( is_array( $loaders ) )
      foreach ( $loaders as $loader )
        spl_autoload_unregister( $loader );
    
    foreach ( $this->loaders as $loader )
      spl_autoload_register( $loader );
    
    $this->loader = null;
    
  }
  
  public function testSpringboardNamespace() {
    $class = 'Springboard\\SpringboardTest';
    $this->assertFalse( class_exists( $class, false ) );
    $this->loader->autoload( $class );
    $this->assertTrue( class_exists( $class ) );
  }
  
  public function testModelNamespace() {
    $class = 'Model\\ModelTest';
    $this->assertFalse( class_exists( $class, false ) );
    $this->loader->autoload( $class );
    $this->assertTrue( class_exists( $class ) );
  }
  
  public function testVisitorNamespace() {
    $class = 'Visitor\\VisitorTest';
    $this->assertFalse( class_exists( $class, false ) );
    $this->loader->autoload( $class );
    $this->assertTrue( class_exists( $class ) );
  }
  
  public function testFallback() {
    $class = 'fallback';
    $this->assertFalse( class_exists( $class, false ) );
    $this->loader->autoload( $class );
    $this->assertTrue( class_exists( $class ) );
  }
  
  public function testExistingClass() {
    
    $this->assertFalse( $this->loader->findExistingClass('NoSuchClass') );
    
    $class = 'Springboard\\SpringboardTest';
    $this->assertEquals( $this->loader->findExistingClass( $class ), $class );
    
    $class = 'Model\\ModelTest';
    $this->assertEquals( $this->loader->findExistingClass( $class ), $class );
    
    $class = 'Visitor\\VisitorTest';
    $this->assertEquals( $this->loader->findExistingClass( $class ), $class );
    
  }
  
}
