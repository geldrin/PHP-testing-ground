#!/usr/bin/env php
<?php
define('BASE_PATH',  realpath( dirname( __FILE__ ) ) . '/' );
set_time_limit(0);

if ( isset( $_SERVER['APPLICATION_ENV'] ) and $_SERVER['APPLICATION_ENV'] == 'developer' )
  define('PRODUCTION', false );
else
  define('PRODUCTION', true );

include_once( BASE_PATH . 'libraries/Springboard/Application/Cli.php');

$app    = new Springboard\Application\Cli( BASE_PATH, PRODUCTION );
$initdb = new Initdb( $app, $argv );
$initdb->setupMultistring();
echo "Done!\n";

class Initdb {
  public $application;
  public $bootstrap;
  public $orgModel;
  public $multistringtables = array(
    'genres',
    'roles',
    'channel_types',
    'help_contents',
    'contents',
    'languages',
  );
  
  public function __construct( $app, $argv ) {
    $this->application = $app;
    $this->bootstrap = $app->bootstrap;
    
    $this->checkArguments( $argv );
    
  }
  
  public function checkArguments( $argv ) {
    
    if ( count( $argv ) == 1 )
      throw new Exception("Organizationid argument not specified! Expecting a simple integer with the organizationid");
    
    $orgid = intval( $argv[1] );
    if ( !$orgid )
      throw new Exception("Invalid organizationid passed! Expecting a simple integer");
    
    $this->orgModel = $this->bootstrap->getModel('organizations');
    $this->orgModel->select( $orgid );
    
    if ( !$this->orgModel->row )
      throw new Exception("Invalid organizationid passed! No record exists with that id: " . $orgid );
    
  }
  
  public function setupMultistring() {
    
    foreach( $this->multistringtables as $table ) {
      
      echo "Now setting up $table\n";
      $file = sprintf(
        BASE_PATH . 'data/defaultvalues/%s.php',
        $table
      );
      
      if ( !file_exists( $file ) )
        throw new Exception("Defaultvalues for table $table does not exist: " . $file );
      
      $values = include( $file );
      $this->loadMultiStrings( $table, $values );
      
    }
    
  }
  
  public function loadMultiStrings( $table, $values ) {
    
    $model    = $this->bootstrap->getModel( $table );
    $parentid = '0';
    $orgid    = $this->orgModel->id;
    
    foreach( $values as $data ) {
      
      if ( empty( $data ) )
        continue;
      
      $strings = array();
      if ( isset( $data['name'] ) ) {
        
        $strings['name_stringid'] = array(
          'hu' => $data['namehungarian'],
          'en' => $data['nameenglish'],
        );
        
      }
      
      if ( isset( $data['title'] ) ) {
        
        $strings['title_stringid'] = array(
          'hu' => $data['title'],
          'en' => $data['titleen'],
        );
        
      }
      
      if ( isset( $data['body'] ) ) {
        
        $strings['body_stringid'] = array(
          'hu' => $data['body'],
          'en' => $data['bodyen'],
        );
        
      }
      
      
      // nem szamit ha nincsenek ilyen mezok
      $data['organizationid'] = $orgid;
      
      if ( !@$data['weight'] )
        $data['weight'] = 100;
      
      if ( isset( $data['origparentid'] ) and !$data['origparentid'] )
        $data['parentid'] = $parentid;
      else
        $data['parentid'] = 0;
      
      $row = $model->insert( $data, $strings, false );
      
      if ( isset( $data['origparentid'] ) and !$data['origparentid'] )
        $data['parentid'] = $row['id'];
      
    }
    
  }
  
}
