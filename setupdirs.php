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
    
  }
  
  public function loadConfig( $filename ) {
    
    if ( !is_readable( $filename ) )
      throw new \Exception("Unable to read $filename\n");
    
    $config = include( $filename );
    $this->config = array_replace_recursive( $this->config, $config );
    
  }
  
  protected function getAttributes( $values = null, $prefix = null ) {
    
    $ret = array(
      'user'  => $this->config['setupdirs']['defaultuser'],
      'group' => $this->config['setupdirs']['defaultgroup'],
      'perms' => $this->config['setupdirs']['defaultperms'],
    );
    
    if ( !$values and $prefix !== null ) {
      
      foreach( $ret as $key => $v ) {
        if ( isset( $this->config['setupdirs'][ $prefix . $key ] ) )
          $ret[ $key ] = $this->config['setupdirs'][ $prefix . $key ];
      }
      
    } elseif ( $values )
      $ret = array_merge( $ret, $values );
    
    return $ret;
    
  }
  
  public function setup() {
    
    preg_match_all(
      '#(.+)/\*\*$#',
      file_get_contents( $this->basepath . '.gitignore' ),
      $matches
    );
    
    $attr = $this->getAttributes(null, 'privileged');
    chdir( $this->basepath );
    
    echo `chmod -R {$attr['perms']} .`;
    echo `chown -R {$attr['user']}:{$attr['group']} .git modules libraries views resources models`;
    
    $attr = $this->getAttributes(null, 'privileged');
    foreach( $matches[1] as $dir ) {
      
      $dir = $this->basepath . $dir;
      echo `mkdir -p $dir`;
      echo `chmod -R {$attr['perms']} $dir`;
      echo `chown -R {$attr['user']}:{$attr['group']} $dir`;
      
    }
    
    foreach( $this->config['setupdirs']['extradirs'] as $value ) {
      
      $dir  = $value['dir'];
      $attr = $this->getAttributes(null, 'privileged');
      echo `mkdir -p $dir`;
      echo `chmod -R {$attr['perms']} $dir`;
      echo `chown -R {$attr['user']}:{$attr['group']} $dir`;
      
    }
    
  }
  
}
