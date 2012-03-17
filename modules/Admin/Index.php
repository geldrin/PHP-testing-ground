<?php
namespace Admin;
class Index extends \Springboard\Controller\Admin {
  protected $formactions = array();
  public $permissions = array(
    'index'  => 'admin',
    'logout' => 'admin',
    'ping'   => 'admin',
  );
  
  public function indexAction() {
    
    $menu = $this->prepareMenu( \Admin\Menu::get( $this->bootstrap ) );
    $this->toSmarty['menu'] = $menu;
    $this->smartyoutput('Admin/index.tpl');
    
  }
  
  protected function prepareMenu( $menu ) {
    
    foreach( $menu as $key => $value ) {
      
      $menu[ $key ]['link'] = $this->getUrlFromFragment( $value['link'] );
      
    }
    
    return $menu;
    
  }
  
  public function pingAction() {
    echo time();
  }
  
}
