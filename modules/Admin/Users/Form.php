<?php
namespace Admin\Users;

class Form extends \Springboard\Controller\Admin\Form {
  
  public function postSetupForm( $action = null ) {
    
    if ( $action == 'modify' )
      $this->form->setValue('password', '', false );
    
  }
  
  protected function updateAction() {
    
    $model  = $this->bootstrap->getModel( $this->controller->module );
    $values = $this->form->getElementValues( false );
    $crypto = $this->bootstrap->getEncryption();
    
    if ( strlen( $values['password'] ) )
      $values['password'] = $crypto->getHash( $values['password'] );
    else
      unset( $values['password'] );
    
    $model->select( $values['id'] );
    $model->updateRow( $values );
    
    $this->controller->redirect('users/index');
    
  }
  
  protected function insertAction() {
    
    $model  = $this->bootstrap->getModel( $this->controller->module );
    $values = $this->form->getElementValues( false );
    $crypto = $this->bootstrap->getEncryption();
    $values['password'] = $crypto->getHash( $values['password'] );
    $model->insert( $values );
    
    $this->controller->redirect('users/index');
    
  }
  
}