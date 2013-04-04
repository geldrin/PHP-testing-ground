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
    
    $values['permissions']    = implode('|', $values['permissions'] );
    $values['validationcode'] = $crypto->randomPassword( 10 );
    $values['userid']         = $user['id'];
    
    if ( !empty( $values['departments'] ) )
      $values['departments']  = implode('|', $values['departments'] );
    
    if ( !empty( $values['groups'] ) )
      $values['groups']       = implode('|', $values['groups'] );
    
    $invModel->insert( $values );
    
    $invModel->row['id'] = $crypto->asciiEncrypt( $invModel->row['id'] );
    $this->controller->toSmarty['values'] = $invModel->row;
    $this->controller->toSmarty['user']   = $user;
    
    $queue->sendHTMLEmail(
      $values['email'],
      $l('users', 'invitationmailsubject'),
      $this->controller->fetchSmarty('Visitor/Users/Email/Invitation.tpl')
    );
    
    $this->controller->redirectWithMessage('users/admin', $l('users', 'user_invited') );
    
  }
  
}
