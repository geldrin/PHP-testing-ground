<?php
namespace Visitor\Users\Form;
class Invite extends \Visitor\Form {
  public $configfile = 'Invite.php';
  public $template   = 'Visitor/genericform.tpl';
  public $needdb     = true;
  
  public function postSetupForm() {
    
    $l = $this->bootstrap->getLocalization();
    $this->toSmarty['title'] = $l('users', 'invite_title');
    
  }
  
  public function onComplete() {
    
    $values    = $this->form->getElementValues( 0 );
    $invModel  = $this->bootstrap->getModel('users_invitations');
    $crypto    = $this->bootstrap->getEncryption();
    $queue     = $this->bootstrap->getMailqueue();
    $l         = $this->bootstrap->getLocalization();
    $smarty    = $this->bootstrap->getSmarty();
    $user      = $this->bootstrap->getUser();
    /*
    // folosleges feltakaritas?
    $invModel->addFilter('userid', $user->id );
    $invModel->addFilter('email', $values['email'], false, false );
    $invitations = $invModel->getArray();
    
    if ( !empty( $invitations ) )
      foreach( $invitations as $invitation )
        $invModel->delete( $invitation['id'] );
    */
    $values['permissions']    = implode('|', $values['permissions'] );
    $values['validationcode'] = $crypto->randomPassword( 10 );
    $values['userid']         = $user->id;
    
    $invModel->insert( $values );
    
    $invModel->row['id'] = $crypto->asciiEncrypt( $invModel->row['id'] );
    $smarty->assign('values', $invModel->row );
    
    $queue->embedImages = false;
    $queue->sendHTMLEmail(
      $values['email'],
      $l('users', 'invitationmailsubject'),
      $smarty->fetch('Visitor/Users/Email/Invitation.tpl')
    );
    
    $this->controller->redirectWithMessage('users/admin', $l('users', 'user_invited') );
    
  }
  
}
