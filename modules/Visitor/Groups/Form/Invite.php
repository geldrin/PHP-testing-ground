<?php
namespace Visitor\Groups\Form;

class Invite extends \Visitor\Form {
  public $configfile = 'Invite.php';
  public $template   = 'Visitor/genericform.tpl';
  public $needdb     = true;
  
  public function init() {
    
    $l = $this->bootstrap->getLocalization();
    $this->groupModel = $this->controller->modelOrganizationAndIDCheck(
      'groups',
      $this->application->getNumericParameter('id')
    );
    $this->controller->toSmarty['title'] = $l('groups', 'invite_title');
    
  }
  
  public function onComplete() {
    
    $values     = $this->form->getElementValues( 0 );
    $user       = $this->bootstrap->getSession('user');
    
    if ( !empty( $values['users'] ) )
      $this->groupModel->addUsers( $values['users'] );
    
    $this->controller->redirect(
      $this->application->getParameter('forward', 'groups' )
    );
    
  }
  
}
