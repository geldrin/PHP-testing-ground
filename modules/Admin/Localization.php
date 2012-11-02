<?php
namespace Admin;
class Localization extends \Springboard\Controller\Admin {
  public $hidenavigation = true;
  public $permissions    = array(
    'update' => 'admin',
    'modify' => 'admin',
    'index'  => 'admin',
  );
  
  protected $formactions = array(
    'modify', 'update',
  );
  
  public function indexAction() {
    
    $this->toSmarty['modules'] = $this->getModulesWithLocales();
    $this->preparePage();
    $this->smartyoutput('Admin/localization.tpl');
    
  }
  
  public function getModulesWithLocales() {
    
    $iterator = new \GlobIterator(
      $this->bootstrap->config['modulepath'] . 'Visitor/*/Locale/*.ini'
    );
    $modpath = preg_quote( $this->bootstrap->config['modulepath'] . 'Visitor/', '#' );
    $modules = array();
    
    foreach( $iterator as $path => $v ) {
      
      preg_match( "#^$modpath(.*)/Locale/.*$#", $path, $match );
      $modules[] = $match[1];
      
    }
    
    $modules = array_unique( $modules );
    sort( $modules );
    
    return $modules;
    
  }
  
}
