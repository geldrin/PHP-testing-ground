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
  
}
