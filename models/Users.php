<?php
namespace Model;

class Users extends \Springboard\Model {
  
  public function selectAndCheckUserValid( $email, $password ) {
    
    $this->addFilter('email', $email, false );
    $this->addFilter('password', $this->application->getHash( $password ), false );
    
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
  
}
