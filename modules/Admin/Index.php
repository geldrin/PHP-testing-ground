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
    
    $smarty = $this->bootstrap->getSmarty();
    $menu = $this->prepareMenu( \Admin\Menu::get( $this->bootstrap ) );
    $smarty->assign('menu', $menu );
    $this->output( $smarty->fetch('Admin/index.tpl') );
    
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
