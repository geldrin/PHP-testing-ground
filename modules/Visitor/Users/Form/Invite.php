<?php
namespace Visitor\Users\Form;
class Invite extends \Visitor\HelpForm {
  public $configfile = 'Invite.php';
  public $template   = 'Visitor/Users/Invite.tpl';
  public $needdb     = true;

  protected $l;
  protected $crypto;

  public function postSetupForm() {

    $l = $this->bootstrap->getLocalization();
    $this->controller->toSmarty['title']     = $l('users', 'invite_title');
    $this->controller->toSmarty['helpclass'] = 'small right';

  }

  public function onComplete() {

    $values       = $this->form->getElementValues( 0 );
    $user         = $this->bootstrap->getSession('user');
    $userModel    = $this->bootstrap->getModel('users');
    $invModel     = $this->bootstrap->getModel('users_invitations');
    $l = $this->l = $this->bootstrap->getLocalization();
    $this->crypto = $this->bootstrap->getEncryption();

    switch( $values['usertype'] ) {
      case 'single':
        $users = array(
          $values['email'] => null,
        );
        break;

      case 'multiple':

        if ( $values['delimeter'] == 'tab' )
          $values['delimeter'] = "\t";

        if (
             !isset( $_FILES['invitefile'] ) or
             $_FILES['invitefile']['error'] != 0
           ) {

          $this->form->addMessage( $l('users', 'invitefile_help') );
          $this->form->invalidate();
          return;

        }

        $users = $this->parseInviteFile(
          $_FILES['invitefile']['tmp_name'],
          $values['encoding'],
          $values['delimeter']
        );

        if ( !$this->form->validate() ) // a parseInviteFile hibat talalt es invalidalta a formot
          return;

        break;

    }

    $permissions = $departments = $groups = '';

    if ( !empty( $values['permissions'] ) )
      $permissions = implode('|', $values['permissions'] );

    if ( !empty( $values['departments'] ) )
      $departments = implode('|', $values['departments'] );

    if ( !empty( $values['groups'] ) )
      $groups      = implode('|', $values['groups'] );

    $userid        = $user['id'];
    $disabledafter = ( $values['needtimestampdisabledafter'] )
      ? $values['timestampdisabledafter']
      : null
    ;

    $timestamp   = date('Y-m-d H:i:s');
    $invitecount = 0;
    $emails      = array_keys( $users );
    $userids     = $userModel->searchEmails(
      $emails,
      $this->controller->organization['id']
    );
    $template    = $this->handleTemplate( $userModel, $values );
    $templateid  = null;
    if ( !empty( $template ) and $template['id'] )
      $templateid = $template['id'];

    if ( !empty( $userids ) )
      include_once(
        $this->bootstrap->config['templatepath'] .
        'Plugins/modifier.nameformat.php'
      );

    foreach( $users as $email => $username ) {

      $invite = array(
        'email'                  => $email,
        'name'                   => $username, // null lesz ha meg nincs ilyen emaillel user es non-csv az invite
        'userid'                 => $userid,
        'groups'                 => $groups,
        'departments'            => $departments,
        'permissions'            => $permissions,
        'validationcode'         => $this->crypto->randomPassword( 10 ),
        'timestampdisabledafter' => $disabledafter,
        'status'                 => 'invited',
        'organizationid'         => $this->controller->organization['id'],
        'timestamp'              => $timestamp,
        'templateid'             => $templateid,
      );

      // mert a contenttype nocontent|recordingid|livefeedid|channelid lehet
      switch( $values['contenttype'] ) {
        case 'recordingid':
        case 'livefeedid':
        case 'channelid':
          $invite[ $values['contenttype'] ] = $values[ $values['contenttype'] ];
          break;
        case 'nocontent':
          break;

        default:
          throw new \Exception('Unhandled contenttype: ' . $values['contenttype'] );
          break;

      }

      if ( isset( $userids[ $email ] ) ) {

        $invite['registereduserid'] = $userids[ $email ]['id'];
        $invite['status']           = 'existing';
        $invite['name']             = smarty_modifier_nameformat(
          $userids[ $email ]
        );
        $userModel->id = $userids[ $email ]['id'];
        $userModel->applyInvitationPermissions( $invite );

      }

      $invModel->insert( $invite );
      $this->controller->sendInvitationEmail( $invModel->row );
      $invitecount++;

    }

    $messages = $this->form->getMessages();

    $thousandsseparator = ' ';
    if ( \Springboard\Language::get() == 'en' )
      $thousandsseparator = ',';

    $invitecount = number_format( $invitecount, 0, '.', $thousandsseparator );
    if ( $invitecount == '1' )
      $redirmessage = $l('users', 'user_invited');
    else
      $redirmessage = sprintf( $l('users', 'users_invited'), $invitecount );

    if ( empty( $messages ) )
      $this->controller->redirectWithMessage(
        'users/invitations',
        $redirmessage
      );

    $this->controller->toSmarty['sessionmessage'] = $redirmessage;

  }

  public function parseInviteFile( $file, $encoding, $delimeter ) {

    include_once( $this->bootstrap->config['libpath'] . 'clonefish/constants.php');
    $l              = $this->l;
    $organizationid = $this->controller->organization['id'];
    $usersModel     = $this->bootstrap->getModel('users');

    if ( filesize( $file ) > 5242880 ) { // nagyobb mint 5 mega

      $this->form->addMessage( $l('users', 'invitefiletoobig') );
      $this->form->invalidate();
      return;

    }

    //UTF16LE-BOM -> utf8
    $data = file_get_contents( $file );
    $boms = array(
      'UTF-32BE' => chr(0x00) . chr(0x00) . chr(0xFE) . chr(0xFF),
      'UTF-32LE' => chr(0xFF) . chr(0xFE) . chr(0x00) . chr(0x00),
      'UTF-16BE' => chr(0xFE) . chr(0xFF),
      'UTF-16LE' => chr(0xFF) . chr(0xFE),
      'UTF-8'    => chr(0xEF) . chr(0xBB) . chr(0xBF),
    );

    // BOM torles
    foreach( $boms as $bomencoding => $bom ) {

      if ( substr( $data, 0, strlen( $bom ) ) == $bom ) {

        $data = substr( $data, strlen( $bom ) );
        break;

      }

    }

    $data = mb_convert_encoding( $data, 'UTF-8', $encoding );
    file_put_contents( $file, $data );
    unset( $data );

    ini_set( 'auto_detect_line_endings', true );
    $fhandle = fopen( $file, 'rb' );
    $line    = 0;
    $users   = array();

    while( ( $row = fgetcsv( $fhandle, 0, $delimeter ) ) !== false ) {

      $line++;
      if ( empty( $row )  or count( $row ) != 2 ) {

        $this->form->addMessage( $l('users', 'invitefileinvalid') );
        $this->form->invalidate();
        return;

      }

      $username = trim( $row[0] ); // elso oszlop a nev
      $email    = trim( $row[1] ); // masodik email
      $lineerror = false;

      if ( !preg_match( CF_EMAIL, $email ) ) {

        if ( $line == 1 ) // elso sor, ugorjuk at
          continue;

        $this->form->addMessage(
          sprintf( $l('users', 'invitefileinvalidemail'), $line )
        );

        $lineerror = true;

      }

      if ( isset( $users[ $email ] ) ) {

        $this->form->addMessage(
          sprintf( $l('users', 'invitefileinvalidduplicateemail'), $line )
        );

        $lineerror = true;

      }

      if ( $usersModel->emailExists( $email, $organizationid ) ) {

        $this->form->addMessage(
          sprintf( $l('users', 'invitefileinvalidexistingemail'), $line )
        );

        $lineerror = true;

      }

      if ( !$lineerror )
        $users[ $email ] = $username;

    }

    return $users;

  }

}
