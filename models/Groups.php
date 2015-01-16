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

  public function getUserCount() {
    
    $this->ensureID();
    return $this->db->getOne("
      SELECT COUNT(*)
      FROM groups_members AS gm
      WHERE gm.groupid = '" . $this->id . "'
      GROUP BY gm.userid
    ");
    
  }
  
  public function getUserArray( $start, $limit, $orderby ) {
    
    $this->ensureID();
    return $this->db->getArray("
      SELECT
        u.id,
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
    if (
         $user['isadmin'] or
         $user['isclientadmin'] or
         $user['isuploader'] or
         $user['ismoderateduploader'] or
         $user['isliveadmin'] or
         $user['iseditor']
       )
      return true;

    return false;
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
      FROM
        groups AS g,
        groups_members AS gm
      WHERE
        $where
        gm.groupid       = g.id AND
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
         $this->row['organizationid'] == $user['organizationid'] and
         (
           $user['isadmin'] or $user['isclientadmin'] or $user['iseditor'] or
           $this->row['userid'] == $user['id']
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
          " . ( $organization['fullnames']
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
}
