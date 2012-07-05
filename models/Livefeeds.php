<?php
namespace Model;

class Livefeeds extends \Springboard\Model {
  
  // --------------------------------------------------------------------------
  public function &delete( $id, $magic_quotes_gpc = 0 ) {
    
    // TODO mitortenik a recordingokkal?
    $this->db->execute("
      DELETE FROM recordings
      WHERE livefeedid = " . $this->db->qstr( $id ) . "
    ");
    
    $this->db->execute("
      DELETE FROM livefeed_streams
      WHERE livefeedid = " . $this->db->qstr( $id ) . "
    ");
    
    return parent::delete( $id, $magic_quotes_gpc );

  }
  
  public function getFeedsForChannel( $channelid ) {
    
    $streamObj = getObject('livefeed_streams');
    $this->clearFilter();
    $this->addFilter('channelid', $channelid );
    $feeds = $this->getArray();
    $ret   = array();
    
    foreach( $feeds as $key => $feed ) {
      
      $streamObj->clearFilter();
      $streamObj->addFilter('livefeedid', $feed['id'] );
      
      $feeds[ $key ]['streams'] = $streamObj->getArray();
      $ret[ $feed['id'] ] = $feeds[ $key ];
      
    }
    
    return $ret;
    
  }
  
  public function getFeedsFromChannelTree( $channeltree ) {
    
    $channelids = $this->getIdsFromTree( $channeltree );
    $channelids = array_unique( $channelids );
    $ret        = array();
    
    $results = $this->db->getArray("
      SELECT DISTINCT *
      FROM livefeeds
      WHERE channelid IN('" . implode("', '", $channelids ) . "')
    ");
    
    foreach( $results as $result )
      $ret[ $result['id'] ] = $result;
    
    return $ret;
    
  }
  
  protected function getIdsFromTree( $channeltree ) {
    
    $channelids = array();
    foreach( $channeltree as $channel ) {
      
      $channelids[] = $channel['id'];
      if ( !empty( $channel['children'] ) )
        $channelids = array_merge( $channelids, $this->getIdsFromTree( $channel['children'] ) );
      
    }
    
    return $channelids;
    
  }
  
  public function getAssocLivefeeds() {
    
    return $this->db->getAssoc("
      SELECT
        lf.channelid,
        lf.id,
        lf.nameoriginal,
        lf.nameenglish,
        lf.external,
        lf.status
      FROM
        livefeeds AS lf,
        channels AS c
      WHERE
        (
          lf.status IS NOT NULL AND
          lf.status <> 'finished' AND
          lf.external = '0'
        ) OR (
          lf.external = '1' AND
          c.id = lf.channelid AND
          c.starttimestamp <= NOW() AND
          c.endtimestamp >= NOW()
        )
    ");
    
  }
  
  public function getAssocUserLivefeeds( $userid ) {
    
    return $this->db->getAssoc("
      SELECT
        channelid,
        id,
        nameoriginal,
        nameenglish,
        external,
        status
      FROM
        livefeeds
      WHERE
        userid = " . $this->db->qstr( $userid ) . "
    ");
    
  }
  
  public function getStreams() {
    
    $this->ensureID();
    
    return $this->db->getAssoc("
      SELECT
        id AS streamid,
        id,
        name,
        streamurl,
        keycode,
        aspectratio,
        contentstreamurl,
        contentkeycode,
        contentaspectratio,
        feedtype,
        timestamp
      FROM livefeed_streams
      WHERE livefeedid = '" . $this-> id . "'
    ");
    
  }
  
  public function getStreamUrls( $streamid ) {
    
    $this->ensureObjectLoaded();
    
    $ret = array();
    $data = $this->db->getRow("
      SELECT
        id,
        streamurl,
        keycode,
        contentstreamurl,
        contentkeycode,
        feedtype
      FROM livefeed_streams
      WHERE
        livefeedid = '" . $this->id . "' AND
        id         = '" . $streamid . "'
    ");
    
    $ret['media_streams'] = array( $stream['streamurl'] . $stream['keycode'] );
    
    if ( $this->row['numberofstreams'] == 2 )
      $ret['media_secondaryStreams'] = array( $stream['contentstreamurl'] . $stream['contentkeycode'] );
    
    return $ret;
    
  }
  
  public function getFlashData( $streamid, $info, $sessionid ) {
    
    $this->ensureObjectLoaded();
    
    $recordingbaseuri = $info['BASE_URI'] . \Springboard\Language::get() . '/live/view/' . $this->id;
    $domain           = $info['organization']['domain'];
    
    $data = array(
      'language'              => \Springboard\Language::get(),
      'media_servers'         => array(
        $this->bootstrap->config['wowza']['liveurl'],
      ),
      'recording_title'       => '', // TODO
    );
    
    // default bal oldalon van a video, csak akkor allitsuk be ha kell
    if ( !$this->row['slideonright'] )
      $data['layout_videoOrientation'] = 'right';
    
    if ( $data['language'] != 'en' )
      $data['locale'] = $info['STATIC_URI'] . 'js/flash_locale_' . $data['language'] . '.json';
    
    $data = $data + $this->getStreamUrls();
    
    return $data;
    
  }
  
}
