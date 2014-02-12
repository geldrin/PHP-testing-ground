<?php
namespace Visitor\Users\Form;
class MassInvite extends \Visitor\HelpForm {
  public $configfile = 'MassInvite.php';
  public $template   = 'Visitor/genericform.tpl';
  public $needdb     = true;
  
  protected $l;
  protected $crypto;
  
  public function postSetupForm() {
    
    $l = $this->bootstrap->getLocalization();
    $this->controller->toSmarty['title']     = $l('users', 'massinvite_title');
    $this->controller->toSmarty['helpclass'] = 'small right';
    
  }
  
  public function onComplete() {
    
    $values    = $this->form->getElementValues( 0 );
    $invModel  = $this->bootstrap->getModel('users_invitations');
    $user      = $this->bootstrap->getSession('user');
    
    $this->controller->toSmarty['user'] = $user;
    $l = $this->l = $this->bootstrap->getLocalization();
    $this->crypto = $this->bootstrap->getEncryption();
    
    if ( $values['delimeter'] == 'tab' )
      $values['delimeter'] = "\t";
    
    if ( !isset( $_FILES['invitefile'] ) or $_FILES['invitefile']['error'] != 0 ) {
      
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
    
    $permissions = implode('|', $values['permissions'] );
    $departments = implode('|', $values['departments'] );
    $groups      = implode('|', $values['groups'] );
    $userid      = $user['id'];
    $disabledafter = ( $values['needtimestampdisabledafter'] )
      ? $values['timestampdisabledafter']
      : null
    ;
    
    $invitecount = 0;
    foreach( $users as $email => $username ) {
      
      $invite = array(
        'email'          => $email,
        'name'           => $username,
        'userid'         => $userid,
        'groups'         => $groups,
        'departments'    => $departments,
        'permissions'    => $permissions,
        'validationcode' => $this->crypto->randomPassword( 10 ),
        'timestampdisabledafter' => $disabledafter,
      );
      
      $invModel->insert( $invite );
      $this->sendEmail( $invModel->row );
      $invitecount++;
      
    }
    
    $messages = $this->form->getMessages();
    
    $thousandsseparator = ' ';
    if ( \Springboard\Language::get() == 'en' )
      $thousandsseparator = ',';
    
    $invitecount = number_format( $invitecount, 0, '.', $thousandsseparator );
    
    if ( empty( $messages ) )
      $this->controller->redirectWithMessage(
        'users/admin',
        sprintf( $l('users', 'users_invited'), $invitecount )
      );
    
    $this->controller->toSmarty['sessionmessage'] = sprintf(
      $l('users', 'users_invited'),
      $invitecount
    );
    
  }
  
  public function sendEmail( &$values ) {
    
    $l = $this->l;
    $values['id'] = $this->crypto->asciiEncrypt( $values['id'] );
    $this->controller->toSmarty['values'] = $values;
    $this->controller->sendOrganizationHTMLEmail(
      $values['email'],
      $l('users', 'invitationmailsubject'),
      $this->controller->fetchSmarty('Visitor/Users/Email/MassInvitation.tpl')
    );
    
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
