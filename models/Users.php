<?php
namespace Model;

class Users extends \Springboard\Model {
  const USER_UNVALIDATED = -2; // disabled mezo lehetseges ertekei
  const USER_BANNED      = -1;
  const USER_VALIDATED   = 0;
  const USER_DISABLED    = 1;
  
  public function selectAndCheckUserValid( $organizationid, $email, $password, $isadmin = null ) {
    
    $crypto = $this->bootstrap->getEncryption();
    $this->clearFilter();
    
    if ( $organizationid !== null )
      $this->addFilter('organizationid', $organizationid );
    
    if ( $isadmin )
      $this->addFilter('isadmin', 1 );
    
    $this->addFilter('email',    $email, false );
    $this->addFilter('password', $crypto->getHash( $password ), false );
    $this->addFilter('disabled', self::USER_VALIDATED );
    
    $user = $this->getRow();
    if ( empty( $user ) ) {
      return false;
    } else {
      
      $this->id  = $user['id'];
      $this->row = $user;
      return true;
      
    }
    
  }
  
  public function selectAndCheckAPIUserValid( $organizationid, $email, $password, $currentip ) {
    
    $uservalid = $this->selectAndCheckUserValid( $organizationid, $email, $password );
    
    if ( !$uservalid )
      return false;
    
    if ( !$this->row['isapienabled'] )
      return false;
    
    if ( $this->row['apiaddresses'] and $currentip ) {
      
      $found       = false;
      $addresses = explode(',', $this->row['apiaddresses'] );
      
      foreach ( $addresses as $ip ) {
        
        $ip = trim( $ip );
        if ( !$ip )
          continue;
        
        // ha csillaggal vegzodik akkor range match
        if ( substr( $ip, -1, 1 ) == '*' and $ip != '*' ) {
          
          if ( strpos( $currentip, substr( $ip, 0, -1 ) ) === 0 ) {
            
            $found = true;
            break;
            
          }
          
        } elseif ( $ip == $currentip or $ip == '*' ) {
          
          $found = true;
          break;
          
        }
        
      }
      
      return $found;
      
    }
    
    return true;
    
  }
  
  public function registerForSession( $sessionkey = 'user' ) {
    
    $user = $this->bootstrap->getSession( $sessionkey );
    $user->setArray( $this->row );
    return $user;
    
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
  
  public function checkEmailAndDisabledStatus( $email, $disabled ) {
    
    $this->addFilter('email', $email, false, false);
    $this->addFilter('disabled', $disabled );
    
    $user = $this->getRow();
    
    if ( empty( $user ) )
      return false;
    
    $this->id  = $user['id'];
    $this->row = $user;
    
    return true;
    
  }
  
  public function checkEmailAndUpdateValidationCode( $email, $code ) {
    
    if ( !$this->checkEmailAndDisabledStatus( $email, self::USER_VALIDATED ) )
      return false;
    
    $this->updateRow( array(
        'validationcode' => $code
      )
    );
    
    return true;
    
  }
  
  public function checkIDAndValidationCode( $id, $code ) {
    
    $crypt = $this->bootstrap->getEncryption();
    $id    = intval( $crypt->asciiDecrypt( $id ) );
    
    if ( $id <= 0 or !$code )
      return false;
    
    $this->select( $id );
    
    if ( $this->row and $this->row['validationcode'] == $code )
      return true;
    
    return false;
    
  }
  
  public function getGroupCount() {
    
    $this->ensureID();
    return $this->db->getOne("
      SELECT COUNT(*)
      FROM groups_members
      WHERE userid = '" . $this->id . "'
    ");
    
  }
  
}
