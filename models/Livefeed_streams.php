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
  
  public function updateFeedThumbnail() {
    $this->ensureObjectLoaded();
    // befrissítjuk a livefeedet, nem problema ha NULL-ozzuk
    $this->db->execute("
      UPDATE livefeeds AS lf
      SET lf.indexphotofilename = (
        SELECT lfs.indexphotofilename
        FROM livefeed_streams AS lfs
        WHERE
          lfs.indexphotofilename IS NOT NULL AND
          lfs.indexphotofilename <> '' AND
          lfs.livefeedid          = '" . $this->row['livefeedid'] . "'
        ORDER BY lfs.id ASC
        LIMIT 1
      )
      WHERE lf.id = '" . $this->row['livefeedid'] . "'
      LIMIT 1
    ");

    // majd megkeressuk a csatornat ahove a livefeed tartozik hogy azt is befrissithessuk
    $channelid = $this->db->getOne("
      SELECT channelid
      FROM livefeeds
      WHERE id = '" . $this->row['livefeedid'] . "'
      LIMIT 1
    ");

    // tobb livefeed lehet a csatorna alatt, az elso aminek van thumbnailje
    // azt allitjuk be a csatornanak
    $this->db->execute("
      UPDATE channels AS c
      SET c.indexphotofilename = (
        SELECT lf.indexphotofilename
        FROM livefeeds AS lf
        WHERE
          lf.indexphotofilename IS NOT NULL AND
          lf.indexphotofilename <> '' AND
          lf.channelid           = '$channelid'
        ORDER BY lf.id ASC
        LIMIT 1
      )
      WHERE c.id = '$channelid'
      LIMIT 1
    ");
  }

}