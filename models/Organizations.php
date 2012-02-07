<?php
namespace Model;

class Organizations extends \Springboard\Model {
  
  public function checkDomain( $domain ) {
    
    $this->clearFilter();
    $this->addFilter('domain', $domain, false, false );
    $this->addFilter('disabled', 0 );
    $organization = $this->getRow();
    
    if ( !$organization )
      return false;
    
    $this->id  = $organization['id'];
    $this->row = $organization;
    
    return true;
    
  }
  
  public function findChildrenIDs( $parentid = null ) {
    
    if ( $parentid === null )
      $this->ensureID();
    
    if ( !$parentid )
      $parentid = $this->db->qstr( $this->id );
    else
      $parentid = $this->db->qstr( $parentid );
    
    $children = $this->db->getCol("
      SELECT id
      FROM organizations
      WHERE parentid = " . $parentid
    );
    
    foreach( $children as $parentid )
      $children = array_merge( $children, $this->findChildrenIDs( $parentid ) );
    
    return $children;
    
  }
  
}
