<?php
namespace Model;

class Livefeed_streams extends \Springboard\Model {
  
  public function getStreamsForFeed( $feedid ) {
    
    $ret = array();
    $this->clearFilter();
    $this->addFilter('livefeedid', $feedid );
    $streams = $this->getArray();
    
    foreach( $streams as $stream )
      $ret[ $stream['id'] ] = $stream;
    
    return $ret;
    
  }
  
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