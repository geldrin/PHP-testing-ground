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
  private $userids; // lookup table email => userinfoval
  private $baseuri; // cache mezo csv meghivo urlhez

  public function postSetupForm() {

    $l = $this->bootstrap->getLocalization();
    $this->controller->toSmarty['title']     = $l('users', 'invite_title');
    $this->controller->toSmarty['helpclass'] = 'small right';

  }

  public function onComplete() {
    $this->bootstrap->includeTemplatePlugin('nameformat');
    $values       = $this->form->getElementValues( 0 );
    $userModel    = $this->bootstrap->getModel('users');
    $this->crypto = $this->bootstrap->getEncryption();
    $l = $this->l = $this->bootstrap->getLocalization();
    $externalsend = $values['externalsend'] === 'external';

    // non-authoritative lookup table: email => user(ha van)
    $emails = $this->getEmailsByType( $values['usertype'], $values );
    if ( $emails === null ) // hiba, a form mar invalidalva
      return;

    // authoritative lookup table: email => user
    $this->userids = $this->emailsToUsers( $emails );
    // a handleTemplate a \Visitor\Form -bol
    $template = $this->handleTemplate( $userModel, $values );

    $templateid  = null;
    if ( !empty( $template ) and $template['id'] )
      $templateid = $template['id'];

    // default template allitas
    if ( !$templateid and !$externalsend )
      $this->controller->toSmarty['template'] = array(
        'subject' => $l('users', 'templatesubject_default'),
      );

    // error check
    $messages = $this->form->getMessages();

    if ( $externalsend )
      $this->initCSVOutput( $values );

    // kuldes
    $invitecount = $this->insertInvitations( $emails, $values, $templateid );

    // user feedback
    $invitecount = $this->formatInviteCount( $invitecount );
    if ( $invitecount == '1' )
      $redirmessage = $l('users', 'user_invited');
    else
      $redirmessage = sprintf( $l('users', 'users_invited'), $invitecount );

    // ha nincs csv kuldes akkor rogton redirect
    if ( empty( $messages ) and !$externalsend )
      $this->controller->redirectWithMessage(
        'users/invitations',
        $redirmessage
      );

    // amugy ki kell kuldjuk a csv-t es utana ha betoltodik
    // az oldal mutatjuk az uzenetet
    $this->controller->toSmarty['sessionmessage'] = $redirmessage;

    // ha csv kuldes es kuldtunk is valamit akkor elhalunk
    if ( $externalsend and $invitecount )
      die();
  }

  // duplikatumok nelkuli email lookup tablet ad vissza,
  // email => userinfo(ha van, amugy null)
  // nem authoritative a userinfo, kesobb biztosra megyunk
  private function getEmailsByType( $type, &$values ) {
    $l = $this->l;

    switch( $type ) {
      case 'single':
        $emails = array(
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
          return null;

        }

        $emails = $this->parseInviteFile(
          $_FILES['invitefile']['tmp_name'],
          $values['encoding'],
          $values['delimeter']
        );

        // fatal hiba tortent, mindenkeppen irunk ki hibat
        if ( $emails === false ) {
          $this->form->addMessage( $l('users', 'invite_fatalerror') );
          return null;
        }

        // a parseInviteFile hibat talalt es invalidalta a formot
        // de csak akkor adunk hibauzenetet ha nem csv export,
        // vagy nincs kinek kuldeni emailt
        $externalsend = $values['externalsend'] === 'external';
        if (
            !$this->form->validate() and
            ( !$externalsend or empty( $emails ) )
           )
          return null;

        break;

    }

    return $emails;
  }

  // adott emailekkel rendelkezu userek kereses, es
  // lookup table keszitese: email => userinfo
  // ez mar authoritative info
  private function emailsToUsers( &$emails ) {
    $onlyemails = array_keys( $emails );
    $userModel  = $this->bootstrap->getModel('users');
    return $userModel->searchEmails(
      $onlyemails,
      $this->controller->organization['id']
    );
  }

  // a kozos invite ami mindenkinel ugyanaz
  // TODO dinamikus privilegiumok rework
  private function assembleBaseInvite( &$values, $templateid ) {
    $user       = $this->bootstrap->getSession('user');
    $baseInvite = array(
      'groups'                 => !empty( $values['groups'] )
        ? implode('|', $values['groups'] )
        : ''
      ,
      'departments'            => !empty( $values['departments'] )
        ? implode('|', $values['departments'] )
        : ''
      ,
      'permissions'            => !empty( $values['permissions'] )
        ? implode('|', $values['permissions'] )
        : ''
      ,
      'timestampdisabledafter' => $values['needtimestampdisabledafter']
        ? $values['timestampdisabledafter']
        : null
      ,
      'userid'                 => $user['id'], // a meghivo felhasznalo
      'status'                 => 'invited',
      'organizationid'         => $this->controller->organization['id'],
      'timestamp'              => date('Y-m-d H:i:s'),
      'templateid'             => $templateid,
      'invitationvaliduntil'   => $values['invitationvaliduntil'],
      'customforwardurl'       => $this->getBaseForwardURL( $values ),
    );

    return $baseInvite;
  }

  // db insert, es email kuldes / csv output a feleloseg
  // a konkret emailt a controller kuldi ki, a csv-ket helybe intezzuk
  private function insertInvitations( &$emails, &$values, $templateid ) {
    $invModel    = $this->bootstrap->getModel('users_invitations');
    $userModel   = $this->bootstrap->getModel('users');
    $baseInvite  = $this->assembleBaseInvite( $values, $templateid );
    $invitecount = 0;

    $externalsend = $values['externalsend'] === 'external';
    $forwardurl   = $baseInvite['customforwardurl'];
    foreach( $emails as $email => $name ) {
      $invite = array_merge(
        $baseInvite,
        array(
          'email'          => $email,
          'namefirst'      => $name['namefirst'],
          'namelast'       => $name['namelast'],
          'validationcode' => $this->crypto->randomPassword( 10 ),
        )
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

      // letezik user ezzel az email-el, pontos adatokat tole
      if ( isset( $this->userids[ $email ] ) ) {

        $invite['registereduserid'] = $this->userids[ $email ]['id'];
        $invite['status']           = 'existing';
        $invite['namefirst']        = $this->userids[ $email ]['namefirst'];
        $invite['namelast']         = $this->userids[ $email ]['namelast'];
        $userModel->id = $this->userids[ $email ]['id'];
        $userModel->applyInvitationPermissions( $invite );

      }

      $invModel->insert( $invite );

      if ( !$externalsend )
        $this->controller->sendInvitationEmail( $invModel->row );
      else
        $this->handleCSVOutput( $invModel->row, $forwardurl );

      $invitecount++;
    }

    return $invitecount;
  }

  // az alap forward url, lehet ures
  // arra a contentre visz amire meghivtak a usert (ha van)
  private function getBaseForwardURL( &$values ) {
    if ( $values['customforwardurl'] )
      return $values['customforwardurl'];

    $module     = '';
    $forwardurl = '';
    // mert a contenttype nocontent|recordingid|livefeedid|channelid lehet
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
      case 'nocontent':
        return '';
        break;
      default:
        throw new \Exception('Unhandled contenttype: ' . $values['contenttype'] );
        break;
    }

    $forwardurl =
      \Springboard\Language::get() . '/' . $module .
      $values[ $values['contenttype'] ] . '-'
    ;

    if ( $values['externalsend'] === 'external' ) {
      $obj->select( $values[ $values['contenttype'] ] );
      $title = '';
      if ( isset( $obj->row['title'] ) )
        $title = $obj->row['title'];
      elseif ( isset( $obj->row['name'] ) )
        $title = $obj->row['name'];

      $forwardurl .= \Springboard\Filesystem::filenameize( $title );
    }

    return $forwardurl;
  }

  // user, csv specific forward url ahol mindenkeppen muszaj hogy legyen
  private function getForwardURL( &$invite ) {
    $forwardurl = $invite['customforwardurl'];

    // ha mar van user-je akkor be kell lepjen,
    // amugy regisztralas utan kell elkuldenunk a forwardurl-re ha van
    if ( isset( $invite['registereduserid'] ) and $invite['registereduserid'] )
      $url = $this->baseuri . 'login';
    else
      $url =
        $this->baseuri . 'validateinvite/' .
        $this->crypto->asciiEncrypt( $invite['id'] ) . '-' .
        $invite['validationcode']
      ;

    if ( $forwardurl )
      $url .= '?forward=' . rawurlencode( $forwardurl );

    return $url;
  }

  public function parseInviteFile( $file, $encoding, $delimeter ) {

    include_once( $this->bootstrap->config['libpath'] . 'clonefish/constants.php');
    $l = $this->l;

    if ( filesize( $file ) > 5242880 ) { // nagyobb mint 5 mega

      $this->form->addMessage( $l('users', 'invitefiletoobig') );
      $this->form->invalidate();
      return false;

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
    $fatalerror = false;
    while( ( $row = fgetcsv( $fhandle, 0, $delimeter ) ) !== false ) {

      $line++;
      if ( empty( $row )  or count( $row ) != 3 ) {

        $this->form->addMessage( $l('users', 'invitefileinvalid') );
        $this->form->invalidate();
        return false;

      }

      $firstname = trim( $row[0] ); // elso oszlop a keresztnev
      $lastname  = trim( $row[1] ); // masodik a csaladnev
      $email     = trim( $row[2] ); // harmadik email
      $email     = mb_strtolower( $email ); // mindig kisbetus legyen
      $lineerror = false;

      if ( !preg_match( CF_EMAIL, $email ) ) {

        if ( $line == 1 ) // elso sor, ugorjuk at
          continue;

        $this->form->addMessage(
          sprintf(
            $l('users', 'invitefileinvalidemail'),
            htmlspecialchars( '"' . $email . '"', ENT_QUOTES, 'UTF-8' ),
            $line
          )
        );

        $lineerror = true;
        $fatalerror = true;

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

    if ( $fatalerror )
      return false;

    return $users;
  }

  private function initCSVOutput( &$values ) {
    // nem tortenhet meg
    if ( $values['externalsend'] !== 'external' )
      throw new \Exception('initCSVOutput called for non-external invite');

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

  private function handleCSVOutput( $invite, $forwardurl ) {
    // nincs hova irni? megszakadt a tcp kapcsolat
    if ( !$this->csvHandle )
      return;

    // sorrend fontos, lasd initCSVOutput!
    $values = array(
      trim( $invite['namefirst'] ),
      trim( $invite['namelast'] ),
      $invite['email'],
      $this->getForwardURL( $invite ),
    );

    $success = fputcsv( $this->csvHandle, $values, $this->csvDelimiter );
    if ( $success !== false )
      return;

    // nem sikerult kiirni a csv-t, kuldjunk rola emailt
    $d = \Springboard\Debug::getInstance();
    $d->log(
      false,
      false,
      "Failed to putcsv!\n" .
      \Springboard\Debug::getRequestInformation(),
      true
    );
  }

  private function formatInviteCount( $invitecount ) {
    $thousandsseparator = ' ';
    if ( \Springboard\Language::get() == 'en' )
      $thousandsseparator = ',';

    return number_format( $invitecount, 0, '.', $thousandsseparator );
  }
}
