<?php
namespace Model;

class Organizations_news extends \Springboard\Model\Multilingual {
  public $multistringfields = array( 'title', 'lead', 'body' );
  
  protected static function getNewsSringsSQL( $language = null, $skipselect = false ) {
    
    if ( !$language )
      $language = \Springboard\Language::get();
    
    if ( $skipselect )
      $sql = '';
    else
      $sql = "
        slead.value  AS lead,
        stitle.value AS title,
        sbody.value  AS body
      ";
    
    return $sql . "
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
        slead.value          <> '' AND
        stitle.value         <> '' AND
        sbody.value          <> ''
    ";
    
  }
  
  protected static function getNewsUserWhere( $organizationid, $user ) {
    
    $where = '';
    if ( !$user['iseditor'] or $user['organizationid'] != $organizationid ) {
      
      $where = " AND
        orgn.disabled = '0' AND
        orgn.starts <= NOW() AND orgn.ends >= NOW()
      ";
      
    }
    
    return $where;
    
  }
  
  public function getRecentNews( $limit, $organizationid ) {
    
    $items = $this->db->getArray("
      SELECT orgn.*,
      " . self::getNewsSringsSQL() . " AND
        orgn.disabled = '0' AND
        orgn.organizationid = '$organizationid' AND
        orgn.starts <= NOW() AND orgn.ends >= NOW()
      ORDER BY weight, starts DESC
      LIMIT 0, $limit
    ");
    
    return $items;
    
  }
  
  public function selectAccessibleNews( $id, $organizationid, $user, $language = null ) {
    
    if ( $id <= 0 )
      return false;
    
    if ( !$language )
      $language = \Springboard\Language::get();
    
    if ( $user['isnewseditor'] and $user['organizationid'] == $organizationid )
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
      " . self::getNewsSringsSQL( $language ) . " AND
        $where
    ");
    
    if ( !empty( $data ) ) {
      
      $this->id  = $data['id'];
      return $data;
      
    } else
      return false;
    
  }
  
  public function getNewsCount( $organizationid, $user ) {
    
    return $this->db->getOne("
      SELECT COUNT(*)
        " . self::getNewsSringsSQL( null, true ) . " AND
        orgn.organizationid = '" . $organizationid . "'
        " . self::getNewsUserWhere( $organizationid, $user ) . "
      LIMIT 1
    ");
    
  }
  
  public function getNewsArray( $start, $limit, $orderby, $organizationid, $user ) {
    
    return $this->db->getArray("
      SELECT orgn.*,
        " . self::getNewsSringsSQL() . " AND
        orgn.organizationid = '" . $organizationid . "'
        " . self::getNewsUserWhere( $organizationid, $user ) . "
      ORDER BY $orderby
      LIMIT $start, $limit
    ");
    
  }
  
}
