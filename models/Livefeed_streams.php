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

  public function generateUniqueKeycode( $existingkeycode = null, $length = 6 ) {

    $found = true;
    while( $found ) {

      $keycode = '';
      while (true) {
        $keycode .= mt_rand( 100000, 999999 );
        $keycode = substr( $keycode, 0, $length );

        if ( strlen( $keycode ) == $length )
          break;
      }

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
