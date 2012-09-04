<?php
namespace Model;

class Livefeeds extends \Springboard\Model {
  
  public function delete( $id, $magic_quotes_gpc = 0 ) {
    
    $this->db->execute("
      DELETE FROM livefeed_streams
      WHERE livefeedid = " . $this->db->qstr( $id ) . "
    ");
    
    return parent::delete( $id, $magic_quotes_gpc );
    
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
  
  protected function getStreamTypeWhere( $prefix = '', $onlymobile = null ) {
    
    $where = '';
    if ( $onlymobile !== null ) {
      
      if ( $onlymobile )
        $where = " AND {$prefix}streamtype IN('mobile', 'normal/mobile') ";
      else
        $where = " AND {$prefix}streamtype IN('normal', 'normal/mobile') ";
      
    }
    
    return $where;
  
  }
  
  public function getStreams( $onlymobile = null ) {
    
    $this->ensureID();
    $where = $this->getStreamTypeWhere( '', $onlymobile );
    return $this->db->getAssoc("
      SELECT
        id AS streamid,
        id,
        name,
        status,
        keycode,
        aspectratio,
        contentkeycode,
        recordinglinkid,
        contentaspectratio,
        streamtype,
        timestamp
      FROM livefeed_streams
      WHERE livefeedid = '" . $this->id . "' $where
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
  
  public function isAccessible( $user ) {
    
    $this->ensureObjectLoaded();
    
    if (
         isset( $user['id'] ) and
         (
           $this->row['userid'] == $user['id'] or
           (
             $user['iseditor'] and
             $user['organizationid'] == $this->row['organizationid']
           )
         )
       )
      return true;
    
    switch( $this->row['accesstype'] ) {
      
      case 'public':
        break;
      
      case 'registrations':
        
        if ( !isset( $user['id'] ) )
          return 'registrationrestricted';
        
        break;
      
      case 'departments':
      case 'groups':
        
        if ( $this->row['accesstype'] == 'groups')
          $error = 'grouprestricted';
        else
          $error = 'departmentrestricted';
        
        if ( !isset( $user['id'] ) )
          return $error;
        elseif ( $user['id'] == $this->row['userid'] )
          return true;
        elseif ( $user['iseditor'] and $user['organizationid'] == $this->row['organizationid'] )
          return true;
        
        $feedid = "'" . $this->row['id'] . "'";
        $userid = "'" . $user['id'] . "'";
        
        if ( $this->row['accesstype'] == 'departments')
          $sql = "
            SELECT
              u.id
            FROM
              access AS a,
              users AS u
            WHERE
              a.livefeedid   = $feedid AND
              u.departmentid = a.departmentid AND
              u.id           = $userid
            LIMIT 1
          ";
        else
          $sql = "
            SELECT
              gm.userid
            FROM
              access AS a,
              groups_members AS gm
            WHERE
              a.livefeedid = $feedid AND
              gm.groupid   = a.groupid AND
              gm.userid    = $userid
            LIMIT 1
          ";
        
        $row = $this->db->getRow( $sql );
        
        if ( empty( $row ) )
          return $error;
        elseif ( $timefailed )
          return $error . '_timefailed';
        
        break;
      
      default:
        throw new \Exception('Unknown accesstype ' . $this->row['accesstype'] );
        break;
      
    }
    
    return true;
    
  }
  
  public function clearAccess() {
    
    $this->ensureID();
    
    $this->db->execute("
      DELETE FROM access
      WHERE livefeedid = '" . $this->id . "'
    ");
    
  }
  
  protected function insertMultipleIDs( $ids, $table, $field ) {
    
    $this->ensureID();
    
    $values = array();
    foreach( $ids as $id )
      $values[] = "('" . intval( $id ) . "', '" . $this->id . "')";
    
    $this->db->execute("
      INSERT INTO $table ($field, livefeedid)
      VALUES " . implode(', ', $values ) . "
    ");
    
  }
  
  public function restrictDepartments( $departmentids ) {
    $this->insertMultipleIDs( $departmentids, 'access', 'departmentid');
  }
  
  public function restrictGroups( $groupids ) {
    $this->insertMultipleIDs( $groupids, 'access', 'groupid');
  }
  
  public function cloneChannelAccess() {
    
    $this->ensureObjectLoaded();
    if ( !$this->row['channelid'] )
      throw new \Exception('Channelid is not set: ' . var_export( $this->row, true ) );
    
    $accessModel   = $this->bootstrap->getModel('access');
    $channelModel  = $this->bootstrap->getModel('channels');
    $rootchannelid = $channelModel->findRootID( $this->row['channelid'] );
    if ( !$rootchannelid )
      $rootchannelid = $this->row['channelid'];
    
    $accesses = $this->db->getArray("
      SELECT *
      FROM access
      WHERE channelid = '$rootchannelid'
    ");
    
    foreach( $accesses as $access ) {
      
      unset( $access['channelid'] );
      $access['livefeedid'] = $this->id;
      $accessModel->insert( $access );
      
    }
    
  }
  
  public function getChat( $excludemoderated = null ) {
    
    $this->ensureID();
    
    $where = array(
      "lc.livefeedid = '" . $this->id . "'",
      "lc.userid     = u.id",
    );
    
    if ( $excludemoderated !== null )
      $where[] = "lc.moderated <> '$excludemoderated'";
    
    return $this->db->getArray("
      SELECT
        lc.*,
        u.nickname,
        u.nameformat,
        u.nameprefix,
        u.namefirst,
        u.namelast
      FROM
        livefeed_chat AS lc,
        users AS u
      WHERE " . implode(' AND ', $where ) . "
      ORDER BY lc.id ASC
      LIMIT 0, 200
    ");
    
  }
  
}
