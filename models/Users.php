<?php
namespace Model;

class Users extends \Springboard\Model {
  const USER_UNVALIDATED = -2; // disabled mezo lehetseges ertekei
  const USER_BANNED      = -1;
  const USER_VALIDATED   = 0;
  const USER_DISABLED    = 1;
  
  public function selectAndCheckUserValid( $organizationid, $email, $password ) {
    
    $crypto = $this->bootstrap->getEncryption();
    $this->clearFilter();
    
    $this->addFilter('organizationid', $organizationid );
    $this->addFilter('email',          $email, false );
    $this->addFilter('password',       $crypto->getHash( $password ), false );
    $this->addFilter('disabled',       0 );
    
    $user = $this->getRow();
    if ( empty( $user ) ) {
      return false;
    } else {
      
      $this->id  = $user['id'];
      $this->row = $user;
      return true;
      
    }
    
  }
  
  public function registerForSession() {
    
    $user = $this->bootstrap->getUser();
    $user->setArray( $this->row );
    
  }
  
  public function updateLastLogin( $diagnostics = null ) {
    
    $sql = '';
    if ( $diagnostics )
      $sql = ', browser = ' . $this->db->qstr( $diagnostics );
    
    $this->db->query("
      UPDATE LOW_PRIORITY users 
      SET
        lastloggedin = NOW() $sql
      WHERE 
        id = '" . $this->id . "'"
    );
    
  }
  
  public function checkEmailAndUpdateValidationCode( $email, $code ) {
    
    $this->addFilter('email', $email, false, false);
    $this->addFilter('disabled', 0 );
    
    $user = $this->getRow();
    
    if ( empty( $user ) )
      return false;
    
    $this->id  = $user['id'];
    $this->row = $user;
    
    $this->updateRow( array(
        'validationcode' => $code
      )
    );
    
    return true;
    
  }
  
  public function checkIDAndValidationCode( $id, $code ) {
    
    $this->select( $id );
    
    if ( $this->row and $this->row['validationcode'] == $code )
      return true;
    
    return false;
    
  }
  
}
