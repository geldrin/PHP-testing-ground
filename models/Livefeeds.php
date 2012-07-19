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
  
  protected function getFeedTypeWhere( $prefix = '', $onlymobile = null ) {
    
    $where = '';
    if ( $onlymobile !== null ) {
      
      if ( $onlymobile )
        $where = " AND {$prefix}feedtype = 'mobile' ";
      else
        $where = " AND {$prefix}feedtype <> 'mobile' ";
    }
    
    return $where;
    
  }
  
  public function getStreams( $onlymobile = null ) {
    
    $this->ensureID();
    $where = $this->getFeedTypeWhere( '', $onlymobile );
    return $this->db->getAssoc("
      SELECT
        id AS streamid,
        id,
        name,
        keycode,
        aspectratio,
        contentkeycode,
        contentaspectratio,
        feedtype,
        timestamp
      FROM livefeed_streams
      WHERE livefeedid = '" . $this-> id . "' $where
    ");
    
  }
  
  protected function getAuthorizeSessionid( $domain, $sessionid, $streamcode ) {
    
    if ( !$domain or !$sessionid )
      return '';
    
    return '?sessionid=' . $domain . '_' . $sessionid . '_' . $streamcode;
    
  }
  
  public function getMediaUrl( $type, $streamcode, $domain = null, $sessionid = null ) {
    
    switch( $type ) {
      
      case 'livehttp':
        //http://stream.videosquare.eu/devvsqlive/123456/playlist.m3u8
        $sprintfterm =
          '%s/playlist.m3u8' .
          $this->getAuthorizeSessionid( $domain, $sessionid, $streamcode )
        ;
        
        break;
      
      case 'livertsp':
        //rtsp://stream.videosquare.eu/devvsqlive/123456
        $sprintfterm =
          '%s' .
          $this->getAuthorizeSessionid( $domain, $sessionid, $streamcode )
        ;
        
        break;
      
    }
    
    $host = $this->bootstrap->config['wowza'][ $type . 'url' ];
    return $host . sprintf( $sprintfterm, $streamcode );
    
  }
  
}
