<?php
namespace Model;

class Livefeed_streams extends \Springboard\Model {
  
  public function checkUniqueStreamid( $streamid, $existingstreamid = null ) {
    
    $found = $this->db->getOne("
      SELECT COUNT(*)
      FROM livefeed_streams
      WHERE
        streamid = " . $this->db->qstr( $streamid ) . " OR
        contentstreamid = " . $this->db->qstr( $streamid ) . "
    ");
    
    if ( $found or $streamid == $existingstreamid )
      $streamid = $this->generateUniqueStreamid( $existingstreamid );
    
    return $streamid;
    
  }
  
  public function generateUniqueStreamid( $existingstreamid = null ) {
    
    $found = true;
    while( $found ) {
      
      $streamid = mt_rand( 100000, 999999 );
      
      if ( $streamid == $existingstreamid )
        continue;
      
      $found = $this->db->getOne("
        SELECT COUNT(*)
        FROM livefeed_streams
        WHERE
          streamid = '" . $streamid . "' OR
          contentstreamid = '" . $streamid . "'
      ");
      
    }
    
    return $streamid;
    
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
          lfs.livefeedid          = '" . $this->row['livefeedid'] . "' AND
          (
            lfs.status           <> 'markedfordeletion' OR
            lfs.status IS NULL
          )
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
          lf.channelid           = '$channelid' AND
          (
            lf.status           <> 'markedfordeletion' OR
            lf.status IS NULL
          )
        ORDER BY lf.id ASC
        LIMIT 1
      )
      WHERE c.id = '$channelid'
      LIMIT 1
    ");
  }

  public function markAsDeleted() {
    
    $this->ensureID();
    $this->db->execute("
      UPDATE livefeed_streams
      SET status = 'markedfordeletion'
      WHERE id = '" . $this->id . "'
      LIMIT 1
    ");

  }

}