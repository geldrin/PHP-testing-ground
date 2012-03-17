<?php
namespace Visitor\Users\Form;
class Invite extends \Visitor\Form {
  public $configfile = 'Invite.php';
  public $template   = 'Visitor/genericform.tpl';
  public $needdb     = true;
  
  public function postSetupForm() {
    
    $l = $this->bootstrap->getLocalization();
    $this->controller->toSmarty['title'] = $l('users', 'invite_title');
    
  }
  
  public function onComplete() {
    
    $values    = $this->form->getElementValues( 0 );
    $invModel  = $this->bootstrap->getModel('users_invitations');
    $crypto    = $this->bootstrap->getEncryption();
    $queue     = $this->bootstrap->getMailqueue();
    $l         = $this->bootstrap->getLocalization();
    $user      = $this->bootstrap->getSession('user');
    /*
    // folosleges feltakaritas?
    $invModel->addFilter('userid', $user['id'] );
    $invModel->addFilter('email', $values['email'], false, false );
    $invitations = $invModel->getArray();
    
    if ( !empty( $invitations ) )
      foreach( $invitations as $invitation )
        $invModel->delete( $invitation['id'] );
    */
    $values['permissions']    = implode('|', $values['permissions'] );
    $values['validationcode'] = $crypto->randomPassword( 10 );
    $values['userid']         = $user['id'];
    
    $invModel->insert( $values );
    
    $invModel->row['id'] = $crypto->asciiEncrypt( $invModel->row['id'] );
    $this->controller->toSmarty['values'] = $invModel->row;
    
    $queue->sendHTMLEmail(
      $values['email'],
      $l('users', 'invitationmailsubject'),
      $this->controller->fetchSmarty('Visitor/Users/Email/Invitation.tpl')
    );
    
    $this->controller->redirectWithMessage('users/admin', $l('users', 'user_invited') );
    
  }
  
}
