<?php
namespace Admin;
class Login extends \Springboard\Controller\Admin {
  public $permissions = array(
    'login'  => 'public',
    'index'  => 'public',
  );
  
  public function route() {
    
    $this->form = $this->bootstrap->getAdminFormController( $this->module, $this );
    $this->form->configfile = $this->configfile;
    
    try {
      $this->form->route();
    } catch( \Springboard\Exception\NotFound $e ) {
      // TODO 404
      echo $e->getMessage();
    }
    
  }
  
}
