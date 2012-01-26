<?php
namespace Model;

class Users extends \Springboard\Model {
  
  public function selectAndCheckUserValid( $email, $password ) {
    
    $crypto = $this->bootstrap->getCrypto();
    
    $this->addFilter('email',    $email, false );
    $this->addFilter('password', $crypto->getHash( $password ), false );
    $this->addFilter('disabled', 0 );
    
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
  
  public function updateLastLogin( $diagnostics ) {
    
    $this->db->query("
      UPDATE LOW_PRIORITY users 
      SET
        lastloggedin = NOW(),
        browser   = " . $this->db->qstr( $diagnostics ) . "
      WHERE 
        id = '" . $this->id . "'"
    );
    
  }
  
}
