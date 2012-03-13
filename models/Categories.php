<?php
namespace Model;

class Categories extends \Springboard\Model\Multilingual {
  public $multistringfields = array( 'name' );
  
  public function updateVideoCounters() {
    
    $this->ensureObjectLoaded();
    
    /* leszarmazott kategoriak szamlaloi */
    $counter = $this->db->getOne("
      SELECT SUM( numberofrecordings )
      FROM categories
      WHERE parentid = '" . $this->id . "'
    ");
    
    if ( !is_numeric( $counter ) )
      $counter = 0;
    
    $this->db->query("
      UPDATE categories
      SET numberofrecordings = 
        (
          -- az adott kategoriahoz rendelt felvetelek szama
          SELECT COUNT(*)
          FROM recordings r, recordings_categories rc
          WHERE
            r.id = rc.recordingid AND
            rc.categoryid = '" . $this->id . "' AND
            ( r.status = 'onstorage' OR r.status = 'live' ) AND
            r.ispublished = 1 AND
            r.accesstype = 'public' AND
            (
              r.visiblefrom IS NULL OR
              r.visibleuntil IS NULL OR
              (
                r.visiblefrom  <= NOW() AND
                r.visibleuntil >= NOW()
              )
            )
        ) + " . $counter . "
      WHERE
        id = '" . $this->id . "'
    ");
    
    $row = $this->row;
    if ( $row['parentid'] ) {
      
      //felfele is bejarjuk
      $parent = $this->bootstrap->getModel('categories');
      while ( $row['parentid'] ) {
        $parent->select( $row['parentid'] );
        $parent->updateVideoCounters();
        $row = $parent->row;
      }
      
    }
    
  }
  
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
