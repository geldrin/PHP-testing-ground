<?php
namespace Model;

class Categories extends \Springboard\Model\Multilingual {
  public $multistringfields = array( 'name', 'namehyphenated' );
  
  public function updateVideoCounters() {

    $this->ensureObjectLoaded();

    $childrenids   = $this->cachedFindChildrenIDs( $this->id );
    $childrenids[] = $this->id;

    $this->db->query("
      UPDATE categories
      SET numberofrecordings = (
        -- az adott kategoriahoz rendelt felvetelek szama
        SELECT COUNT( DISTINCT r.id )
        FROM
          recordings r,
          recordings_categories rc
        WHERE
          rc.categoryid IN('" . implode("', '", $childrenids ) . "') AND
          r.id             = rc.recordingid AND
          r.status         = 'onstorage' AND
          r.approvalstatus = 'approved' AND
          (
            r.visiblefrom IS NULL OR
            r.visibleuntil IS NULL OR
            (
              r.visiblefrom  <= CURRENT_DATE() AND
              r.visibleuntil >= CURRENT_DATE()
            )
          )
      )
      WHERE id = '" . $this->id . "'
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
    $this->addFilter('parentid',       $parentid );
    $this->addFilter('organizationid', $organizationid );
    
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
  
  // --------------------------------------------------------------------------
  public function delete( $id, $magic_quotes_gpc = 0 ) {

    $this->db->query("
      DELETE FROM recordings_categories
      WHERE categoryid = " . $this->db->qstr( $id )
    );

    return parent::delete( $id, $magic_quotes_gpc );

  }

}
