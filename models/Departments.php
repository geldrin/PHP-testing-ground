<?php
namespace Model;

class Departments extends \Springboard\Model {
  
  public function getDepartmentTree( $organizationid, $parentid = 0, $maxlevel = 2, $currentlevel = 0 ) {
    
    if ( $currentlevel >= $maxlevel )
      return array();
    
    $currentlevel++;
    $this->clearFilter();
    $this->addFilter('parentid',       $parentid );
    $this->addFilter('organizationid', $organizationid );
    
    $items = $this->getArray( false, false, false, 'weight, name');
    
    foreach( $items as $key => $value )
      $items[ $key ]['children'] = $this->getDepartmentTree(
        $organizationid,
        $value['id'],
        $maxlevel,
        $currentlevel
      );
    
    return $items;
    
  }
  
  public function findChildrenIDs( $parentid = null ) {
    
    if ( !$parentid ) {
      
      $this->ensureID();
      $parentid = $this->id;
      
    }
    
    $children = $this->db->getCol("
      SELECT id
      FROM departments
      WHERE parentid = " . $this->db->qstr( $parentid )
    );
    
    foreach( $children as $child )
      $children = array_merge( $children, $this->findChildrenIDs( $child ) );
    
    return $children;
   
  }
  
  public function getUserCount() {
    
    $this->ensureID();
    return $this->db->getOne("
      SELECT COUNT(*)
      FROM users_departments
      WHERE departmentid = '" . $this->id . "'
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
        users_departments AS ud
      WHERE
        u.id            = ud.userid AND
        ud.departmentid = '" . $this->id . "' AND
        u.disabled      = '0'
      ORDER BY $orderby
      LIMIT $start, $limit
    ");
    
  }
  
  public function deleteUser( $userid ) {
    
    $this->ensureID();
    $this->db->query("
      DELETE FROM users_departments
      WHERE
        departmentid = '" . $this->id . "' AND
        userid  = " . $this->db->qstr( $userid ) . "
      LIMIT 1
    ");
    
  }
  
  public function deleteAndClearMembers() {
    
    $this->ensureID();
    $this->db->query("
      DELETE FROM users_departments
      WHERE departmentid = '" . $this->id . "'
    ");
    $this->db->query("
      DELETE FROM departments
      WHERE id = '" . $this->id . "'
    ");
    
  }
  
  public function getSearchCount( $searchterm, $organization, $userModel ) {
    $this->ensureID();
    return $this->db->getOne("
      SELECT COUNT(*)
      FROM
        users AS u,
        users_departments AS ud
      WHERE
        ud.userid       = u.id AND
        ud.departmentid = '" . $this->id . "' AND
        u.disabled      = '0' AND
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
        users_departments AS ud
      WHERE
        ud.userid       = u.id AND
        ud.departmentid = '" . $this->id . "' AND
        u.disabled      = '0' AND
        " . $userModel->getSearchWhere( $originalterm, $organization, 'u.' ) . "
      ORDER BY $order
      LIMIT $start, $limit
    ");
  }

}
