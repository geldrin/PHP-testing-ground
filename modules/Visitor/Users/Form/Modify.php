<?php
namespace Visitor\Users\Form;
class Modify extends \Visitor\HelpForm {
  public $configfile = 'Modify.php';
  public $template = 'Visitor/genericform.tpl';
  public $needdb = true;
  public $userModel;
  public $user;
  
  public function init() {
    
    parent::init();
    $this->user      = $this->bootstrap->getSession('user');
    $this->userModel = $this->controller->modelIDCheck('users', $this->user['id'] );
    $this->values    = $this->userModel->row;
    unset( $this->values['password'] );
    
  }
  
  public function onComplete() {
    
    $values = $this->form->getElementValues( 0 );
    $crypt  = $this->bootstrap->getEncryption();
    $l      = $this->bootstrap->getLocalization();
    
    if ( !@$values['password'] )
      unset( $values['password'] );
    else
      $values['password'] = $crypt->getHash( $values['password'] );
    
    $this->userModel->updateRow( $values );
    $this->userModel->registerForSession();
    
    $this->controller->redirectWithMessage('users/modify', $l('users', 'usermodified') );
    
  }
  
}
