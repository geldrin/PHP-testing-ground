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
  
}