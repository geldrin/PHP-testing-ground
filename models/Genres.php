<?php
namespace Model;

class Genres extends \Springboard\Model\Multilingual {
  public $multistringfields = array( 'name' );
  
  public function getTreeArray( $order = null, $parentid = 0 ) {
    
    if ( !$order )
      $order = 'g.weight, g.name';
    
    $this->addTextFilter("
      s.translationof = g.name_stringid AND
      s.language      = '" . \Springboard\Language::get() . "' AND
      g.parentid      = '" . $parentid . "'
    ", 'treearray' );
    
    $items = $this->db->getArray("
      SELECT g.*, s.value as name
      FROM genres AS g, strings AS s
      " . $this->getFilter() . "
      ORDER BY $order
    ");
    
    foreach( $items as $key => $value )
      $items[ $key ]['children'] = $this->getTreeArray( $order, $value['id'] );
    
    return $items;
    
  }

  // --------------------------------------------------------------------------
  public function delete( $id, $magic_quotes_gpc = 0 ) {

    $this->db->query("
      DELETE FROM recordings_genres
      WHERE genreid = " . $this->db->qstr( $id )
    );
    return parent::delete( $id, $magic_quotes_gpc );

  }

}
