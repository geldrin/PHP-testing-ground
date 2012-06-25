<?php
namespace Model;

class Channels extends \Springboard\Model {
  var $channelroots = array();
  
  /*
   *
   * A metodust akkor hivjuk meg a checkIndexPhotoFilename-bol, ha egy csatornahoz rendelt 
   * video valamilyen modon torlodott, letiltasra kerult, statusza valtozott.
   *
   * EKkor az adott csatornanak kell a videoszamlalojat modositani, valamint az osszes szulojenek
   * hasonlokeppen.
   *
   */
  
  function updateVideoCounters() {
  
    $this->ensureObjectLoaded();
    
    /* leszarmazott csatornak szamlaloi */
    $counter = $this->db->getOne("
      SELECT 
        SUM( numberofrecordings ) 
      FROM
        channels
      WHERE 
        parentid = '" . $this->id . "'
    ");
    
    if ( !is_numeric( $counter ) )
      $counter = 0;

    $this->db->query("
      UPDATE channels
      SET numberofrecordings = 
        (
          -- az adott csatornahoz rendelt felvetelek szama
          SELECT
            COUNT(*)
          FROM
            recordings r, channels_recordings cr
          WHERE
            r.id = cr.recordingid AND
            cr.channelid = '" . $this->id . "' AND
            ( r.status = 'onstorage' OR r.status = 'live' ) AND
            r.ispublished = 1 AND
            r.accesstype = 'public' AND
            (
              r.visiblefrom IS NULL OR
              r.visibleuntil IS NULL OR
              (
                r.visiblefrom  <= CURRENT_DATE() AND
                r.visibleuntil >= CURRENT_DATE()
              )
            )
        ) + " . $counter . "
      WHERE
        id = '" . $this->id . "'
    ");
    
    $row = $this->row;
    if ( $row['parentid'] ) {
    
      $parent = $this->bootstrap->getModel('channels');
      while ( $row['parentid'] ) {
        $parent->select( $row['parentid'] );
        $parent->updateVideoCounters();
        $row = $parent->row;
      }
      
    }

  }

  function findChannelWithoutIndexFilename( $channelid ) {

    $channel = $this->bootstrap->getModel('channels');
    $channel->select( $channelid );
    $false = false;

    if ( !strlen( trim( $channel->row['indexphotofilename'] ) ) ) {

      // az aktualis csatorna tehat ures, eddig ez a potencialis
      // visszatero ertek

      if ( $channel->row['parentid'] ) {
        // van szuloje, a szulonek vajon van-e indexfotoja?
        // rekurziobol kiderul, mindenkepp csatornat kell visszakapnunk.
        $fromparent = $this->findChannelWithoutIndexFilename( $channel->row['parentid'] );
        if ( !strlen( $fromparent->row['indexphotofilename'] ) )
          return $fromparent;
      }

    }

    // ha az adott csatornanak van indexfotoja, vagy
    // ha nincs szuloje, vagy annak ki volt toltve a fileneve, 
    // akkor az aktualis csatorna a legkulso updatelendo.
    return $channel;

  }

  /*
   * Egy adott csatorna alatti csatornak id-jet adja vissza, rekurziv
   * bejarassal.
   */

  function findChildrenIDs( $parentid = null, $ispublic = null ) {
    
    $this->ensureID();

    if ( !$parentid )
      $parentid = $this->db->qstr( $this->id );
    else
      $parentid = $this->db->qstr( $parentid );
    
    $sql = "
      SELECT id
      FROM channels
      WHERE parentid = " . $parentid . "
      ORDER BY weight
    ";
    
    if ( $ispublic !== null )
      $sql .= " AND ispublic = '" . (int)$ispublic . "'";
    
    $children = $this->db->getCol( $sql );
    
    foreach( $children as $parentid )
      $children = array_merge( $children, $this->findChildrenIDs( $parentid ) );
    
    return $children;
   
  }
  
  function findParents( $id = null ) {
    
    if ( !$id ) {
      $this->ensureID();
      $id = $this->db->qstr( $this->id );
    } else
      $id = $this->db->qstr( $id );

    $parents = $this->db->getAssoc("
      SELECT parentid, id
      FROM channels
      WHERE id = $id
      ORDER BY weight
    ");
    
    foreach( $parents as $parentid => $id ) {
      
      if ( !$parentid )
        continue;
      
      $parents = array_merge( $parents, $this->findParents( $parentid ) );
      
    }
    
    return $parents;
    
  }

  /*
   * A metodus celja, hogy egy csatorna szamara talaljon megfelelo
   * indexphotofilename erteket. Az egesz csatornaag csucsat probalja 
   * ellatni ilyen ertekkel. Ha az adott csatornanak mar van ilyenje,
   * akkor nincs teendo.
   *
   * Ezt a fuggvenyt video-csatorna osszerendeleskor hivjuk meg.
   */

  function updateIndexFilename( $forceupdate = false ) {

    $this->ensureObjectLoaded();

    if ( !$forceupdate and strlen( trim( @$this->row['indexphotofilename'] ) ) )
      return;

    $parent = $this->findChannelWithoutIndexFilename( $this->id );
    // - vagy visszaadja a sajat csatornat, mert ki van toltve az
    //   indexfotoja, de a forceolas miatt tovabb kell mennunk
    // - vagy visszaadja egy szulojet, aminek nincs kitoltve az
    //   indexfotoja, de annak a szulojenek ki van

    if ( !$parent )
      $parent   = $this;

    $children = $parent->findChildrenIDs();
    $parentid = $parent->id;

    // a children csatornak felol keresunk egyetlen videot
    $indexphotofilename = $this->bootstrap->getModel('recordings')->getIndexPhotoFromChannels( 
      array_merge( array( $parentid ), $children ),
      $parent->row['ispublic']
    );

    $parent->row['indexphotofilename'] = $indexphotofilename;
    $parent->updateRow();

  }

  function &delete( $id, $magic_quotes_gpc = 0 ) {
    
    if ( !@$this->row['id'] != $id )
      $this->select( $id );
    
    $childrenids   = $this->findChildrenIDs( $id );
    $childrenids[] = $id;
    
    $this->db->execute("
      DELETE FROM
        channels_contributors
      WHERE
        channelid IN('" . implode("', '", $childrenids ) . "')
    ");
    
    $this->db->execute("
      DELETE FROM
        channels_recordings
      WHERE
        channelid IN('" . implode("', '", $childrenids ) . "')
    ");
    
    $this->updateVideoCounters();
    $ret = $this->db->execute("
      DELETE FROM
        channels
      WHERE
        id IN('" . implode("', '", $childrenids ) . "')
    ");
    
    return $ret;
    
  }
  
  function update( &$rs, $values ) {

    if ( isset( $values['ispublic'] ) and @$this->row['ispublic'] != $values['ispublic'] ) {
      
      $children   = $this->findChildrenIDs();
      $children[] = $this->id;
      
      $this->db->execute("
        UPDATE channels
        SET ispublic = '" . $values['ispublic'] . "'
        WHERE id IN('" . implode("', '", $children ) . "')
      ");
      
    }
    
    return parent::update( $rs, $values );

  }
  
  function getArray( $start = false, $limit = false, $where = false, $orderby = false ) {
    
    if ( $where )
      $this->addTextFilter( $where );
    
    return
      $this->db->getArray(
        "SELECT
          *,
          ( SELECT count(*) FROM channels_recordings WHERE channelid = channels.id ) AS recordcount
         FROM " . $this->table . " " .
        $this->getFilter() . " " .
        ( strlen( $orderby ) ? 'ORDER BY ' . $orderby : '' ) . " " .
        ( is_numeric( $start ) ? 'LIMIT ' . $start . ', ' . $limit : "" )
      );
    
  }
  
  function getSingleChannelTree( $rootid, $orderby = 'c.weight, c.nameoriginal, c.nameenglish', $parentid = 0, $ispublic = null ) {
    
    if ( $rootid )
      $this->addFilter('c.id', $rootid, true, false, 'rootid');
    
    if ( $parentid !== null )
      $this->addFilter('c.parentid', $parentid, true, false, 'parentid');
    
    if ( $ispublic !== null )
      $this->addFilter('c.ispublic', (int)$ispublic, true, false, 'ispublic');
    
    $this->addTextFilter('c.channeltypeid = ct.id', 'channeltype');
    
    $channels = $this->getChannelArray( false, false, false, $orderby );
    $this->clearFilter('rootid');
    
    foreach( $channels as $key => $channel )
      $channels[ $key ]['children'] = $this->getSingleChannelTree( false, $orderby, $channel['id'] );
    
    return $channels;
    
  }
  
  function findIDInChildren( $channeltree ) {
    
    if ( empty( $channeltree ) )
      return false;
    
    foreach( $channeltree as $channel ) {
      
      if ( $channel['id'] == $this->id )
        return true;
      
      if ( $this->findIDInChildren( @$channel['children'] ) )
        return true;
      
    }
    
    return false;
    
  }
  
  function getChannelCount( $where = false ) {
    
    $this->addTextFilter('c.channeltypeid = ct.id', 'channeltype');
    
    if ( $where )
      $this->addTextFilter( $where );
    
    $ret = $this->db->getOne(
      'SELECT count(*) FROM channels AS c, channel_types AS ct ' . $this->getFilter()
    );
    
    $this->clearFilter('channeltype'); // pop the c.channeltypeid = ct.id
    
    return $ret;
    
  }
  
  function getChannelTree( $start = false, $limit = false, $where = false, $orderby = 'c.weight, c.nameoriginal, c.nameenglish', $parentid = 0 ) {
    
    if ( $parentid !== null )
      $this->addFilter('c.parentid', $parentid, true, false, 'parentid');
    
    $this->addTextFilter('c.channeltypeid = ct.id', 'channeltype');
    
    $channels = $this->getChannelArray( $start, $limit, $where, $orderby );
    foreach( $channels as $key => $channel )
      $channels[ $key ]['children'] = $this->getChannelTree( false, false, false, $orderby, $channel['id'] );
    
    return $channels;
    
  }
  
  function getChannelArray( $start, $limit, $where, $orderby ) {
    
    if ( $where )
      $this->addTextFilter( $where );
    
    $this->addTextFilter("s.translationof = ct.name_stringid AND s.language = '". \Springboard\Language::get() . "'", 'channeltypestring');

    $ret =
      $this->db->getArray(
        "SELECT
          c.*,
          s.value AS channeltype,
          ( 
            SELECT count(*) 
            FROM channels_recordings 
            WHERE 
              channelid = c.id 
          ) AS recordcount
         FROM
           channels AS c,
           channel_types AS ct,
           strings AS s " .
        $this->getFilter() .
        ( strlen( $orderby ) ? 'ORDER BY ' . $orderby : '' ) . " " .
        ( is_numeric( $start ) ? 'LIMIT ' . $start . ', ' . $limit : "" )
      );
    
    $this->clearFilter('channeltype');
    $this->clearFilter('channeltypestring');
    return $ret;
    
  }
  
  function getFavoriteChannelID() {
    
    $channelid = $this->db->getOne("
      SELECT c.id
      FROM channels c, channel_types ct
      WHERE
        ( c.userid IS NULL OR c.userid = 0 ) AND
        c.channeltypeid = ct.id AND
        ct.isfavorite = 1
      ORDER BY c.id
    ");
    
    if ( !$channelid )
      throw new Exception("favorite channel missing");
    else
      return $channelid;
    
  }
  
  function getUsersChannels( $userid, $organizationids = null ) {

    if ( !$userid )
      return array();
    
    $where = "c.userid = '$userid'";
    if ( !empty( $organizationids ) )
      $where = "(
        c.userid = '" . $userid . "' OR
        c.organizationid IN('" . implode("', '", $organizationids ) . "')
      )";
    
    // az eventeket is belevesszuk a channelokba mert ez jelenik meg a
    // video nezo oldalon mint lista amihez hozza lehet adni a videot
    return $this->db->getArray("
      SELECT c.*
      FROM
        channels c, channel_types ct
      WHERE
        ct.isfavorite = 0 AND
        c.channeltypeid = ct.id AND
        $where
      ORDER BY
        ct.ispersonal DESC
    ");

  }
  
  // this function serves the addtochannel function of the recordings module
  // it returns a channel tree with the users recordings
  // if a recording is present in the channel it's signalled so we can
  // show it to the user
  function addChannelStateToArray( $recordingid, &$array, $recordingschannels = null ) {
    
    if ( !$recordingschannels ) {
      
      $recordingschannels = $this->db->getAssoc("
        SELECT
          channelid, id
        FROM
          channels_recordings
        WHERE
          recordingid = " . $this->db->qstr( $recordingid ) . "
      ");
      
    }
    
    foreach( $array as $key => $value ) {
      
      if ( array_key_exists( $value['id'], $recordingschannels ) ) {
        
        $array[ $key ]['active'] = true;
        $array[ $key ]['channelsrecordingsid'] = $recordingschannels[ $value['id'] ];
        
      }
      
      if ( !empty( $value['children'] ) )
        $this->addChannelStateToArray( $recordingid, $array[ $key ]['children'], $recordingschannels );
      
    }
    
    return $array;
    
  }
  
  function getValidYearsForIDs( $ids ) {
    
    return $this->db->getCol("
      SELECT DISTINCT YEAR( starttimestamp )
      FROM channels
      WHERE
        channeltypeid IN ('" . implode("', '", $ids ) . "') AND
        starttimestamp IS NOT NULL
      ORDER BY
        starttimestamp DESC
    ");
    
  }
  
  function getValidYearsForChannels() {
    
    return $this->db->getCol("
      SELECT DISTINCT YEAR( c.starttimestamp )
      FROM channels c, channel_types ct
      WHERE
        c.channeltypeid = ct.id AND
        ct.isfavorite = 0 AND
        ct.isevent = 0 AND
        ( 
          ct.ispersonal = 0 OR
          ( ct.ispersonal = 1 AND c.ispublic = 1 )
        ) AND
        c.starttimestamp IS NOT NULL AND
        c.parentid = 0 AND
        c.numberofrecordings > 0
      ORDER BY
        c.starttimestamp DESC
    ");
    
  }
  
  function findRoot( $channel ) {
    
    if ( !$channel['parentid'] )
      return $this->channelroots[ $channel['id'] ] = $channel;
    
    if ( isset( $this->channelroots[ $channel['id'] ] ) )
      return $this->channelroots[ $channel['id'] ];
    
    $parent = $this->db->getRow("
      SELECT
        c.*,
        s.value AS channeltype
      FROM
        channels AS c,
        strings AS s,
        channel_types AS ct
      WHERE
        ct.id = c.channeltypeid AND
        c.ispublic = '1' AND
        s.translationof = ct.name_stringid AND s.language = '" . \Springboard\Language::get() . "' AND
        c.id = '" . $channel['parentid'] . "'
    ");
    
    if ( empty( $parent ) )
      return $this->channelroots[ $channel['id'] ] = $channel;
    
    $this->channelroots[ $channel['id'] ] = $parent;
    return $this->findRoot( $parent );
    
  }
  
  function findRootID( $parentid = null, $ispublic = null ) {
    
    if ( !$parentid ) {
      
      $this->ensureID();
      $parentid = $this->id;
      
    }
    
    $sql = "
      SELECT id, parentid
      FROM
        channels AS c
      WHERE
        c.id = '" . $parentid . "'
    ";
    
    if ( $ispublic !== null )
      $sql .= " AND ispublic = '" . (int)$ispublic . "'";
    
    $parent = $this->db->getRow( $sql );
    
    if ( empty( $parent ) )
      return $parentid;
    elseif ( $parent['parentid'] )
      return $this->findRootID( $parent['parentid'] );
    else
      return $parent['id'];
    
  }
  
  function findChannelInTree( $channeltree, $channelid ) {
    
    foreach( $channeltree as $key => $value ) {
      
      if ( $value['id'] == $channelid )
        return $channeltree[ $key ];
      elseif( !empty( $value['children'] ) )
        return $this->findChannelInTree( $value['children'], $channelid );
      
    }
    
  }
  
  function getChannelWithChanneltype( $channelid ) {
    
    return $this->db->getRow("
      SELECT c.*, s.value AS channeltype
      FROM
        channels AS c,
        channel_types AS ct,
        strings AS s
      WHERE
        c.id = '" . $channelid . "' AND
        ct.id = c.channeltypeid AND
        s.translationof = ct.name_stringid AND
        s.language = '" . \Springboard\Language::get() . "'
    ");
    
  }
  
  public function insertIntoFavorites( $recordingid, $user ) {
    
    $channelid = $this->cachedGetFavoriteChannelID();
    return $this->insertIntoChannel( $recordingid, $user, false, $channelid );
    
  }
  
  public function insertIntoChannel( $recordingid, $user, $adjustweight = false, $channelid = null ) {
    
    if ( $channelid === null ) {
      
      $this->ensureID();
      $channelid = $this->id;
      
    }
    
    if ( !$user or !isset( $user['id'] ) )
      throw new Exception('Invalid user specified');
    
    $channelrecordingsModel = $this->bootstrap->getModel('channels_recordings');
    $channelrecordingsModel->addFilter('userid', $user['id'] );
    $channelrecordingsModel->addFilter('channelid', $this->id );
    $channelrecordingsModel->addFilter('recordingid', $recordingid );
    
    $channelrecording = $channelrecordingsModel->getRow();
    if ( !empty( $channelrecording ) ) // already inserted, nothing to do
      return false;
    
    $channelrecordingsModel->insert( array(
        'userid'      => $user['id'],
        'channelid'   => $channelid,
        'recordingid' => $recordingid,
      )
    );
    
    if ( $adjustweight )
      $channelrecordingsModel->updateRow( array(
          'weight' => $channelrecordingsModel->id,
        )
      );
    
    return true;
    
  }
  
  public function getRecordingsIndexphotos() {
    
    $this->ensureID();
    
    $recordings = $this->db->getArray("
      SELECT 
        r.title, 
        r.indexphotofilename
      FROM recordings r, channels_recordings cr
      WHERE 
        cr.channelid = '" . $this->id . "' AND
        r.status = 'onstorage' AND
        cr.recordingid = r.id
    ");
    
    return $recordings;
    
  }
  
  public function getLiveFeedCountForChannel() {
    
    $this->ensureID();
    return $this->db->getOne("
      SELECT COUNT(*)
      FROM livefeeds
      WHERE channelid = '" . $this->id . "'
      LIMIT 1
    ");
    
  }
  
}
