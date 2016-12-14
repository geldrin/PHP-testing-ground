<?php
namespace Model;

class Groups extends \Springboard\Model {

  // --------------------------------------------------------------------------
  public function delete( $id, $magic_quotes_gpc = 0 ) {

    $this->db->query("
      DELETE FROM groups_members
      WHERE groupid = " . $this->db->qstr( $id )
    );
    $this->db->query("
      DELETE FROM access
      WHERE groupid = " . $this->db->qstr( $id )
    );
    return parent::delete( $id, $magic_quotes_gpc );

  }

  public function updateMembersFromExternalId( $externalid, $userid ) {
    $externalid = $this->db->qstr( $externalid );
    $this->db->query("
      UPDATE groups_members
      SET userid = '$userid'
      WHERE userexternalid = $externalid
    ");
  }

  public function getDirectoryGroupsForExternalId( $externalid, $directory ) {
    $externalid = $this->db->qstr( $externalid );
    $membersql  = "
      SELECT COUNT(*)
      FROM groups_members AS gm
      WHERE
        gm.userexternalid = $externalid AND
        gm.groupid        = '%s'
    ";

    if ( $directory['ldapgroupaccessid'] )
      $accesssql = sprintf( $membersql, $directory['ldapgroupaccessid'] );
    else
      $accesssql = "-- no ldapgroupaccessid specified, auto allowing
          1
      ";

    $sql = "
      SELECT
        (
          " . $accesssql . "
        ) AS hasaccess,
        (
          " . sprintf( $membersql, $directory['ldapgroupadminid'] ) . "
        ) AS isadmin
    ";
    $ret = $this->db->getRow( $sql );

    if ( $this->bootstrap->config['debugauth'] ) {
      $line  = "models/groups::getDirectoryGroupsForExternalId az externalid '$externalid' keressuk a directoryhoz tartozo csoportban, az sql:\n$sql\n\neredmenye:\n" . var_export( $ret, true );
      $line .= "\nSID: " . session_id();

      $d = \Springboard\Debug::getInstance();
      $d->log(
        false,
        'authdebug.txt',
        $line,
        false,
        true,
        true
      );
    }

    return $ret;
  }

  public function getUserCount() {

    $this->ensureID();
    // mivel lehetseges hogy a groups_members-be van rekord de nem tartozik hozza
    // user mert mondjuk non-lokalis (ActiveDirectory) userekbol all a csoport
    return $this->db->getOne("
      SELECT COUNT(*)
      FROM
        groups_members AS gm,
        users AS u
      WHERE
        gm.groupid = '" . $this->id . "' AND
        gm.userid  = u.id AND
        u.disabled = '0'
      GROUP BY gm.userid
    ");

  }

  public function getUserArray( $start, $limit, $orderby ) {

    $this->ensureID();
    return $this->db->getArray("
      SELECT
        u.id,
        u.externalid,
        u.email,
        u.nameformat,
        u.nickname,
        u.nameprefix,
        u.namefirst,
        u.namelast,
        u.disabled
      FROM
        users AS u,
        groups_members AS gm
      WHERE
        u.id       = gm.userid AND
        gm.groupid = '" . $this->id . "' AND
        u.disabled = '0'
      GROUP BY u.id
      ORDER BY $orderby
      LIMIT $start, $limit
    ");

  }

  public function deleteUser( $userid ) {

    $this->ensureID();
    $this->db->query("
      DELETE FROM groups_members
      WHERE
        groupid = '" . $this->id . "' AND
        userid  = " . $this->db->qstr( $userid ) . "
    ");

  }

  private function canSeeGroups( $user ) {
    return \Model\Userroles::userHasPrivilege(
      $user,
      'groups_visible',
      'or',
      'isadmin', 'isclientadmin', 'isuploader', 'ismoderateduploader', 'isliveadmin', 'iseditor'
    );
  }

  public function getGroupCount( $user, $organizationid ) {

    if ( $this->canSeeGroups( $user ) )
      $where = '';
    else
      $where = "userid         = '" . $user['id'] . "' AND";

    return $this->db->getOne("
      SELECT COUNT(*)
      FROM groups
      WHERE
        $where
        organizationid = '$organizationid'
      LIMIT 1
    ");

  }

  public function getGroupArray( $start, $limit, $orderby, $user, $organizationid ) {

    if ( $this->canSeeGroups( $user ) )
      $where = '';
    else
      $where = "g.userid         = '" . $user['id'] . "' AND";

    return $this->db->getArray("
      SELECT
        g.*,
        COUNT(DISTINCT gm.userid) AS usercount
      FROM groups AS g
      LEFT JOIN groups_members AS gm ON(
        gm.groupid = g.id
      )
      WHERE
        $where
        g.organizationid = '$organizationid'
      GROUP BY g.id
      ORDER BY $orderby
      LIMIT $start, $limit
    ");

  }

  protected function insertMultipleIDs( $ids, $table, $field ) {

    $this->ensureID();

    $values = array();
    foreach( $ids as $id )
      $values[] = "('" . intval( $id ) . "', '" . $this->id . "')";

    $this->db->execute("
      INSERT INTO $table ($field, groupid)
      VALUES " . implode(', ', $values ) . "
    ");

  }

  public function addUsers( $userids ) {
    $this->insertMultipleIDs( $userids, 'groups_members', 'userid');
  }

  public function isMember( $user ) {

    if ( !$user or !$user['id'] )
      return false;

    $this->ensureObjectLoaded();
    if (
         $this->row['userid'] == $user['id'] or
         \Model\Userroles::userHasPrivilege(
           $user,
           'general_ignoreAccessRestrictions',
           'or',
           'isclientadmin', 'iseditor', 'isadmin'
         )
       )
      return true;

    return (bool)$this->db->getOne("
      SELECT COUNT(*)
      FROM groups_members
      WHERE
        groupid = '" . $this->id . "' AND
        userid  = '" . $user['id'] . "'
      LIMIT 1
    ");

  }

  public function isValidUser( $userid, $organizationid ) {

    $userid = intval( $userid );
    $user   = array(
      'id'             => $userid,
      'organizationid' => $organizationid,
      'isadmin'        => false,
      'isclientadmin'  => false,
      'iseditor'       => false,
    );

    if ( $this->isMember( $user ) )
      return false;

    return (bool)$this->db->getOne("
      SELECT COUNT(*)
      FROM users
      WHERE
        organizationid = '$organizationid' AND
        id             = '$userid'
      LIMIT 1
    ");

  }

  public function getSearchCount( $searchterm, $organization, $userModel ) {
    $this->ensureID();
    return $this->db->getOne("
      SELECT COUNT(*)
      FROM
        users AS u,
        groups_members AS gm
      WHERE
        gm.userid  = u.id AND
        gm.groupid = '" . $this->id . "' AND
        u.disabled = '0' AND
        " . $userModel->getSearchWhere( $searchterm, $organization, 'u.' ) . "
    ");
  }

  public function getSearchArray( $originalterm, $organization, $userModel, $start, $limit, $order ) {

    $this->ensureID();
    $term        = $this->db->qstr( $originalterm );
    $searchterm  = str_replace( ' ', '%', $originalterm );
    $searchterm  = $this->db->qstr( '%' . $searchterm . '%' );

    return $this->db->getArray("
      SELECT
        u.id,
        u.email,
        u.nameformat,
        u.nickname,
        u.nameprefix,
        u.namefirst,
        u.namelast,
        u.disabled,
        (
          1 +
          IF( u.email     = $term, 3, 0 ) +
          " . ( $organization['displaynametype'] != 'shownickname'
            ? "
              IF( u.namefirst = $term, 2, 0 ) +
              IF( u.namelast  = $term, 2, 0 ) +
              IF( u.email LIKE $searchterm, 1, 0 ) +
              IF(
                IF( u.nameformat = 'straight',
                  CONCAT_WS(' ', u.nameprefix, u.namelast, u.namefirst ),
                  CONCAT_WS(' ', u.nameprefix, u.namefirst, u.namelast )
                ) LIKE $searchterm,
                1,
                0
              )"
            : "IF( u.nickname = $term, 3, 0 )"
          ) . "
        ) AS relevancy
      FROM
        users AS u,
        groups_members AS gm
      WHERE
        gm.userid  = u.id AND
        gm.groupid = '" . $this->id . "' AND
        u.disabled = '0' AND
        " . $userModel->getSearchWhere( $originalterm, $organization, 'u.' ) . "
      ORDER BY $order
      LIMIT $start, $limit
    ");
  }

  public function getDirectoryGroups( $organizationid ) {
    return $this->db->getArray("
      SELECT
        id,
        organizationdirectoryldapdn AS dn
      FROM groups
      WHERE
        source         = 'directory' AND
        organizationid = $organizationid
    ");
  }

  public function searchStatistics( $user, $term, $organizationid, $start, $limit ) {

    $searchterm = str_replace( ' ', '%', $term );
    $searchterm = $this->db->qstr( '%' . $searchterm . '%' );
    return $this->db->getArray("
      SELECT
        g.id,
        g.name,
        g.source,
        COUNT(DISTINCT gm.userid) AS usercount
      FROM groups AS g
      LEFT JOIN groups_members AS gm ON(
        gm.groupid = g.id
      )
      WHERE
        g.name LIKE $searchterm AND
        g.organizationid = '$organizationid'
      GROUP BY g.id
      ORDER BY g.name
      LIMIT $start, $limit
    ");
  }

}
