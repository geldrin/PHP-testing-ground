<?php
namespace Visitor\Users\Form;
class Invite extends \Visitor\HelpForm {
  public $configfile = 'Invite.php';
  public $template   = 'Visitor/genericform.tpl';
  public $needdb     = true;
  
  public function postSetupForm() {
    
    $l = $this->bootstrap->getLocalization();
    $this->controller->toSmarty['title']     = $l('users', 'invite_title');
    $this->controller->toSmarty['helpclass'] = 'small right';
    
  }
  
  public function onComplete() {
    
    $values = $this->form->getElementValues( 0 );
    $l      = $this->bootstrap->getLocalization();
    $values['organizationid']       = $this->controller->organization['id'];
    $values['timestamp']            = date('Y-m-d H:i:s');
    $values['invitationvaliduntil'] = date('Y-m-d H:i:s', strtotime('+1 month') );
    $values['status']               = 'invited';

    $this->addInvitation( $values );
    $this->controller->redirectWithMessage('users/admin', $l('users', 'user_invited') );
    
  }
  
  public function addInvitation( &$values ) {
    
    $invModel  = $this->bootstrap->getModel('users_invitations');
    $crypto    = $this->bootstrap->getEncryption();
    $l         = $this->bootstrap->getLocalization();
    $user      = $this->bootstrap->getSession('user');
    
    if ( is_array( $values['permissions'] ) )
      $values['permissions']  = implode('|', $values['permissions'] );
    
    $values['validationcode'] = $crypto->randomPassword( 10 );
    $values['userid']         = $user['id'];
    
    if ( isset( $values['departments'] ) and is_array( $values['departments'] ) )
      $values['departments']  = implode('|', $values['departments'] );
    
    if ( isset( $values['groups'] ) and is_array( $values['groups'] ) )
      $values['groups']       = implode('|', $values['groups'] );
    
    if ( !@$values['needtimestampdisabledafter'] )
      unset( $values['timestampdisabledafter'] );
    
    $invModel->insert( $values );
    
    $invModel->row['id'] = $crypto->asciiEncrypt( $invModel->row['id'] );
    $this->controller->toSmarty['values'] = $invModel->row;
    $this->controller->toSmarty['user']   = $user;
    
    $this->controller->sendOrganizationHTMLEmail(
      $values['email'],
      $l('users', 'invitationmailsubject'),
      $this->controller->fetchSmarty('Visitor/Users/Email/Invitation.tpl')
    );
    
  }
  
}
