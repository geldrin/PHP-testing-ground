<?php
namespace Visitor\Users\Form;
class Signup extends \Visitor\HelpForm {
  public $configfile = 'Signup.php';
  public $template   = 'Visitor/genericform.tpl';
  public $needdb     = true;
  
  public function init() {
    $this->controller->toSmarty['helpclass'] = 'rightbox halfbox';
    parent::init();
  }
  
  public function postSetupForm() {
    
    $l = $this->bootstrap->getLocalization();
    $this->controller->toSmarty['title'] = $l('users', 'register_title');
    
  }
  
  public function onComplete() {
    
    $values    = $this->form->getElementValues( 0 );
    $userModel = $this->bootstrap->getModel('users');
    $crypto    = $this->bootstrap->getEncryption();
    $queue     = $this->bootstrap->getMailqueue();
    $l         = $this->bootstrap->getLocalization();
    $groupSession = $this->bootstrap->getSession('groupinvitation');
    $userinvitationSession = $this->bootstrap->getSession('userinvitation');
    
    $values['timestamp']      = date('Y-m-d H:i:s');
    $values['lastloggedin']   = $values['timestamp'];
    $values['browser']        = $_SERVER['HTTP_USER_AGENT'];
    $values['disabled']       = $userModel::USER_UNVALIDATED;
    $values['validationcode'] = $crypto->randomPassword( 10 );
    $values['password']       = $crypto->getHash( $values['password'] );
    $values['language']       = \Springboard\Language::get();
    $values['organizationid'] = $this->controller->organization['id'];
    
    if ( $invitation = $userinvitationSession['invitation'] ) {
      
      $invitationModel = $this->bootstrap->getModel('users_invitations');
      $invitationModel->select( $invitation['id'] );
      
      if ( !$invitationModel->row )
        throw new \Exception('No user invitation found with session data: ' . var_export( $invitation, true ) );
      
      foreach( explode('|', $invitation['permissions'] ) as $permission )
        $values[ $permission ] = 1;
      
      $userinvitationSession->clear();
      $invitationModel->delete( $invitationModel->id );
      
    }
    
    $userModel->insert( $values );
    
    $userModel->row['id'] = $crypto->asciiEncrypt( $userModel->id );
    $this->controller->toSmarty['values'] = $userModel->row;
    
    $queue->sendHTMLEmail(
      $userModel->row['email'],
      $l('users', 'validationemailsubject'),
      $this->controller->fetchSmarty('Visitor/Users/Email/Validation.tpl')
    );
    
    $this->controller->redirect('contents/needvalidation');
    
  }
  
}
