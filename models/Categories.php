<?php
namespace Model;

class Categories extends \Springboard\Model\Multilingual {
  public $multistringfields = array( 'name' );
  
  public function getCategoryTree( $organizationid, $parentid = 0, $maxlevel = 2, $currentlevel = 0 ) {
    
    if ( $currentlevel >= $maxlevel )
      return array();
    
    $currentlevel++;
    $this->clearFilter();
    $this->addFilter('parentid',       $parentid, true, true, 'parentid' );
    $this->addFilter('organizationid', $organizationid, true, true, 'organizationid' );
    
    $items = $this->getArray( false, false, false, 'weight, s1.value');
    
    foreach( $items as $key => $value )
      $items[ $key ]['children'] = $this->getCategoryTree(
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
      FROM categories
      WHERE 
        parentid = " . $this->db->qstr( $parentid )
    );
    
    foreach( $children as $child )
      $children = array_merge( $children, $this->findChildrenIDs( $child ) );
    
    return $children;
   
  }
  
}
