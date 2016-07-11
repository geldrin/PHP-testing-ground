<?php
namespace Model;

class Users extends \Springboard\Model {
  // disabled mezo lehetseges ertekei
  const USER_UNVALIDATED = -2; // letrehozva, az email kikuldve, validaciora varunk
  const USER_BANNED      = -1; // inaktiv
  const USER_VALIDATED   = 0; // minden oke
  const USER_DISABLED    = 1; // letiltva adminisztracios oldalrol
  const USER_DIRECTORYDISABLED = 2; // letiltva LDAP-bol (nem tagja a csoportnak), automatikusan viszacsinaljuk
  protected $registeredSessionKey;

  protected function checkUser( &$user, $organizationid ) {

    if ( $user['organizationid'] != $organizationid and !$user['isadmin'] )
      return 'organizationinvalid';

    if (
         $user['timestampdisabledafter'] and
         strtotime( $user['timestampdisabledafter'] ) < time()
       )
      return 'expired';

    return true;

  }

  // lehetseges viszateresi ertekek:
  // true -> belepes sikeres
  // barmi mas mint true -> belepes sikertelen, de konkretabban:
  // Users::checkUser viszateresi ertekei (organizationinvalid, expired)
  public function selectAndCheckUserValid( $organizationid, $email, $password, $isadmin = null ) {

    $crypto = $this->bootstrap->getEncryption();
    $where  = array(
      'email    = ' . $this->db->qstr( $email ),
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

    if ( !empty( $user ) and $crypto->passwordValid( $password, $user['password'] ) ) {

      $valid = $this->checkUser( $user, $organizationid );
      if ( $valid !== true )
        return $valid;

      if ( $user['isadmin'] );
        $user['organizationid'] = $organizationid;

      $this->id  = $user['id'];
      $this->row = $user;

      if ( $crypto->shouldRehashPassword( $user['password'] ) ) {

        $this->updateRow( array(
            'password' => $crypto->getPasswordHash( $password ),
          )
        );

      }

      return true;

    } else
      return false;

  }

  public function selectAndCheckAPIUserValid( $organizationid, $email, $password, $currentip ) {

    $uservalid = $this->selectAndCheckUserValid( $organizationid, $email, $password );

    if ( $uservalid !== true )
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

  public function updateLastLogin( $diagnostics = null, $ipaddresses = array() ) {

    $this->ensureObjectLoaded();

    $sql = '';
    if ( $diagnostics )
      $sql = ', browser = ' . $this->db->qstr( $diagnostics );

    if ( $ipaddresses ) {
      $ipaddress = '';
      foreach( $ipaddresses as $key => $value )
        $ipaddress .= ' ' . $key . ': ' . $value;

      $sql .= ', lastloggedinipaddress = ' . $this->db->qstr( $ipaddress );
    }

    if ( array_key_exists('firstloggedin',  $this->row ) and !$this->row['firstloggedin'] )
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

  public function checkEmailAndDisabledStatus( $email, $disabled, $organizationid ) {

    $this->addFilter('email', $email, false, false);
    $this->addFilter('disabled', $disabled );
    $this->addFilter('organizationid', $organizationid );

    $user = $this->getRow();

    if ( empty( $user ) )
      return false;

    $this->id  = $user['id'];
    $this->row = $user;

    return true;

  }

  public function checkEmailAndUpdateValidationCode( $email, $code, $organizationid ) {

    if ( !$this->checkEmailAndDisabledStatus( $email, self::USER_VALIDATED, $organizationid ) )
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
    if ( empty( $ids ) )
      return;

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

  public function clearLocalGroups( $localgroups ) {
    $this->ensureID();
    if ( empty( $localgroups ) )
      return;

    $this->db->execute("
      DELETE FROM groups_members
      WHERE
        userid = '" . $this->id . "' AND
        groupid IN('" . implode("', '", $localgroups ) . "')
    ");
  }

  public function clearFromGroups( $groupids ) {

    $this->ensureID();

    $this->db->execute("
      DELETE FROM groups_members
      WHERE
        userid = '" . $this->id . "' AND
        groupid IN('" . implode("', '", $groupids ) . "')
    ");

  }

  public function addDepartments( $departmentids ) {
    $this->insertMultipleIDs( $departmentids, 'users_departments', 'departmentid');
  }

  public function addGroups( $groupids ) {
    $this->insertMultipleIDs( $groupids, 'groups_members', 'groupid');
  }

  public function getAssocDirectoryGroupIDs( $organizationid ) {
    $this->ensureID();

    return $this->db->getAssoc("
      SELECT DISTINCT
        g.id AS idx,
        '1' AS value
      FROM
        groups_members AS gm,
        groups AS g
      WHERE
        gm.userid        = '" . $this->id . "' AND
        gm.groupid       = g.id AND
        g.source         = 'directory' AND
        g.organizationid = '$organizationid'
    ");
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

  public function searchStatistics( $user, $term, $organization, $start, $limit ) {
    return $this->getSearchArray( $term, $organization, $start, $limit, 'relevancy DESC');
  }

  public function getSearchWhere( $searchterm, $organization, $prefix = '' ) {
    $searchterm = str_replace( ' ', '%', $searchterm );
    $searchterm = $this->db->qstr( '%' . $searchterm . '%' );
    if ( $organization['displaynametype'] != 'shownickname' )
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
        {$prefix}email LIKE $searchterm OR
        {$prefix}externalid LIKE $searchterm OR
        $where
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
          IF( externalid = $term, 3, 0 ) +
          " . ( $organization['displaynametype'] != 'shownickname'
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
        r.contentmasterlength,
        r.isintrooutro,
        r.approvalstatus,
        " . \Model\Recordings::getWatchedPositionPercentSQL() . ",
        rvp.position,
        rvp.timestamp
      FROM
        recordings AS r,
        recording_view_progress AS rvp
      WHERE
        rvp.userid       = '" . $this->id . "' AND
        rvp.recordingid  = r.id AND
        r.organizationid = '$organizationid' AND
        r.status NOT IN('markedfordeletion', 'deleted')
    ");

    if ( empty( $recordings ) )
      return array();

    $seenrecordings = array();
    $recordids = array();
    foreach( $recordings as $key => $recording ) {
      $recordids[] = $recording['id'];

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
          LOWER(email) AS arraykey,
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
    foreach( explode('|', $invitation['permissions'] ) as $permission ) {
      if ( $permission )
        $values[ $permission ] = 1;
    }

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

    if ( !empty( $values ) ) {
      unset( $this->row['id'] );
      $this->updateRow( $values );
    }

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

      $hashctx = hash_init('md5');
      hash_update( $hashctx, $values['subject'] );
      hash_update( $hashctx, $values['title'] );
      hash_update( $hashctx, $values['prefix'] );
      hash_update( $hashctx, $values['postfix'] );
      $hash = hash_final( $hashctx );

      $hashctx = hash_init('md5');
      hash_update( $hashctx, $template['subject'] );
      hash_update( $hashctx, $template['title'] );
      hash_update( $hashctx, $template['prefix'] );
      hash_update( $hashctx, $template['postfix'] );
      $existinghash = hash_final( $hashctx );
      unset( $hashctx );

      if ( $hash !== $existinghash )
        $needinsert = true;

    } elseif (
               strlen( trim( $values['subject'] ) ) or
               strlen( trim( $values['title'] ) ) or
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

  public function getAccreditedRecordings( $organizationid, $existingids = array() ) {
    $this->ensureObjectLoaded();
    // azok a recording ahol .isseekbardisabled = 1
    $ret        = array();
    $recordings = $this->db->getArray("
      SELECT DISTINCT
        " . \Model\Recordings::getRecordingSelect('r.') . ",
        " . \Model\Recordings::getWatchedPositionPercentSQL() . ",
        IFNULL(rvp.position, 0) AS lastposition
      FROM
        recordings AS r
        LEFT JOIN recording_view_progress AS rvp ON(
          r.id       = rvp.recordingid AND
          rvp.userid = '" . $this->id . "'
        )
      WHERE
        r.isintrooutro      = '0' AND
        r.approvalstatus    = 'approved' AND
        r.status            = 'onstorage' AND -- TODO live
        r.organizationid    = '$organizationid' AND
        r.status            = 'onstorage' AND
        r.isseekbardisabled = '1'
      ORDER BY rvp.timestamp DESC
    ");

    if ( !empty( $existingids ) )
      foreach( $recordings as $key => $recording ) {
        if ( in_array( $recording['id'], $existingids ) )
          unset( $recordings[ $key ] );
      }

    if ( $this->row['isadmin'] or $this->row['iseditor'] or $this->row['isclientadmin'] )
      return $recordings;

    $ids = array();
    foreach( $recordings as $key => $recording ) {
      if ( $recording['accesstype'] == 'departmentsorgroups' )
        $ids[] = $recording['id'];
      else { // ha nem departmentsorgroups akkor public/registered ami automatan engedett
        $ret[] = $recording;
        unset( $recordings[ $key ] );
      }
    }

    if ( empty( $ids ) )
      return $ret;

    $userid      = $this->id;
    $departments = $this->db->getAssoc("
      SELECT a.recordingid, COUNT(*)
      FROM
        access AS a,
        users_departments AS ud
      WHERE
        a.recordingid IN('" . implode("', '", $ids ) . "') AND
        ud.departmentid = a.departmentid AND
        ud.userid       = $userid
      GROUP BY a.recordingid
    ");
    $groups      = $this->db->getAssoc("
      SELECT a.recordingid, COUNT(*)
      FROM
        access AS a,
        groups_members AS gm
      WHERE
        a.recordingid   IN('" . implode("', '", $ids ) . "') AND
        gm.groupid    = a.groupid AND
        gm.userid     = $userid
      GROUP BY a.recordingid
    ");

    foreach( $recordings as $recording ) {
      $id = $recording['id'];
      if ( isset( $departments[ $id ] ) or isset( $groups[ $id ] ) )
        $ret[] = $recording;
    }

    return $ret;
  }

  public function getCourses( $organization ) {

    $this->ensureID();

    $coursetypeid = $this->bootstrap->getModel('channels')->cachedGetCourseTypeID(
      $organization['id']
    );

    $departmentids = $this->db->getCol("
      SELECT departmentid
      FROM users_departments
      WHERE userid = '" . $this->id . "'
    ");
    $groupids      = $this->db->getCol("
      SELECT groupid
      FROM groups_members
      WHERE userid = '" . $this->id . "'
    ");

    $where = array();
    if ( !empty( $departmentids ) )
      $where[] = "
        (
          c.accesstype = 'departmentsorgroups' AND
          a.departmentid IN('" . implode("', '", $departmentids ) . "')
        )
      ";

    if ( !empty( $groupids ) )
      $where[] = "
        (
          c.accesstype = 'departmentsorgroups' AND
          a.groupid IN('" . implode("', '", $groupids ) . "')
        )
      ";

    if ( !empty( $where ) )
      $where = ' OR ' . implode(' OR ', $where );
    else
      $where = '';

    $channels = $this->db->getArray("
      SELECT DISTINCT c.*
      FROM channels AS c
      LEFT JOIN users_invitations AS ui ON (
        ui.registereduserid = '" . $this->id . "' AND
        ui.channelid        = c.id AND
        ui.organizationid   = '" . $organization['id'] . "' AND
        ui.status           <> 'deleted'
      )
      LEFT JOIN access AS a ON (
        a.channelid = c.id AND
        (
          a.departmentid IS NOT NULL OR
          a.groupid IS NOT NULL
        )
      )
      WHERE
        c.channeltypeid     = '$coursetypeid' AND
        c.organizationid    = '" . $organization['id'] . "' AND
        (
          ui.id IS NOT NULL $where
        )
      ORDER BY c.title
    ");

    $channelids     = array();
    $channelidtokey = array();
    foreach( $channels as $key => $channel ) {
      $channelids[] = $channel['id'];
      $channelidtokey[ $channel['id'] ] = $key;
      $channels[ $key ]['recordings'] = array();
      $channels[ $key ]['recordingtowatch'] = null;
    }

    $recordingsModel = $this->bootstrap->getModel('recordings');
    $recordings      = $recordingsModel->getUserChannelRecordingsWithProgress(
      $channelids,
      $this->row,
      $organization,
      false
    );

    $recordings      = $recordingsModel->addPresentersToArray(
      $recordings,
      true,
      $organization['id']
    );

    foreach( $recordings as $recording ) {

      $key = $channelidtokey[ $recording['channelid'] ];
      $channels[ $key ]['recordings'][] = $recording;

      // first recording that is not watched enough
      if (
           !$channels[ $key ]['recordingtowatch'] and
           $recording['positionpercent'] < $organization['elearningcoursecriteria']
         )
        $channels[ $key ]['recordingtowatch'] = $recording;

    }

    usort( $channels, array( $this, 'compareUserChannels') );
    return $channels;

  }

  private function compareUserChannels( $a, $b ) {

    if (
         $a['recordingtowatch'] !== null and
         $b['recordingtowatch'] !== null )
      return 0;
    elseif ( $a['recordingtowatch'] !== null )
      return -1;
    else
      return 1;

  }

  public function getUsersWithPermission( $permission, $filteruserid, $organizationid ) {
    return $this->db->getArray("
      SELECT *
      FROM users
      WHERE
        is{$permission} = '1' AND
        organizationid  = '$organizationid' AND
        id             <> '$filteruserid' AND
        disabled        = '0'
    ");
  }

  protected function validateAutoLoginCookie() {

    // a msghash 64char, a hash 32char, a ketto separator char (|), plusz az id
    if (
         !isset( $_COOKIE['autologin'] ) or
         !preg_match('/^[a-zA-Z0-9|]{98,}$/', $_COOKIE['autologin'] )
       )
      return array();

    $values = explode('|', $_COOKIE['autologin'], 4 );
    if ( count( $values ) != 3 )
      return array();

    // ellenorizzuk hogy ezt a cookiet tenylegesen mi allitottuk be
    // ha nem ellenoriznenk akkor aranylag egyszeruen lehetne DOS-olni minket
    // csupan azzal hogy mindig lekerjuk az id-nek megfelelo usert az adatbazisbol
    $pos      = strpos( $_COOKIE['autologin'], '|' );
    $msg      = substr( $_COOKIE['autologin'], $pos + 1 );
    $crypt    = $this->bootstrap->getEncryption();
    $hashinfo = array(
      'expected' => hash_hmac( 'sha256', $msg, $this->bootstrap->config['hashseed'] ),
      'actual'   => $values[0]
    );

    if ( !$crypt->hashEqual( $hashinfo ) or !$values[1] )
      return array();

    $id = intval( $crypt->asciiDecrypt( $values[1] ) );
    if ( !$id )
      return array();

    $this->clearFilter();
    $this->addFilter('id', $id );
    $row = $this->getRow();

    // a hash valtozzon ha a user passwordot valt, elfelejtette a passwordjet,
    // vagy kitiltjak
    if (
         empty( $row ) or
         md5( $row['password'] . $row['validationcode'] . $row['disabled'] ) != $values[2]
       )
      return array();

    return $row;

  }

  public function loginFromCookie( $organizationid, $ipaddresses ) {

    $row = $this->validateAutoLoginCookie();
    if ( !$row )
      return false;

    if ( $row['isadmin'] )
      $row['organizationid'] = $organizationid;

    $valid     = $this->checkUser( $row, $organizationid );
    if ( $valid !== true )
      return false;

    $this->id  = $row['id'];
    $this->row = $row;

    if ( !$this->checkSingleLoginUsers() )
      return false;

    $this->registerForSession();
    $this->updateSessionInformation();

    $this->updateLastlogin(
      "(cookie auto-login)\n" .
      \Springboard\Debug::getRequestInformation( 0, false ),
      $ipaddresses
    );

    return true;

  }

  public function setAutoLoginCookie( $ssl = false ) {

    $this->ensureObjectLoaded();
    $crypt = $this->bootstrap->getEncryption();
    $value =
      $crypt->asciiEncrypt( $this->id ) . "|" .
      md5( $this->row['password'] . $this->row['validationcode'] . $this->row['disabled'] )
    ;

    $msghash = hash_hmac( 'sha256', $value, $this->bootstrap->config['hashseed'] );
    $value   = $msghash . '|' . $value;

    // httponly cookie
    setcookie('autologin', $value, strtotime('+3 months'), '/', null, $ssl, true );

  }

  public function unsetAutoLoginCookie( $ssl = false ) {
    // expiry in the past
    setcookie('autologin', '', 1, '/', null, $ssl, true );
  }

  public function loginFromExternalID( $externalid, $source, $organizationid, $ipaddresses ) {

    $where  = array(
      'externalid = ' . $this->db->qstr( $externalid ),
      'source     = ' . $this->db->qstr( $source ),
    );

    if ( $organizationid !== null )
      $where[] = 'organizationid = ' . $this->db->qstr( $organizationid );

    $where = implode(" AND ", $where );
    $row   = $this->db->getRow("
      SELECT *
      FROM users
      WHERE $where
      ORDER BY id
      LIMIT 1
    ");

    if ( !$row )
      return null; // meg akarjuk kulonboztetni hogy nem letezik

    if ( $row['disabled'] != self::USER_VALIDATED )
      return false;

    if ( $row['isadmin'] )
      $row['organizationid'] = $organizationid;

    $valid = $this->checkUser( $row, $organizationid );
    if ( $valid !== true )
      return false;

    $this->id  = $row['id'];
    $this->row = $row;

    if ( !$this->checkSingleLoginUsers() )
      return false;

    $this->registerForSession();
    $this->updateSessionInformation();

    $this->updateLastlogin(
      "($source auto-login)\n" .
      \Springboard\Debug::getRequestInformation( 0, false ),
      $ipaddresses
    );

    return true;

  }

  public function insertExternal( $data, $organization ) {
    $defaults = array(
      'externalid'            => $data['externalid'],
      'source'                => $data['source'],
      'email'                 => '',
      'nickname'              => '',
      'namefirst'             => '',
      'namelast'              => '',
      'browser'               => '',
      'validationcode'        => '',
      'nameformat'            => 'straight',
      'organizationid'        => $organization['id'],
      'timestamp'             => date('Y-m-d H:i:s'),
      'lastloggedin'          => date('Y-m-d H:i:s'),
      'language'              => \Springboard\Language::get(),
      'newsletter'            => 0,
      'disabled'              => self::USER_VALIDATED,
      'issingleloginenforced' => 0,
    );

    return $this->insert( array_merge( $defaults, $data ) );
  }

  public function isSubscribedToChannel( $channelid ) {
    $this->ensureID();
    $userid    = $this->db->qstr( $this->id );
    $channelid = $this->db->qstr( $channelid );

    return (bool)$this->db->getOne("
      SELECT COUNT(*)
      FROM subscriptions
      WHERE
        channelid = $channelid AND
        userid    = $userid
    ");
  }

  public function toggleSubscription( $channelid, $state ) {
    $this->ensureID();
    $userid    = $this->db->qstr( $this->id );
    $channelid = $this->db->qstr( $channelid );

    switch( $state ) {
      case 'add':
        $timestamp = $this->db->qstr( date('Y-m-d H:i:s') );
        $this->db->query("
          INSERT INTO subscriptions
          (channelid, userid, timestamp) VALUES
          ($channelid, $userid, $timestamp)
        ");
        break;
      case 'del':
        $this->db->query("
          DELETE FROM subscriptions
          WHERE
            channelid = $channelid AND
            userid    = $userid
          LIMIT 1
        ");
        break;
      default:
        throw new \Exception("Unknown state to set for the subscription");
        break;
    }
  }

  public function getGroups( $organizationid ) {
    $this->ensureID();

    return $this->db->getArray("
      SELECT
        g.id,
        g.name,
        g.source,
        gm.id AS memberid
      FROM
        groups AS g LEFT JOIN groups_members AS gm ON(
          gm.userid  = '" . $this->id . "' AND
          gm.groupid = g.id
        )
      WHERE g.organizationid = '$organizationid'
      GROUP BY g.id
      ORDER BY g.name DESC
    ");
  }

  public function getUsersByIDs( $ids, $organizationid, $select = '*' ) {
    $ret = array();

    while( !empty( $ids ) ) {
      $chunk = array_splice( $ids, 0, 50 );
      $users = $this->db->getArray("
        SELECT $select
        FROM users
        WHERE
          id IN('" . implode("', '", $chunk ) . "') AND
          organizationid = '$organizationid'
        LIMIT 50
      ");

      $ret = array_merge( $ret, $users );
    }

    return $ret;
  }

}
