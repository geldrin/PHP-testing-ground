<?php
namespace Model;

class Departments extends \Springboard\Model {
  
  // --------------------------------------------------------------------------
  public function delete( $id, $magic_quotes_gpc = 0 ) {

    $this->db->query("
      DELETE FROM users_departments
      WHERE departmentid = " . $this->db->qstr( $id )
    );

    $this->db->query("
      DELETE FROM access
      WHERE departmentid = " . $this->db->qstr( $id )
    );

    return parent::delete( $id, $magic_quotes_gpc );

  }

  public function getDepartmentTree( $organizationid, $orderby, $parentid = 0, $maxlevel = 2, $currentlevel = 0 ) {
    
    if ( $currentlevel >= $maxlevel )
      return array();
    
    $currentlevel++;
    $items = $this->db->getArray("
      SELECT
        d.*,
        COUNT(*) AS usercount
      FROM
        departments AS d LEFT JOIN users_departments AS ud ON(
          ud.departmentid = d.id
        )
      WHERE
        d.parentid       = '$parentid' AND
        d.organizationid = '$organizationid'
      GROUP BY d.id
      ORDER BY $orderby
    ");
    
    foreach( $items as $key => $value )
      $items[ $key ]['children'] = $this->getDepartmentTree(
        $organizationid,
        $orderby,
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
      FROM
        users_departments AS ud,
        users AS u
      WHERE
        ud.departmentid = '" . $this->id . "' AND
        u.id            = ud.userid AND
        u.disabled      = '0'
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
