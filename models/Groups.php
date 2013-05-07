<?php
namespace Model;

class Groups extends \Springboard\Model {
  
  public function getUserCount() {
    
    $this->ensureID();
    return $this->db->getOne("
      SELECT COUNT(*)
      FROM groups_members
      WHERE groupid = '" . $this->id . "'
    ");
    
  }
  
  public function getUserArray( $start, $limit, $orderby ) {
    
    $this->ensureID();
    return $this->db->getArray("
      SELECT
        u.id,
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
      LIMIT 1
    ");
    
  }
  
  protected function getUserGroupWhere( $user ) {
    
    if ( !$user['isadmin'] and !$user['isclientadmin'] )
      return "
        FROM
          groups AS g,
          groups_members AS gm
        WHERE
          g.organizationid = '" . $user['organizationid'] . "' AND
          (
            g.userid = '" . $user['id'] . "' OR
            (
              gm.userid  = '" . $user['id'] . "' AND
              gm.groupid = g.id
            )
          )
      ";
    else
      return "
        FROM
          groups AS g
        WHERE
          g.organizationid = '" . $user['organizationid'] . "'
      ";
    
  }
  
  public function getGroupCount( $user ) {
    
    $where = $this->getUserGroupWhere( $user );
    return $this->db->getOne("
      SELECT COUNT(*)
      $where
    ");
    
  }
  
  public function getGroupArray( $start, $limit, $orderby, $user ) {
    
    $where = $this->getUserGroupWhere( $user );
    return $this->db->getArray("
      SELECT g.*
      $where
      ORDER BY $orderby
      LIMIT $start, $limit
    ");
    
  }
  
  public function deleteAndClearMembers() {
    
    $this->ensureID();
    $this->db->query("
      DELETE FROM groups_members
      WHERE groupid = '" . $this->id . "'
    ");
    $this->db->query("
      DELETE FROM groups
      WHERE id = '" . $this->id . "'
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
  
}