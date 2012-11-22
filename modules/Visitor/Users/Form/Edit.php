<?php
namespace Visitor\Users\Form;
class Edit extends \Visitor\HelpForm {
  public $configfile = 'Edit.php';
  public $template = 'Visitor/genericform.tpl';
  public $needdb = true;
  public $userModel;
  public $user;
  
  public function init() {
    
    parent::init();
    $l               = $this->bootstrap->getLocalization();
    $this->userModel = $this->controller->modelOrganizationAndIDCheck(
      'users',
      $this->application->getNumericParameter('id')
    );
    $this->values    = $this->userModel->row;
    unset( $this->values['password'] );
    
    $this->values['permissions'] = array();
    foreach( $l->getLov('permissions') as $k => $v ) {
      
      if ( $this->values[ $k ] )
        $this->values['permissions'][] = $k;
      
    }
    
  }
  
  public function onComplete() {
    
    $values = $this->form->getElementValues( 0 );
    $crypt  = $this->bootstrap->getEncryption();
    $l      = $this->bootstrap->getLocalization();
    
    foreach( $l->getLov('permissions') as $k => $v ) {
      
      if ( isset( $_REQUEST['permissions'][ $k ] ) and in_array( $k, $values['permissions'] ) )
        $values[ $k ] = 1;
      else
        $values[ $k ] = 0;
      
    }
    
    if ( !@$values['password'] )
      unset( $values['password'] );
    else
      $values['password'] = $crypt->getHash( $values['password'] );
    
    $this->userModel->updateRow( $values );
    
    $forward = $this->application->getParameter('forward', 'users/admin');
    $this->controller->redirectWithMessage( $forward, $l('users', 'usermodified') );
    
  }
  
}