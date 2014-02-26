<?php
namespace Visitor\Users\Form;
class Invite extends \Visitor\HelpForm {
  public $configfile = 'Invite.php';
  public $template   = 'Visitor/genericform.tpl';
  public $needdb     = true;
  
  public function postSetupForm() {
    
    $l = $this->bootstrap->getLocalization();
    $this->controller->toSmarty['title']     = $l('users', 'invite_title');
    $this->controller->toSmarty['helpclass'] = 'small right';
    
  }
  
  public function onComplete() {
    
    $values = $this->form->getElementValues( 0 );
    $l      = $this->bootstrap->getLocalization();
    $this->addInvitation( $values );
    $this->controller->redirectWithMessage('users/admin', $l('users', 'user_invited') );
    
  }
  
  public function addInvitation( &$values ) {
    // TODO emailt userid-re ha lehet
    // TODO status: if user found -> existing, if new -> registed -> default: pending
    $invModel  = $this->bootstrap->getModel('users_invitations');
    $crypto    = $this->bootstrap->getEncryption();
    $l         = $this->bootstrap->getLocalization();
    $user      = $this->bootstrap->getSession('user');
    
    if ( is_array( $values['permissions'] ) )
      $values['permissions']  = implode('|', $values['permissions'] );
    
    $values['validationcode'] = $crypto->randomPassword( 10 );
    $values['userid']         = $user['id'];
    
    if ( isset( $values['departments'] ) and is_array( $values['departments'] ) )
      $values['departments']  = implode('|', $values['departments'] );
    
    if ( isset( $values['groups'] ) and is_array( $values['groups'] ) )
      $values['groups']       = implode('|', $values['groups'] );
    
    if ( !@$values['needtimestampdisabledafter'] )
      unset( $values['timestampdisabledafter'] );
    
    $invModel->insert( $values );
    
    $invModel->row['id'] = $crypto->asciiEncrypt( $invModel->row['id'] );
    $this->controller->toSmarty['values'] = $invModel->row;
    $this->controller->toSmarty['user']   = $user;
    
    $this->controller->sendOrganizationHTMLEmail(
      $values['email'],
      $l('users', 'invitationmailsubject'),
      $this->controller->fetchSmarty('Visitor/Users/Email/Invitation.tpl')
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
