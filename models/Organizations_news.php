<?php
namespace Model;

class Organizations_news extends \Springboard\Model\Multilingual {
  public $multistringfields = array( 'title', 'lead', 'body' );
  
  public function getRecentNews( $limit, $organizationid ) {
    
    $this->clearFilter();
    $this->addFilter('organizationid', $organizationid );
    $this->addFilter('disabled', 0 );
    $this->addTextFilter("starts <= NOW() AND ends >= NOW()");
    
    $items = $this->getArray( 0, $limit, false, 'weight, starts DESC' );
    
    return $items;
    
  }
  
  public function selectAccessibleNews( $id, $organizationid, $user, $language = null ) {
    
    if ( $id <= 0 )
      return false;
    
    if ( !$language )
      $language = \Springboard\Language::get();
    
    if ( $user['id'] and $user['iseditor'] and $user['organizationid'] == $organizationid )
      $where = "orgn.id = '" . $id . "'";
    else
      $where = "
        orgn.organizationid = '" . $organizationid . "' AND
        orgn.disabled = '0' AND
        orgn.starts <= NOW() AND orgn.ends >= NOW() AND
        orgn.id = '" . $id . "'
      ";
    
    $data = $this->db->getRow("
      SELECT
        orgn.id,
        orgn.starts,
        orgn.ends,
        slead.value  AS lead,
        stitle.value AS title,
        sbody.value  AS body
      FROM
        organizations_news AS orgn,
        strings AS stitle,
        strings AS slead,
        strings AS sbody
      WHERE
        stitle.translationof = orgn.title_stringid AND
        stitle.language      = '$language' AND
        slead.translationof  = orgn.lead_stringid AND
        slead.language       = '$language' AND
        sbody.translationof  = orgn.body_stringid AND
        sbody.language       = '$language' AND
        $where
    ");
    
    if ( !empty( $data ) ) {
      
      $this->id  = $data['id'];
      return $data;
      
    } else
      return false;
    
  }
  
}
