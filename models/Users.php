<?php
namespace Model;

class Users extends \Springboard\Model {
  const USER_UNVALIDATED = -2; // disabled mezo lehetseges ertekei
  const USER_BANNED      = -1;
  const USER_VALIDATED   = 0;
  const USER_DISABLED    = 1;
  protected $registeredSessionKey;
  
  public function selectAndCheckUserValid( $organizationid, $email, $password, $isadmin = null ) {
    
    $crypto = $this->bootstrap->getEncryption();
    $where  = array(
      'email    = ' . $this->db->qstr( $email ),
      'password = ' . $this->db->qstr( $crypto->getHash( $password ) ),
      'disabled = ' . $this->db->qstr( self::USER_VALIDATED ),
    );
    
    $adminwhere = implode(" AND ", $where ) . ' AND isadmin = 1';
    
    if ( $organizationid !== null )
      $where[] = 'organizationid = ' . $this->db->qstr( $organizationid );
    
    if ( $isadmin )
      $where[] = 'isadmin = 1';
    
    $where = implode(" AND ", $where );
    $user  = $this->db->getRow("
      SELECT *
      FROM users
      WHERE
        ( $where ) OR
        ( $adminwhere )
      ORDER BY id
      LIMIT 1
    ");
    
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
    $this->registeredSessionKey = $sessionkey;
    return $user;
    
  }

  public function updateSessionInformation( $sessionkey = 'user' ) {
  
    $this->ensureObjectLoaded();
    
    if ( strlen( $this->registeredSessionKey ) )
      $sessionkey = $this->registeredSessionKey;

    if ( $this->row['issingleloginenforced'] ) {
      // update user session data when logging in
      if ( strlen( $sessionkey ) ) {
        $this->row['sessionid'] = $this->bootstrap->getSession( $this->registeredSessionKey )->getSessionID();
        $this->row['sessionlastupdated'] = date("Y-m-d H:i:s");
        $this->updateRow( $this->row );
      }
      else
        throw new \Exception('registeredSessionKey is missing in a Users instance');
    }
    
  }
  
  public function checkSingleLoginUsers() {

    $this->ensureObjectLoaded();

    return  
      !$this->row['issingleloginenforced']
      ||
      (
        $this->row['issingleloginenforced'] &&
        ( 
          // a felhasznalo be van lepve, megfelelo a sessionje es
          // sessiontimeouton belul van
          ( 
            $this->row['sessionid'] == 
            $this->bootstrap->getSession('user')->getSessionID() 
          ) &&
          strlen( $this->row['sessionlastupdated'] ) &&
          (
            time() - strtotime( $this->row['sessionlastupdated'] ) < 
            $this->bootstrap->config['sessiontimeout']
          )
        )
        ||
        (
          // ha a felhasznalo sessionje mar lejart, ekkor mindegy,
          // most mi a sessionID-je
          time() - strtotime( $this->row['sessionlastupdated'] ) >
          $this->bootstrap->config['sessiontimeout']
        )
      )
    ;

  }
  
  public function updateLastLogin( $diagnostics = null, $ipaddress = null ) {
    
    $this->ensureObjectLoaded();

    $sql = '';
    if ( $diagnostics )
      $sql = ', browser = ' . $this->db->qstr( $diagnostics );

    if ( $ipaddress )
      $sql .= ', lastloggedinipaddress = ' . $this->db->qstr( $ipaddress );

    if ( !$this->row['firstloggedin'] )
      $sql .= ', firstloggedin = ' . $this->db->qstr( date('Y-m-d H:i:s') );

    $this->db->query("
      UPDATE LOW_PRIORITY users 
      SET
        lastloggedin = NOW()
         $sql
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
  
  public function canUploadAvatar( $status = null ) {
    
    if ( $status === null ) {
      
      $this->ensureObjectLoaded();
      $status = $this->row['avatarstatus'];
      
    }
    
    if (
         in_array( $status, array( '', 'markedfordeletion', 'deleted', 'onstorage') ) or
         preg_match( '/^failed/', $status )
       )
      return true;
    
    return false;
    
  }
  
  protected function insertMultipleIDs( $ids, $table, $field ) {
    
    $this->ensureID();
    
    $values = array();
    foreach( $ids as $id )
      $values[] = "('" . intval( $id ) . "', '" . $this->id . "')";
    
    $this->db->execute("
      INSERT INTO $table ($field, userid)
      VALUES " . implode(', ', $values ) . "
    ");
    
  }
  
  public function clearDepartments() {
    
    $this->ensureID();
    
    $this->db->execute("
      DELETE FROM users_departments
      WHERE userid = '" . $this->id . "'
    ");
    
  }
  
  public function clearGroups() {
    
    $this->ensureID();
    
    $this->db->execute("
      DELETE FROM groups_members
      WHERE userid = '" . $this->id . "'
    ");
    
  }
  
  public function addDepartments( $departmentids ) {
    $this->insertMultipleIDs( $departmentids, 'users_departments', 'departmentid');
  }
  
  public function addGroups( $groupids ) {
    $this->insertMultipleIDs( $groupids, 'groups_members', 'groupid');
  }
  
  public function search( $email, $organizationid ) {
    
    $email = str_replace( ' ', '%', $email );
    $email = $this->db->qstr( '%' . $email . '%' );
    return $this->db->getArray("
      SELECT *
      FROM users
      WHERE
        organizationid = '$organizationid' AND
        isadmin        = '0' AND
        email LIKE $email
    ");
    
  }
  
  public function emailExists( $email, $organizationid ) {
    
    $email = $this->db->qstr( $email );
    return !!$this->db->getOne("
      SELECT COUNT(*)
      FROM users
      WHERE
        email          = $email AND
        organizationid = '$organizationid'
    ");
    
  }
  
}
