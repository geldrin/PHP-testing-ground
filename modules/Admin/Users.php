<?php
namespace Admin;
class Users extends \Springboard\Controller\Admin {
  
  public function init() {
    
    $this->permissions['loginas'] = 'admin';
    parent::init();
    
  }
  
  public function loginasAction() {
    
    $userid = $this->application->getNumericParameter('id');
    
    if ( $userid <= 0 )
      $this->redirect('users');
    
    $user      = $this->bootstrap->getSession('user');
    $userModel = $this->bootstrap->getModel('users');
    $userModel->select( $userid );
    
    if ( empty( $userModel->row ) )
      $this->redirect('users');
    
    $url = ( SSL? 'https://': 'http://' ) . $this->application->config['baseuri'];
    
    $user->setArray( $userModel->row );
    $this->redirect( $url );
    
  }
  
}
