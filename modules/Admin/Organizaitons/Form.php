<?php
namespace Admin\Organizaitons;

class Form extends \Springboard\Controller\Admin\Form {
  
  protected function insertAction() {
    
    $orgModel  = $this->bootstrap->getModel('organizaitons');
    $values = $this->form->getElementValues( false );
    
    $orgModel->insert( $values );
    
    if ( $values['parentid'] == 0 )
      $orgModel->setup();
    
    $this->controller->redirect('users/index');
    
  }
  
}
