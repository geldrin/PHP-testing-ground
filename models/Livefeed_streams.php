<?php
namespace Model;

class Livefeed_streams extends \Springboard\Model {
  
  public function checkUniqueKeycode( $keycode, $existingkeycode = null ) {
    
    $found = $this->db->getOne("
      SELECT COUNT(*)
      FROM livefeed_streams
      WHERE
        keycode = " . $this->db->qstr( $keycode ) . " OR
        contentkeycode = " . $this->db->qstr( $keycode ) . "
    ");
    
    if ( $found or $keycode == $existingkeycode )
      $keycode = $this->generateUniqueKeycode( $existingkeycode );
    
    return $keycode;
    
  }
  
  public function generateUniqueKeycode( $existingkeycode = null ) {
    
    $found = true;
    while( $found ) {
      
      $keycode = mt_rand( 100000, 999999 );
      
      if ( $keycode == $existingkeycode )
        continue;
      
      $found = $this->db->getOne("
        SELECT COUNT(*)
        FROM livefeed_streams
        WHERE
          keycode = '" . $keycode . "' OR
          contentkeycode = '" . $keycode . "'
      ");
      
    }
    
    return $keycode;
    
  }
  
  public function getStatusForIDs( $ids ) {
    
    if ( !$ids or !is_array( $ids ) or empty( $ids ) or count( $ids ) > 200 )
      return array();
    
    foreach ( $ids as $key => $value ) {
      
      $value = intval( $value );
      if ( !$value )
        return array();
      
      $ids[ $key ] = $this->db->qstr( $value );
      
    }
    
    return $this->db->getArray("
      SELECT id, status
      FROM livefeed_streams
      WHERE id IN(" . implode(", ", $ids ) . ")
    ");
    
  }
  
}