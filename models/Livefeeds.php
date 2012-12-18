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
  
  protected function getBrowserCompatibleWhere( $prefix = '', $browser ) {
    
    if ( !$browser or !$browser['mobile'] )
      return " AND {$prefix}isdesktopcompatible <> '0' ";
    
    if ( $browser['mobiledevice'] != 'android' and $browser['mobiledevice'] != 'iphone' )
      return '';
    
    if ( $browser['mobiledevice'] == 'android' )
      return " AND {$prefix}isandroidcompatible <> '0' ";
    elseif ( $browser['mobiledevice'] == 'iphone' )
      return " AND {$prefix}isioscompatible <> '0' ";
    
  }
  
  public function getStreams( $feedid = null ) {
    
    if ( !$feedid ) {
      
      $this->ensureID();
      $feedid = $this->id;
      
    }
    
    return $this->db->getAssoc("
      SELECT
        id AS streamid,
        id,
        name,
        status,
        keycode,
        contentkeycode,
        recordinglinkid,
        quality,
        isdesktopcompatible,
        isandroidcompatible,
        isioscompatible,
        timestamp
      FROM livefeed_streams
      WHERE livefeedid = '" . $feedid . "'
    ");
    
  }
  
  public function getStreamsForBrowser( $browser, $defaultstreamid = null ) {
    
    $streams         = $this->getStreams();
    $narrowedstreams = array();
    $defaultstream   = null;
    
    if (
         $browser['mobile'] and
         $browser['mobiledevice'] != 'iphone' and
         $browser['mobiledevice'] != 'android'
       )
      $unknown = true;
    else
      $unknown = false;
    
    foreach( $streams as $stream ) {
      
      if (
           ( !$browser['mobile'] and $stream['isdesktopcompatible'] ) or
           ( $browser['mobiledevice'] == 'iphone' and $stream['isioscompatible'] ) or
           ( $browser['mobiledevice'] == 'android' and $stream['isandroidcompatible'] ) or
           $unknown
         )
        $narrowedstreams[ $stream['id'] ] = $stream;
      
    }
    
    // nem talaltunk streamet ami raillik a browserre igy minden stream lehetseges, a default az elso lesz
    if ( empty( $narrowedstreams ) ) {
      
      foreach( $streams as $stream )
        $narrowedstreams[ $stream['id'] ] = $stream;
      
    }
    
    if ( !$defaultstreamid ) {
      
      $defaultstream = reset( $narrowedstreams );
      if ( $browser['mobile'] and $browser['tablet'] ) { // hq stream default ha tablet
        
        foreach( $narrowedstreams as $stream ) {
          
          if (
               (
                 ( $browser['mobiledevice'] == 'iphone' and $stream['isioscompatible'] ) or
                 ( $browser['mobiledevice'] == 'android' and $stream['isandroidcompatible'] )
               ) and $stream['quality']
             ) {
            
            $defaultstream = $stream;
            break;
            
          }
          
        }
        
      }
      
    } elseif ( $defaultstreamid and !isset( $narrowedstreams[ $defaultstreamid ] ) )
      return false;
    else
      $defaultstream = $narrowedstreams[ $defaultstreamid ];
    
    if ( // ha nem mobil vagy nem ismert mobil device, de a stream desktop kompat
         ( !$browser['mobile'] and $defaultstream['isdesktopcompatible'] ) or
         ( $defaultstream['isdesktopcompatible'] and $unknown )
       )
      $streamtype = 'desktop';
    elseif ( // ha mobil es android, vagy mobil es ismeretlen de a stream android kompat
            (
              $browser['mobile'] and
              $browser['mobiledevice'] == 'android' and
              $defaultstream['isandroidcompatible']
            ) or
            ( $defaultstream['isandroidcompatible'] and $unknown )
           )
      $streamtype = 'android';
    elseif ( // ha mobil es ios, vagy mobil es ismeretlen de a stream ios kompat
            (
              $browser['mobile'] and
              $browser['mobiledevice'] == 'iphone' and
              $defaultstream['isioscompatible']
            ) or
            ( $defaultstream['isioscompatible'] and $unknown )
           )
      $streamtype = 'ios';
    elseif ( $defaultstream['isdesktopcompatible'] ) // peldaul ha ismert mobile device de nem kompatibilis a stream akkor ez a fallback sorrend
      $streamtype = 'desktop';
    elseif ( $defaultstream['isandroidcompatible'] )
      $streamtype = 'android';
    elseif ( $defaultstream['isioscompatible'] )
      $streamtype = 'ios';
    else
      throw new \Exception(
        "Unhandled stream type: mobile device: " . $browser['mobiledevice'] .
        " defaultstream: " . var_export( $defaultstream, true )
      );
    
    return array(
      'streams'       => $narrowedstreams,
      'defaultstream' => $defaultstream,
      'streamtype'    => $streamtype,
    );
    
  }
  
  public function getFlashData( $info ) {
    
    $authorizecode = $this->getAuthorizeSessionidParam(
      $info['domain'],
      $info['sessionid']
    );
    
    $prefix    = $this->row['issecurestreamingforced']? 'sec': '';
    $flashdata = array(
      'language'               => \Springboard\Language::get(),
      'media_servers'          => array(
        $this->bootstrap->config['wowza'][ $prefix . 'liveingressurl'] . $authorizecode,
        $this->bootstrap->config['wowza'][ $prefix . 'liveurl'] . $authorizecode,
      ),
      'media_streams'          => array( $info['stream']['keycode'] ),
      'recording_title'        => $this->row['name'],
      'recording_type'         => 'live',
      'media_secondaryStreams' => array( $info['stream']['contentkeycode'] ),
    );
    
    $flashdata['media_secondaryServers'] = $flashdata['media_servers'];
    
    if ( !$this->row['slideonright'] )
      $flashdata['layout_videoOrientation'] = 'right';
    
    return $flashdata;
    
  }
  
  public function deleteStreams() {
    
    $this->ensureID();
    $this->db->execute("
      DELETE FROM livefeed_streams
      WHERE livefeedid = '" . $this->id . "'
    ");
    
  }
  
  public function getVCRReclinkID() {
    
    $this->ensureID();
    return $this->db->getOne("
      SELECT recordinglinkid
      FROM livefeed_streams
      WHERE livefeedid = '" . $this->id . "'
      LIMIT 1
    ");
    
  }
  
  public function createVCRStream( $recordinglinkid ) {
    
    $this->ensureID();
    $streamModel = $this->bootstrap->getModel('livefeed_streams');
    $streamModel->insert( array(
        'livefeedid'          => $this->id,
        'recordinglinkid'     => $recordinglinkid,
        'name'                => 'VCR stream',
        'status'              => 'ready',
        'quality'             => 1,
        'isdesktopcompatible' => 1,
        'timestamp'           => date('Y-m-d H:i:s'),
      )
    );
    
    return $streamModel->id;
    
  }
  
  public function modifyVCRStream( $recordinglinkid ) {
    
    $this->ensureID();
    $recordinglinkid = $this->db->qstr( $recordinglinkid );
    $this->db->execute("
      UPDATE livefeed_streams
      SET recordinglinkid = $recordinglinkid
      WHERE
        livefeedid = '" . $this->id . "' AND
        status IS NULL
      LIMIT 1
    ");
    
    return $this->db->Affected_Rows();
    
  }
  
  protected function getAuthorizeSessionid( $domain, $sessionid, $streamcode ) {
    
    if ( !$domain or !$sessionid )
      return '';
    
    return '?sessionid=' . $domain . '_' . $sessionid . '_' . $this->id;
    
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
  
  public function isAccessible( $user, $secure = null ) {
    
    $this->ensureObjectLoaded();
    
    if (
         isset( $user['id'] ) and
         (
           $this->row['userid'] == $user['id'] or
           (
             $user['iseditor'] and
             $user['organizationid'] == $this->row['organizationid']
           ) or
           (
             $user['isclientadmin'] and
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
  
  public function getAuthorizeSessionidParam( $domain, $sessionid ) {
    
    $user = $this->bootstrap->getSession('user');
    if ( isset( $user['id'] ) )
      return sprintf('?sessionid=%s_%s_%s&uid=%s',
        $domain,
        $sessionid,
        $this->id,
        $user['id']
      );
    else
      return sprintf('?sessionid=%s_%s_%s',
        $domain,
        $sessionid,
        $this->id
      );
    
  }
  
  public function canDeleteFeed( $feed = null, $streams = null ) {
    
    if ( !$feed ) {
      
      $this->ensureObjectLoaded();
      $feed = $this->row;
      
    }
    
    if ( $feed['feedtype'] != 'vcr' )
      return true;
    
    if ( !$streams )
      $streams = $this->getStreams( $feed['id'] );
    
    if ( count( $streams ) != 1 )
      throw new \Exception("VCR Helyszinhez tobb mint egy stream tartozik! " . var_export( $streams, true ) );
    
    $stream  = reset( $streams );
    
    if ( $stream['status'] and $stream['status'] != 'ready' )
      return false;
    else
      return true;
    
  }
  
}
