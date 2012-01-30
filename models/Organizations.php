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
  
}
