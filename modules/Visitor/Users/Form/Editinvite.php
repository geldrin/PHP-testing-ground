<?php
namespace Visitor\Users\Form;
class Editinvite extends \Visitor\HelpForm {
  public $configfile = 'Editinvite.php';
  public $template = 'Visitor/Users/Invite.tpl';
  public $needdb = true;
  public $invitationModel;
  public $user;
  
  public function init() {
    
    parent::init();
    $l               = $this->bootstrap->getLocalization();
    $this->invitationModel = $this->controller->modelOrganizationAndIDCheck(
      'users_invitations',
      $this->application->getNumericParameter('id')
    );
    $this->values    = $this->invitationModel->row;
    unset( $this->values['password'] );

    $this->values['permissions'] = explode('|', $this->values['permissions'] );
    $this->values['departments'] = explode('|', $this->values['departments'] );
    $this->values['groups']      = explode('|', $this->values['groups'] );

    if ( $this->values['recordingid'] )
      $this->values['contenttype'] = 'recordingid';
    elseif ( $this->values['channelid'] )
      $this->values['contenttype'] = 'channelid';
    elseif ( $this->values['livefeedid'] )
      $this->values['contenttype'] = 'livefeedid';
    else
      $this->values['contenttype'] = 'nocontent';

    if ( $this->values['timestampdisabledafter'] ) {
      $this->values['needtimestampdisabledafter'] = 1;
      $this->values['timestampdisabledafter']     =
        substr( $this->values['timestampdisabledafter'], 0, 16 )
      ;
    }

  }
  
  public function onComplete() {
    
    $values = $this->form->getElementValues( 0 );
    $crypt  = $this->bootstrap->getEncryption();
    $l      = $this->bootstrap->getLocalization();
    
    if ( !empty( $values['permissions'] ) )
      $values['permissions'] = implode('|', $values['permissions'] );

    if ( !empty( $values['departments'] ) )
      $values['departments'] = implode('|', $values['departments'] );

    if ( !empty( $values['groups'] ) )
      $values['groups']      = implode('|', $values['groups'] );

    if ( !$values['needtimestampdisabledafter'] )
      $values['timestampdisabledafter'] = null;

    $userModel   = $this->bootstrap->getModel('users');
    $template    = $this->handleTemplate( $userModel, $values );
    $templateid  = null;
    if ( !empty( $template ) and $template['id'] )
      $templateid = $template['id'];

    $values['templateid'] = $templateid;

    $this->invitationModel->updateRow( $values );
    
    $forward = $this->application->getParameter('forward', 'users/invitations');
    $this->controller->redirectWithMessage( $forward, $l('users', 'usermodified') );
    
  }

  public function insertIDAndTitle( &$string, $id, $title ) {
    $string = str_replace( 'value=""', "value=\"$id\"", $string );
    $string = str_replace( 'class="title">', "class=\"title\">$title", $string );
    return $string;
  }

}
