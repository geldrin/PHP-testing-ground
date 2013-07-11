#!/usr/bin/env php
<?php
define('BASE_PATH',  realpath( dirname( __FILE__ ) ) . '/' );
error_reporting(E_ALL);
ini_set('display_errors', true);

$production = null;
if ( $argc == 2 )
  $production = (bool)$argv[1];

$setup = new Setupdirs( BASE_PATH, $production );
$setup->setup();

class Setupdirs {
  
  public $config     = array();
  public $production = true;
  public $basepath;

  public $debug      = false;

  public function __construct( $basepath, $production = null ) {
    
    $this->basepath = $basepath;
    $localconfig    = $this->basepath . 'config_local.php';
    
    if ( $production !== null )
      $this->production = $production;
    else {
      
      if ( file_exists( $localconfig ) )
        $this->production = false;
      else
        $this->production = true;
      
    }
    
    $this->loadConfig( $this->basepath . 'config.php' );
    
    if (
         file_exists( $localconfig ) and
         (
           @$this->config['alwaysloadlocalconfig'] or
           !$this->production
         )
       )
      $this->loadConfig( $localconfig );

    $this->defaultDevDirs .=
      '.git libraries models modules resources views ' .
      basename( $this->config['docroot'] )
    ;
    
  }
  
  public function loadConfig( $filename ) {
    
    if ( !is_readable( $filename ) )
      throw new \Exception("Unable to read $filename\n");

    $config = include( $filename );
    $this->config = array_replace_recursive( $this->config, $config );

  }
  
  protected function getAttributes( $array, $prefix ) {

    $ret = array(
      'user'  => $this->config['setupdirs']['user'],
      'group' => $this->config['setupdirs']['group'],
      'perms' => $this->config['setupdirs']['perms'],
    );

    foreach( $ret as $key => $v ) {
      if ( isset( $array[ $prefix . $key ] ) )
        $ret[ $key ] = $array[ $prefix . $key ];
    }
    
    return $ret;
    
  }
  
  public function setup() {
    
    chdir( $this->basepath );

    $attr = $this->getAttributes( $this->config['setupdirs'], '' );
 
    // process default directories 
    $this->chmod("{$attr['perms']} .");
    $this->chown("{$attr['user']}:{$attr['group']} " . $this->defaultDevDirs );

    // process 'privileged' (=typically www-data owned dirs) directories
    // by processing .gitignore
    preg_match_all(
      '#^(.+)/\*\*$#mUi',
      file_get_contents( '.gitignore' ),
      $matches
    );

    $attr = $this->getAttributes( $this->config['setupdirs'], 'privileged' );

    foreach( $matches[1] as $dir )
      $this->setupDirectory( $dir, $attr );

    // process additional dirs
    foreach( @$this->config['setupdirs']['extradirs'] as $dirItem ) {
      $this->setupDirectory(
        $dirItem['dir'], $this->getAttributes( $dirItem, '' )
      );
    }

  }

  private function setupDirectory( $dir, $attr ) {

    $this->mkdir( $dir );
    $this->chmod( "{$attr['perms']} $dir" );
    $this->chown( "{$attr['user']}:{$attr['group']} $dir" );

  }  

  private function chown( $parameters ) {

    if ( $this->isUnixBasedServer() )
      $this->execute('chown -R ' . $parameters );
    else
      $this->warning('chown', $parameters );

  }

  private function chmod( $parameters ) {

    if ( $this->isUnixBasedServer() )
      $this->execute('chmod -R ' . $parameters );
    else
      $this->warning('chmod', $parameters );

  }

  private function mkdir( $parameters ) {

    if ( $this->isUnixBasedServer() )
      $this->execute('mkdir -p ' . $parameters );
    else
      $this->execute('mkdir ' . $parameters );

  }

  private function execute( $command ) {

    if ( $this->isUnixBasedServer() )
      $command .= ' 2>&1';

    if ( !$this->isUnixBasedServer() )
      $command = str_replace( '/', DIRECTORY_SEPARATOR, $command );

    if ( $this->debug )
      echo $command . '<br />'; 

    echo `$command`;

  }

  private function warning( $command, $parameters ) {

    echo 'warning: "' . $command . ' ' . $parameters . '" not supported on ' . PHP_OS . "\n";

  }

  private function isUnixBasedServer() {

    return !preg_match('/^win/i', PHP_OS );

  }
  
}
