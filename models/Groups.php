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
  
  public function removeUser( $userid ) {
    
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
  
}
