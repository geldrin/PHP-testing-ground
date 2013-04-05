<?php
namespace Visitor\Groups\Form;

class Invite extends \Visitor\Form {
  public $configfile = 'Invite.php';
  public $template   = 'Visitor/genericform.tpl';
  public $needdb     = true;
  
  public function postSetupForm() {
    
    $l = $this->bootstrap->getLocalization();
    $this->controller->toSmarty['title'] = $l('groups', 'create_title');
    
  }
  
  public function onComplete() {
    
    $values     = $this->form->getElementValues( 0 );
    $groupModel = $this->bootstrap->getModel('groups');
    $user       = $this->bootstrap->getSession('user');
    
    $values['timestamp'] = date('Y-m-d H:i:s');
    $values['userid']    = $user['id'];
    $groupModel->insert( $values );
    
    $this->controller->redirect(
      $this->application->getParameter('forward', 'groups' )
    );
    
  }
  
}
