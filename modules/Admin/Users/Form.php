<?php
namespace Admin\Users;

class Form extends \Springboard\Controller\Admin\Form {
  
  protected function updateAction() {
    
    $model  = $this->bootstrap->getModel( $this->controller->module );
    $values = $this->form->getElementValues( false );
    $crypto = $this->bootstrap->getCrypto();
    
    if ( strlen( $values['password'] ) )
      $values['password'] = $crypto->getHash( $values['password'] );
    
    $model->select( $values['id'] );
    $model->updateRow( $values );
    
    $this->controller->redirectToFragment('users/index');
    
  }
  
  protected function insertAction() {
    
    $model  = $this->bootstrap->getModel( $this->controller->module );
    $values = $this->form->getElementValues( false );
    $crypto = $this->bootstrap->getCrypto();
    $values['password'] = $crypto->getHash( $values['password'] );
    $model->insert( $values );
    
    $this->controller->redirectToFragment('users/index');
    
  }
  
}