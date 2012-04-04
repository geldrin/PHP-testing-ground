<?php
namespace Model;

class Organizations_news extends \Springboard\Model {
  
  public function getRecentNews( $limit, $organizationid ) {
    
    $this->clearFilter();
    $this->addFilter('organizationid', $organizationid );
    $this->addFilter('disabled', 0 );
    $this->addTextFilter("starts <= NOW() AND ends >= NOW()");
    
    $items = $this->getArray( 0, $limit, false, 'weight, starts DESC' );
    
    return $items;
    
  }
  
  public function selectAccessibleNews( $id, $organizationid, $user ) {
    
    if ( $id <= 0 )
      return false;
    
    if ( $user['id'] and $user['iseditor'] and $user['organizationid'] == $organizationid )
      $where = "id = '" . $id . "'";
    else
      $where = "
        organizationid = '" . $organizationid . "' AND
        disabled = '0' AND
        starts <= NOW() AND ends >= NOW() AND
        id = '" . $id . "'
      ";
    
    $data = $this->db->getRow("
      SELECT *
      FROM organizations_news
      WHERE $where
    ");
    
    if ( !empty( $data ) ) {
      
      $this->id  = $data['id'];
      $this->row = $data;
      
      return $data;
      
    } else
      return false;
    
  }
  
}
