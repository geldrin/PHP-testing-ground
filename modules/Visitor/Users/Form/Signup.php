<?php
namespace Visitor\Users\Form;
class Signup extends \Visitor\HelpForm {
  public $configfile = 'Signup.php';
  public $template   = 'Visitor/genericform.tpl';
  public $needdb     = true;
  
  public function init() {
    
    $userinvitationSession = $this->bootstrap->getSession('userinvitation');
    
    if (
         !count( $userinvitationSession ) and
         $this->controller->organization['registrationtype'] == 'closed'
       )
      $this->controller->redirectToController('contents', 'noregistration');
    
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
    $l         = $this->bootstrap->getLocalization();
    $userinvitationSession = $this->bootstrap->getSession('userinvitation');
    
    $values['timestamp']      = date('Y-m-d H:i:s');
    $values['lastloggedin']   = $values['timestamp'];
    $values['browser']        = $_SERVER['HTTP_USER_AGENT'];
    $values['disabled']       = $userModel::USER_UNVALIDATED;
    $values['validationcode'] = $crypto->randomPassword( 10 );
    $values['password']       = $crypto->getPasswordHash( $values['password'] );
    $values['language']       = \Springboard\Language::get();
    $values['organizationid'] = $this->controller->organization['id'];
    
    if ( $invitation = $userinvitationSession['invitation'] ) {
      
      $invitationModel = $this->bootstrap->getModel('users_invitations');
      $invitationModel->select( $invitation['id'] );
      
      if ( !$invitationModel->row ) { // a meghivo mar kozben fel lett hasznalva
        
        $userinvitationSession->clear();
        $this->controller->addMessage( $l('users', 'invitation_invalid') );
        $this->form->addMessage( $l('users', 'invitation_invalid') );
        $this->form->invalidate();
        return;
        
      }
      
      foreach( explode('|', $invitation['permissions'] ) as $permission )
        $values[ $permission ] = 1;
      
      $departments = array();
      foreach( explode('|', $invitation['departments'] ) as $id ) {
        
        $id = intval( $id );
        if ( $id )
          $departments[] = $id;
        
      }
      
      $groups      = array();
      foreach( explode('|', $invitation['groups'] ) as $id ) {
        
        $id = intval( $id );
        if ( $id )
          $groups[] = $id;
        
      }
      
      if (
           isset( $invitation['timestampdisabledafter'] ) and
           $invitation['timestampdisabledafter']
         )
        $values['timestampdisabledafter'] = $invitation['timestampdisabledafter'];
      
      $userinvitationSession->clear();
      $invitationModel->delete( $invitationModel->id );
      
    }
    
    $userModel->insert( $values );
    
    if ( isset( $departments ) and !empty( $departments ) )
      $userModel->addDepartments( $departments );
    
    if ( isset( $groups ) and !empty( $groups ) )
      $userModel->addGroups( $groups );
    
    $userModel->row['id'] = $crypto->asciiEncrypt( $userModel->id );
    $this->controller->toSmarty['values'] = $userModel->row;
    
    $this->controller->sendOrganizationHTMLEmail(
      $userModel->row['email'],
      $l('users', 'validationemailsubject'),
      $this->controller->fetchSmarty('Visitor/Users/Email/Validation.tpl')
    );
    
    $this->controller->redirect('contents/needvalidation');
    
  }
  
}
