<?php
namespace Visitor\Groups\Form;

class Invite extends \Visitor\Users\Form\Invite {
  public $configfile = 'Invite.php';
  public $template   = 'Visitor/Groups/Invite.tpl';
  public $needdb     = true;
  
  public function init() {
    
    $l = $this->bootstrap->getLocalization();
    $this->groupModel = $this->controller->modelOrganizationAndIDCheck(
      'groups',
      $this->application->getNumericParameter('id')
    );
    $this->controller->toSmarty['title'] = $l('groups', 'invite_title');
    
  }
  
  public function postSetupForm() {}
  public function onComplete() {
    
    $l      = $this->bootstrap->getLocalization();
    $values = $this->form->getElementValues( 0 );
    $url    =
      'groups/details/' . $this->groupModel->id . ',' .
      \Springboard\Filesystem::filenameize( $this->groupModel->row['name'] )
    ;
    
    if ( $values['userid'] ) {
      
      $validuser = $this->groupModel->isValidUser(
        $values['userid'],
        $this->controller->organization['id']
      );
      
      if ( !$validuser ) {
        $this->form->addMessage( $l('groups', 'invaliduser') );
        $this->form->invalidate();
        return;
      }
      
      $this->groupModel->addUsers( array( $values['userid'] ) );
      $this->controller->redirectWithMessage( $url, $l('groups', 'useradded') );
      
    }
    
    unset( $values['id'] );
    $values['groups'] = array( $this->groupModel->id );
    $this->addInvitation( $values );
    $this->controller->redirectWithMessage( $url, $l('users', 'user_invited') );
    
  }
  
}
