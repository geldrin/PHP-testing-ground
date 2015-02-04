<?php
namespace Visitor\Users\Form;
class Edit extends \Visitor\HelpForm {
  public $configfile = 'Edit.php';
  public $template = 'Visitor/Users/Edit.tpl';
  public $needdb = true;
  public $userModel;
  public $user;
  public $basefields = array(
    'nickname', 'nameprefix', 'namefirst', 'namelast', 'nameformat',
    'password', 'confirmpassword', 'externalid', 'groups', 'departments',
    'needtimestampdisabledafter', 'timestampdisabledafter',
  );

  public function init() {
    
    parent::init();
    $l               = $this->bootstrap->getLocalization();
    $this->userModel = $this->controller->modelOrganizationAndIDCheck(
      'users',
      $this->application->getNumericParameter('id')
    );
    $this->values    = $this->userModel->row;
    $this->values['lastloggedinipaddress'] = trim(
      str_replace( 'REMOTE_ADDR:', '', $this->values['lastloggedinipaddress'] )
    );
    unset( $this->values['password'] );
    
    $this->values['permissions'] = array();
    foreach( $l->getLov('permissions') as $k => $v ) {
      
      if ( $this->values[ $k ] )
        $this->values['permissions'][] = $k;
      
    }
    
    if ( $this->values['timestampdisabledafter'] ) {
      $this->values['needtimestampdisabledafter'] = 1;
      $this->values['timestampdisabledafter']     =
        substr( $this->values['timestampdisabledafter'], 0, 16 )
      ;
    }

    $this->controller->toSmarty['channels'] =
      $this->userModel->getRecordingsProgressWithChannels(
        $this->controller->organization['id']
      )
    ;
    
    $this->controller->toSmarty['invitations'] =
      $this->userModel->getInvitations(
        $this->controller->organization['id']
      )
    ;

  }
  
  public function onComplete() {
    
    $values = $this->form->getElementValues( 0 );
    $crypt  = $this->bootstrap->getEncryption();
    $l      = $this->bootstrap->getLocalization();
    
    // ezeket a user sohase valtoztathatja
    unset(
      $values['email'],
      $values['lastloggedin'],
      $values['lastloggedinipaddress']
    );

    // nem localisan regisztralt usereknel nem engedunk permissiont allitani
    if ( $this->userModel->row['source'] !== 'local' ) {

      foreach( $this->basefields as $field )
        unset( $values[ $field ] );

    }

    foreach( $l->getLov('permissions') as $k => $v ) {
      
      if ( isset( $_REQUEST['permissions'][ $k ] ) and in_array( $k, $values['permissions'] ) )
        $values[ $k ] = 1;
      else
        $values[ $k ] = 0;
      
    }

    if ( !@$values['password'] )
      unset( $values['password'] );
    else
      $values['password'] = $crypt->getPasswordHash( $values['password'] );
    
    $this->userModel->clearDepartments();
    if ( isset( $_REQUEST['departments'] ) and !empty( $values['departments'] ) )
      $this->userModel->addDepartments( $values['departments'] );
    
    $this->userModel->clearGroups();
    if ( isset( $_REQUEST['groups'] ) and !empty( $values['groups'] ) )
      $this->userModel->addGroups( $values['groups'] );
    
    unset( $values['departments'], $values['groups'] );
    
    if ( !@$values['needtimestampdisabledafter'] )
      $values['timestampdisabledafter'] = null;

    $this->userModel->updateRow( $values );
    
    $forward = $this->application->getParameter('forward', 'users/admin');
    $this->controller->redirectWithMessage( $forward, $l('users', 'usermodified') );
    
  }
  
}
