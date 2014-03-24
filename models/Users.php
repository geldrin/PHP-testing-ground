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
  
  public function getSearchWhere( $searchterm, $organization, $prefix = '' ) {
    $searchterm = str_replace( ' ', '%', $searchterm );
    $searchterm = $this->db->qstr( '%' . $searchterm . '%' );
    if ( $organization['fullnames'] )
      $where = "
        {$prefix}namefirst LIKE $searchterm OR
        {$prefix}namelast  LIKE $searchterm OR
        IF( {$prefix}nameformat = 'straight',
          CONCAT_WS(' ', {$prefix}nameprefix, {$prefix}namelast, {$prefix}namefirst ),
          CONCAT_WS(' ', {$prefix}nameprefix, {$prefix}namefirst, {$prefix}namelast )
        ) LIKE $searchterm
      ";
    else
      $where = "{$prefix}nickname LIKE $searchterm";

    return "
      {$prefix}organizationid = '" . $organization['id'] . "' AND
      {$prefix}isadmin        = '0' AND
      (
        {$prefix}email LIKE $searchterm OR $where
      )
    ";
  }

  public function getSearchCount( $searchterm, $organization ) {
    return $this->db->getOne("
      SELECT COUNT(*)
      FROM users
      WHERE " . $this->getSearchWhere( $searchterm, $organization )
    );
  }

  public function getSearchArray( $originalterm, $organization, $start, $limit, $order ) {
    $term        = $this->db->qstr( $originalterm );
    $searchterm  = str_replace( ' ', '%', $originalterm );
    $searchterm  = $this->db->qstr( '%' . $searchterm . '%' );

    return $this->db->getArray("
      SELECT
        *,
        (
          1 +
          IF( email     = $term, 3, 0 ) +
          " . ( $organization['fullnames']
            ? "
              IF( namefirst = $term, 2, 0 ) +
              IF( namelast  = $term, 2, 0 ) +
              IF( email LIKE $searchterm, 1, 0 ) +
              IF(
                IF( nameformat = 'straight',
                  CONCAT_WS(' ', nameprefix, namelast, namefirst ),
                  CONCAT_WS(' ', nameprefix, namefirst, namelast )
                ) LIKE $searchterm,
                1,
                0
              )"
            : "IF( nickname = $term, 3, 0 )"
          ) . "
        ) AS relevancy
      FROM users
      WHERE " . $this->getSearchWhere( $originalterm, $organization ) . "
      ORDER BY $order
      LIMIT $start, $limit
    ");
  }

  public function getRecordingsProgressWithChannels( $organizationid ) {

    $this->ensureID();
    $recordings = $this->db->getAssoc("
      SELECT
        r.id AS indexkey,
        r.id,
        r.userid,
        r.organizationid,
        r.title,
        r.subtitle,
        r.description,
        r.indexphotofilename,
        r.recordedtimestamp,
        r.numberofviews,
        r.rating,
        r.status,
        r.masterlength,
        r.isintrooutro,
        r.ispublished,
        rwp.position,
        rwp.timestamp
      FROM
        recordings AS r,
        recording_view_progress AS rwp
      WHERE
        rwp.userid       = '" . $this->id . "' AND
        rwp.recordingid  = r.id AND
        r.organizationid = '$organizationid' AND
        r.status NOT IN('markedfordeletion', 'deleted')
    ");

    if ( empty( $recordings ) )
      return array();

    $seenrecordings = array();
    $recordids = array();
    foreach( $recordings as $key => $recording ) {
      $recordids[] = $recording['id'];
      $recordings[ $key ]['positionpercent'] = round(
        ( $recording['position'] / $recording['masterlength'] ) * 100
      );
      
      if ( $recordings[ $key ]['positionpercent'] > 100 )
        $recordings[ $key ]['positionpercent'] = 100;

      $recordings[ $key ]['viewedminutes'] = round( $recording['position'] / 60 );
    }

    $recordids = implode("', '", $recordids );
    $chanrecordings = $this->db->getArray("
      SELECT
        cr.channelid,
        cr.recordingid,
        cr.weight
      FROM channels_recordings AS cr
      WHERE recordingid IN('$recordids')
      ORDER BY weight
    ");
    $channelstorecordings = array();
    foreach( $chanrecordings as $row ) {
      if ( !isset( $channelstorecordings[ $row['channelid'] ] ) )
        $channelstorecordings[ $row['channelid'] ] = array();

      $channelstorecordings[ $row['channelid'] ][] = $row['recordingid'];
    }

    $channels = $this->db->getArray("
      SELECT DISTINCT
        c.id,
        c.title,
        c.subtitle,
        c.indexphotofilename,
        c.starttimestamp,
        c.endtimestamp
      FROM
        channels AS c,
        channels_recordings AS cr
      WHERE
        cr.recordingid IN('$recordids') AND
        cr.channelid = c.id
      ORDER BY c.title
    ");

    foreach( $channels as $key => $channel ) {
      $channels[ $key ]['recordings'] = array();

      // weight szerint van rendezve ez a tomb, ezert weight szerint lesznek
      // besorolva ala a recordingok is
      foreach( $channelstorecordings[ $channel['id'] ] as $recordingid ) {
        $channels[ $key ]['recordings'][] = $recordings[ $recordingid ];
        $seenrecordings[ $recordingid ] = true;
      }
    }

    $channels['channelcount'] = count( $channels );
    $channels['recordings']   = array();
    foreach( $recordings as $recording ) {
      if ( !isset( $seenrecordings[ $recording['id'] ] ) )
        $channels['recordings'][] = $recording;
    }

    return $channels;

  }

  public function getInvitations( $organizationid ) {
    $this->ensureID();
    return $this->db->getArray("
      SELECT *
      FROM users_invitations
      WHERE
        status           <> 'deleted' AND
        registereduserid  = '" . $this->id . "' AND
        organizationid    = '$organizationid'
      ORDER BY id DESC
    ");
  }

  public function invitationRegistered( $invitationid ) {
    
    $this->db->execute("
      UPDATE users_invitations
      SET
        registereduserid = '" . $this->id . "',
        status           = 'registered'
      WHERE id = '$invitationid'
    ");
    
  }

  public function searchEmails( &$emails, $organizationid ) {
    
    $ret = array();
    while( !empty( $emails ) ) {

      $chunk = array_splice( $emails, 0, 50 );
      $users = $this->db->getAssoc("
        SELECT
          email AS arraykey,
          id,
          email,
          nameprefix,
          namefirst,
          namelast,
          nameformat,
          nickname
        FROM users
        WHERE
          organizationid = '$organizationid' AND
          email IN('" . implode("', '", $chunk ) . "')
        LIMIT 50
      ");
      $ret = array_merge( $ret, $users );

    }

    return $ret;

  }

  public function applyInvitationPermissions( $invitation ) {

    $this->ensureID();

    $values = array();
    foreach( explode('|', $invitation['permissions'] ) as $permission )
      $values[ $permission ] = 1;

    $departments = array();
    foreach( explode('|', $invitation['departments'] ) as $id ) {

      $id = intval( $id );
      if ( $id )
        $departments[] = $id;
      
    }

    $groups      = array();
    foreach( explode('|', $invitation['groups'] ) as $id ) {

      $id = intval( $id );
      if ( $id )
        $groups[] = $id;

    }
    
    if (
         isset( $invitation['timestampdisabledafter'] ) and
         $invitation['timestampdisabledafter']
       )
      $values['timestampdisabledafter'] = $invitation['timestampdisabledafter'];

    if ( !empty( $values ) )
      $this->updateRow( $values );

    if ( !empty( $departments ) )
      $this->addDepartments( $departments );

    if ( !empty( $groups ) )
      $this->addGroups( $groups );

  }

  public function searchForValidInvitation( $organizationid ) {
    $this->ensureObjectLoaded();
    $email = $this->db->qstr( $this->row['email'] );
    return $this->db->getRow("
      SELECT *
      FROM users_invitations
      WHERE
        email          = $email AND
        organizationid = '$organizationid' AND
        status         = 'invited'
      LIMIT 1
    ");
  }

  public function getInviteTemplates( $organizationid ) {
    $templates = $this->db->getAssoc("
      SELECT
        id AS arraykey,
        id,
        prefix,
        postfix,
        timestamp
      FROM invite_templates
      WHERE organizationid = '$organizationid'
      ORDER BY id DESC
    ");

    return $templates;
  }

  public function maybeInsertTemplate( $values ) {

    $needinsert = false;
    if ( intval( $values['id'] ) ) {

      $template = $this->getTemplate(
        intval( $values['id'] ),
        $values['organizationid']
      );

      if ( empty( $template ) )
        throw new \Exception("Template with id: " . $values['id'] . ' not found!');

      $hash         = md5( $values['prefix'] . $values['postfix'] );
      $existinghash = md5( $template['prefix'] . $template['postfix'] );

      if ( $hash != $existinghash )
        $needinsert = true;

    } elseif (
               strlen( trim( $values['prefix'] ) ) or
               strlen( trim( $values['postfix'] ) )
             )
      $needinsert = true;
    else // se template nem volt valasztva, se nem volt kitoltve semmi => nem akar a user templatet
      return array();

    if ( $needinsert ) {
      unset( $values['id'] );
      $templateModel = $this->bootstrap->getModel('invite_templates');
      $templateModel->insert( $values );
      $values['id'] = $templateModel->id;
    }

    return $values;

  }

  public function getTemplate( $templateid, $organizationid ) {
    return $this->db->getRow("
      SELECT *
      FROM invite_templates
      WHERE
        id             = '$templateid' AND
        organizationid = '$organizationid'
      LIMIT 1
    ");
  }

}
