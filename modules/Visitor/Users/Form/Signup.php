<?php
namespace Visitor\Users\Form;
class Signup extends \Visitor\Form {
  public $configfile = 'Signup.php';
  public $template   = 'Visitor/genericform.tpl';
  public $needdb     = true;
  public $invite; // akkor kap erteket ha az invitacio letezik

  public function init() {
    $inviteid = $this->application->getNumericParameter('inviteid');
    if ( $inviteid ) {
      $invitationModel = $this->bootstrap->getModel('users_invitations');
      $invitationModel->select( $inviteid );
      $this->invite = $invitationModel->row;
    }

    if (
         !$this->invite and
         $this->controller->organization['registrationtype'] == 'closed'
       )
      $this->controller->redirectToController('contents', 'noregistration');
    
    $this->controller->toSmarty['formclass'] = 'halfbox centerformwrap';
    $this->controller->toSmarty['titleclass'] = 'center';
    $this->controller->toSmarty['needselect2'] = true;
    parent::init();
  }

  public function postSetupForm() {
    
    $l = $this->bootstrap->getLocalization();
    // submit legyen a title
    $this->form->submit =
    $this->controller->toSmarty['title'] = $l('users', 'register_title');
    
  }
  
  public function onComplete() {
    
    $values    = $this->form->getElementValues( 0 );
    $userModel = $this->bootstrap->getModel('users');
    $crypto    = $this->bootstrap->getEncryption();
    $l         = $this->bootstrap->getLocalization();

    $values['timestamp']      = date('Y-m-d H:i:s');
    $values['lastloggedin']   = $values['timestamp'];
    $values['browser']        = $_SERVER['HTTP_USER_AGENT'];
    $values['disabled']       = $userModel::USER_UNVALIDATED;
    $values['validationcode'] = $crypto->randomPassword( 10 );
    $values['password']       = $crypto->getPasswordHash( $values['password'] );
    $values['language']       = \Springboard\Language::get();
    $values['organizationid'] = $this->controller->organization['id'];
    $values['source']         = 'local';

    if ( $this->invite ) {

      // a meghivo fel lett hasznalva
      if ( $this->invite['status'] != 'invited' ) {

        // es a user mar be is van lepve, kuldjuk el az urlre ha van
        $user = $this->bootstrap->getSession('user');
        if (
             $user['id'] and
             $user['id'] == $this->invite['registereduserid'] and
             $this->invite['customforwardurl']
           )
          $this->controller->redirect(
            $this->invite['customforwardurl']
          );

        // invalid mert vagy nincs belepve es mar felhasznalt, vagy rosz user
        $this->controller->addMessage( $l('users', 'invitation_invalid') );
        $this->form->addMessage( $l('users', 'invitation_invalid') );
        $this->form->invalidate();
        return;
      }
    }

    $userModel->insert( $values );

    $invitation = $this->invite;
    if (
         $invitation or // megvan a meghivonk
         ( // vagy keresni kell
           $invitation = $userModel->searchForValidInvitation(
             $this->controller->organization['id']
           )
         )
       ) {
      $userModel->applyInvitationPermissions( $invitation );
      $userModel->invitationRegistered( $this->invite['id'] );

      // hozzacsapjuk a validacios url-hez az invitaciot hogy mindig
      // mukodjon a customurlforward
      $this->controller->toSmarty['invitationid'] =
        $crypto->asciiEncrypt( $invitation['id'] )
      ;
    }

    $userModel->row['id'] = $crypto->asciiEncrypt( $userModel->id );
    $this->controller->toSmarty['values'] = $userModel->row;

    $emailsubject = @$this->controller->organization['signupvalidationemailsubject'];
    if ( !$emailsubject )
      $l('users', 'validationemailsubject');

    $this->controller->sendOrganizationHTMLEmail(
      $userModel->row['email'],
      $l('users', 'validationemailsubject'),
      $this->controller->fetchSmarty('Visitor/Users/Email/Validation.tpl')
    );
    
    $this->controller->redirect('contents/needvalidation');
    
  }
  
}
