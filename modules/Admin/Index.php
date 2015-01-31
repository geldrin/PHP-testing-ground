<?php
namespace Admin;
class Index extends \Springboard\Controller\Admin {
  protected $formactions = array();
  public $permissions = array(
    'index'  => 'admin',
    'logout' => 'admin',
    'ping'   => 'admin',
    'togglemaintenance' => 'admin',
  );
  
  private $maintenancetypes = array(
    'site', 'upload',
  );

  public function indexAction() {
    
    $menu = $this->prepareMenu( \Admin\Menu::get( $this->bootstrap ) );

    $maintenance = array();
    foreach( $this->maintenancetypes as $type )
      $maintenance[ $type ] = $this->bootstrap->inMaintenance( $type );

    $this->toSmarty['maintenance'] = $maintenance;
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
  
  public function togglemaintenanceAction() {
    $type   = $this->application->getParameter('type');
    $status = $this->application->getParameter('status');

    if (
         !in_array( $type, $this->maintenancetypes ) or
         ( $status !== 'off' and $status !== 'on' )
       )
      return $this->redirect('');

    if ( $status === 'on')
      $this->bootstrap->setMaintenance( $type );
    else
      $this->bootstrap->disableMaintenance( $type );

    return $this->redirect('');
  }
}
