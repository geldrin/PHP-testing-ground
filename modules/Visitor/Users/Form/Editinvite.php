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

    if ( $this->values['invitationvaliduntil'] ) {
      $this->values['invitationvaliduntil']     =
        substr( $this->values['invitationvaliduntil'], 0, 16 )
      ;
    }

    $this->controller->toSmarty['title'] = $l('users', 'invitation_modify');

  }
  
  public function onComplete() {
    
    $values = $this->form->getElementValues( 0 );
    $crypt  = $this->bootstrap->getEncryption();
    $l      = $this->bootstrap->getLocalization();
    $resend = false; // kell e ujrakuldeni az emailt

    if ( !empty( $values['permissions'] ) )
      $values['permissions'] = implode('|', $values['permissions'] );
    else
      $values['permissions'] = '';

    if ( !empty( $values['departments'] ) )
      $values['departments'] = implode('|', $values['departments'] );
    else
      $values['departments'] = '';

    if ( !empty( $values['groups'] ) )
      $values['groups']      = implode('|', $values['groups'] );
    else
      $values['groups']      = '';

    if ( !$values['needtimestampdisabledafter'] )
      $values['timestampdisabledafter'] = null;

    $userModel   = $this->bootstrap->getModel('users');
    $template    = $this->handleTemplate( $userModel, $values );
    $templateid  = null;
    if ( !empty( $template ) and $template['id'] )
      $templateid = $template['id'];

    $values['templateid'] = $templateid;

    // ha valtozott az email akkor ujrakuldjuk
    if (
         isset( $values['email'] ) and $values['email'] and
         $this->invitationModel->row['email'] and
         $this->invitationModel->row['email'] != $values['email']
       )
      $resend = true;

    $this->invitationModel->updateRow( $values );

    if ( $resend ) {
      $this->controller->sendInvitationEmail( $this->invitationModel->row );
      $message = $l('users', 'usermodifiedandinvited');
    } else
      $message = $l('users', 'usermodified');

    $forward = $this->application->getParameter('forward', 'users/invitations');
    $this->controller->redirectWithMessage( $forward, $message );

  }

  public function insertIDAndTitle( &$string, $id, $title ) {
    $string = str_replace( 'value=""', "value=\"$id\"", $string );
    $string = str_replace( 'class="title">', "class=\"title\">$title", $string );
    return $string;
  }

}
