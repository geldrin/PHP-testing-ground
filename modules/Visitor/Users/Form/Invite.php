<?php
namespace Visitor\Users\Form;
class Invite extends \Visitor\HelpForm {
  public $configfile = 'Invite.php';
  public $template   = 'Visitor/Users/Invite.tpl';
  public $needdb     = true;

  protected $l;
  protected $crypto;

  private $csvHandle;
  private $csvDelimiter = ';';
  private $userids; // hashmap email -> userinfoval
  private $baseuri; // cache mezo csv meghivo urlhez

  public function postSetupForm() {

    $l = $this->bootstrap->getLocalization();
    $this->controller->toSmarty['title']     = $l('users', 'invite_title');
    $this->controller->toSmarty['helpclass'] = 'small right';

  }

  public function onComplete() {
    $this->bootstrap->includeTemplatePlugin('nameformat');
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

    $externalsend  = $values['externalsend'] === 'external';
    $userid        = $user['id'];
    $disabledafter = ( $values['needtimestampdisabledafter'] )
      ? $values['timestampdisabledafter']
      : null
    ;

    $timestamp   = date('Y-m-d H:i:s');
    $invitecount = 0;
    $emails      = array_keys( $users );
    $this->userids = $userModel->searchEmails(
      $emails,
      $this->controller->organization['id']
    );
    $template    = $this->handleTemplate( $userModel, $values );
    $templateid  = null;
    if ( !empty( $template ) and $template['id'] )
      $templateid = $template['id'];

    // default template allitas
    if ( !$templateid and !$externalsend )
      $this->controller->toSmarty['template'] = array(
        'subject' => $l('users', 'templatesubject_default'),
      );

    $module     = '';
    $forwardurl = '';
    switch( $values['contenttype'] ) {
      case 'recordingid':
        $module = 'recordings/details/';
        $obj = $this->bootstrap->getModel('recordings');
        break;
      case 'livefeedid':
        $module = 'live/view/';
        $obj = $this->bootstrap->getModel('livefeeds');
        break;
      case 'channelid':
        $module = 'channels/details/';
        $obj = $this->bootstrap->getModel('channels');
        break;
    }

    if ( $module and !$values['customforwardurl'] ) {
      $forwardurl =
        \Springboard\Language::get() . '/' . $module .
        $values[ $values['contenttype'] ] . '-'
      ;

      if ( $externalsend ) {
        $obj->select( $values[ $values['contenttype'] ] );
        $title = '';
        if ( isset( $obj->row['title'] ) )
          $title = $obj->row['title'];
        elseif ( isset( $obj->row['name'] ) )
          $title = $obj->row['name'];

        $forwardurl .= \Springboard\Filesystem::filenameize( $title );
      }
    }

    if ( $values['customforwardurl'] )
      $forwardurl = $values['customforwardurl'];

    // CHECK ERROR
    $messages = $this->form->getMessages();

    // KULSO KULDES, CSV AZ OUTPUT
    if ( $externalsend and empty( $messages ) ) {
      $this->crypto  = $this->bootstrap->getEncryption();
      $this->baseuri =
        $this->bootstrap->baseuri . \Springboard\Language::get() . '/users/'
      ;

      $filename = 'videosquare-userinvitation-' . date('YmdHis') . '.csv';
      $this->csvHandle = \Springboard\Browser::initCSVHeaders(
        $filename,
        array('firstname', 'lastname', 'email', 'customurl', ),
        $this->csvDelimiter
      );
    }

    foreach( $users as $email => $name ) {

      $invite   = array(
        'email'                  => $email,
        'namefirst'              => $name['namefirst'],
        'namelast'               => $name['namelast'],
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
        'invitationvaliduntil'   => $values['invitationvaliduntil'],
        'customforwardurl'       => $forwardurl,
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

      if ( isset( $this->userids[ $email ] ) ) {

        $invite['registereduserid'] = $this->userids[ $email ]['id'];
        $invite['status']           = 'existing';
        $invite['namefirst']        = $this->userids[ $email ]['namefirst'];
        $invite['namelast']         = $this->userids[ $email ]['namelast'];
        $userModel->id = $this->userids[ $email ]['id'];
        $userModel->applyInvitationPermissions( $invite );

      }

      $invModel->insert( $invite );
      $this->handleInvite( $externalsend, $invModel->row, $forwardurl );
      $invitecount++;

    }

    $thousandsseparator = ' ';
    if ( \Springboard\Language::get() == 'en' )
      $thousandsseparator = ',';

    $invitecount = number_format( $invitecount, 0, '.', $thousandsseparator );
    if ( $invitecount == '1' )
      $redirmessage = $l('users', 'user_invited');
    else
      $redirmessage = sprintf( $l('users', 'users_invited'), $invitecount );

    if ( empty( $messages ) and !$externalsend )
      $this->controller->redirectWithMessage(
        'users/invitations',
        $redirmessage
      );

    $this->controller->toSmarty['sessionmessage'] = $redirmessage;

    if ( $externalsend and empty( $messages ) )
      die();

  }

  public function parseInviteFile( $file, $encoding, $delimeter ) {

    include_once( $this->bootstrap->config['libpath'] . 'clonefish/constants.php');
    $l              = $this->l;

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
    foreach( $boms as $bom ) {

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
    $nameformat = \Springboard\Language::get() == 'hu'? 'straight': 'reverse';

    while( ( $row = fgetcsv( $fhandle, 0, $delimeter ) ) !== false ) {

      $line++;
      if ( empty( $row )  or count( $row ) != 3 ) {

        $this->form->addMessage( $l('users', 'invitefileinvalid') );
        $this->form->invalidate();
        return;

      }

      $firstname = trim( $row[0] ); // elso oszlop a keresztnev
      $lastname  = trim( $row[1] ); // masodik a csaladnev
      $email     = trim( $row[2] ); // harmadik email
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

      if ( !$lineerror )
        $users[ $email ] = array(
          'nameprefix' => '',
          'nameformat' => $nameformat,
          'namefirst'  => $firstname,
          'namelast'   => $lastname,
        );

    }

    return $users;

  }

  private function handleInvite( $externalsend, $invite, $forwardurl ) {
    if ( !$externalsend ) {
      $this->controller->sendInvitationEmail( $invite );
      return;
    }

    if ( isset( $invite['registereduserid'] ) )
      $url = $this->baseuri . 'login';
    else
      $url =
        $this->baseuri . 'validateinvite/' .
        $this->crypto->asciiEncrypt( $invite['id'] ) . '-' .
        $invite['validationcode']
      ;

    if ( $forwardurl )
      $url .= '?forward=' . rawurlencode( $forwardurl );

    $firstname = $invite['namefirst'];
    $lastname  = $invite['namelast'];
    if ( isset( $this->userids[ $invite['email'] ] ) ) {
      $user = $this->userids[ $invite['email'] ];
      $firstname = $user['namefirst'];
      $lastname  = $user['namelast'];
    }

    $values = array(
      trim($firstname),
      trim($lastname),
      $invite['email'],
      $url,
    );

    fputcsv( $this->csvHandle, $values, $this->csvDelimiter );
  }
}
