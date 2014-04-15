<?php
namespace Model;

class Livefeeds extends \Springboard\Model {
  protected $streamingserver;
  
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
      $info['cookiedomain'],
      $info['sessionid'],
      $info['user']
    );
    
    $streams          = array();
    $streams[]        = $info['streams']['defaultstream']['keycode'];
    $contentstreams   = array();
    $contentstreams[] = $info['streams']['defaultstream']['contentkeycode'];
    
    foreach( $info['streams']['streams'] as $stream ) {
      
      if (
           $info['streams']['defaultstream']['id'] == $stream['id'] or
           $info['streams']['defaultstream']['quality'] == $stream['quality']
         )
        continue;
      
      $streams[]        = $stream['keycode'];
      $contentstreams[] = $stream['contentkeycode'];
      
    }

    if ( $this->bootstrap->config['forcesecureapiurl'] )
      $apiurl = 'https://' . $info['organization']['domain'] . '/';
    else
      $apiurl = $info['BASE_URI'];

    $apiurl   .=  \Springboard\Language::get() . '/jsonapi';
    $flashdata = array(
      'language'               => \Springboard\Language::get(),
      'api_url'                => $apiurl,
      'user_needPing'          => false,
      'media_streams'          => $streams,
      'feed_id'                => $this->id,
      'recording_title'        => $this->row['name'],
      'recording_type'         => 'live',
      'media_secondaryStreams' => $contentstreams,
      'timeline_autoPlay'      => true,
      'user_checkWatching'     => (bool)$info['user']['ispresencecheckforced'],
      'user_checkWatchingTimeInterval' => $info['checkwatchingtimeinterval'],
      'user_checkWatchingConfirmationTimeout' => $info['checkwatchingconfirmationtimeout'],
    );
    
    if ( $info['user'] and $info['user']['id'] ) {
      $flashdata['user_id'] = $info['user']['id'];
      $flashdata['user_needPing'] = true;
    }
    
    if ( $this->row['issecurestreamingforced'] )
      $flashdata['media_servers'] = array(
        rtrim( $this->bootstrap->config['wowza']['seclivertmpsurl'], '/' ) . $authorizecode,
        rtrim( $this->bootstrap->config['wowza']['seclivertmpeurl'], '/' ) . $authorizecode,
        rtrim( $this->bootstrap->config['wowza']['secliveurl'], '/' ) . $authorizecode,
      );
    else
      $flashdata['media_servers'] = array(
        rtrim( $this->bootstrap->config['wowza']['livertmpurl'], '/' ) . $authorizecode,
        rtrim( $this->bootstrap->config['wowza']['liveurl'], '/' ) . $authorizecode,
      );
    
    $streamingserverModel = $this->bootstrap->getModel('streamingservers');
    $streamingserver      = $streamingserverModel->getServerByClientIP(
      $info['ipaddress'],
      'live'
    );
    
    foreach( $flashdata['media_servers'] as $key => $url )
      $flashdata['media_servers'][ $key ] = sprintf( $url, $streamingserver );
    
    $flashdata['media_secondaryServers'] = $flashdata['media_servers'];
    
    if ( !$this->row['slideonright'] )
      $flashdata['layout_videoOrientation'] = 'right';
    
    if ( $this->row['introrecordingid'] )
      $flashdata = $flashdata + $this->getPlaceholderFlashdata(
        $info
      );

    return $flashdata;
    
  }
  
  public function getPlaceholderFlashdata( &$info ) {
    
    $this->ensureObjectLoaded();
    if ( !$this->row['introrecordingid'] )
      return array();

    $data = array(
      'livePlaceholder_servers' => array(),
    );
    $recordingsModel = $this->bootstrap->getModel('recordings');
    $recordingsModel->select( $this->row['introrecordingid'] );

    if ( $this->row['issecurestreamingforced'] ) {

      $data['livePlaceholder_servers'][] = $recordingsModel->getWowzaUrl(
        'secrtmpsurl', true, $info, $info['sessionid']
      );
      $data['livePlaceholder_servers'][] = $recordingsModel->getWowzaUrl(
        'secrtmpurl',  true, $info, $info['sessionid']
      );
      $data['livePlaceholder_servers'][] = $recordingsModel->getWowzaUrl(
        'secrtmpturl', true, $info, $info['sessionid']
      );

    } else {

      $data['livePlaceholder_servers'][] = $recordingsModel->getWowzaUrl(
        'rtmpurl',  true, $info, $info['sessionid']
      );
      $data['livePlaceholder_servers'][] = $recordingsModel->getWowzaUrl(
        'rtmpturl', true, $info, $info['sessionid']
      );

    }

    $data['livePlaceholder_streams'] = array(
      $recordingsModel->getMediaUrl(
        'default',
        false,
        $info['cookiedomain'],
        null,
        '',
        $this->row['introrecordingid']
      )
    );

    if ( $recordingsModel->row['videoreshq'] )
      $data['livePlaceholder_streams'][] =
        $recordingsModel->getMediaUrl(
          'default',
          true,
          $info['cookiedomain'],
          null,
          '',
          $this->row['introrecordingid']
        )
      ;

    $data['intro_servers'] = $data['livePlaceholder_servers'];
    $data['intro_streams'] = $data['livePlaceholder_streams'];
    return $data;

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
        'isioscompatible'     => 1,
        'isandroidcompatible' => 1,
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
  
  protected function getAuthorizeSessionid( $cookiedomain, $sessionid, $streamcode ) {
    
    if ( !$cookiedomain or !$sessionid )
      return '';
    
    return '?sessionid=' . $cookiedomain . '_' . $sessionid . '_' . $this->id;
    
  }
  
  public function getMediaUrl( $type, $streamcode, $info, $sessionid = null ) {
    
    $url = $this->bootstrap->config['wowza'][ $type . 'url' ] . $streamcode;

    switch( $type ) {
      
      case 'livehttp':
        //http://stream.videosquare.eu/devvsqlive/123456/playlist.m3u8
        $url .=
          '/playlist.m3u8' .
          $this->getAuthorizeSessionid(
            $info['cookiedomain'], $sessionid, $streamcode
          )
        ;
        
        break;
      
      case 'livertsp':
        //rtsp://stream.videosquare.eu/devvsqlive/123456
        $url .= $this->getAuthorizeSessionid(
          $info['cookiedomain'], $sessionid, $streamcode
        );
        
        break;
      
    }
    
    if ( !$this->streamingserver ) {
      
      $streamingserverModel  = $this->bootstrap->getModel('streamingservers');
      $this->streamingserver = $streamingserverModel->getServerByClientIP(
        $info['ipaddress'],
        'live'
      );
      
    }
    
    return sprintf( $url, $this->streamingserver );
    
  }
  
  public function isAccessibleByInvitation( $user, $organization ) {

    if ( !$user['id'] )
      return false;

    $this->ensureID();
    return (bool)$this->db->getOne("
      SELECT COUNT(*)
      FROM users_invitations
      WHERE
        registereduserid = '" . $user['id'] . "' AND
        livefeedid       = '" . $this->id . "' AND
        status           <> 'deleted' AND
        organizationid   = '" . $organization['id'] . "'
      LIMIT 1
    ");

  }

  public function isAccessible( $user, $organization, $secure = null ) {
    
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
    
    if ( $this->isAccessibleByInvitation( $user, $organization ) )
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
              ud.id
            FROM
              access AS a,
              users_departments AS ud
            WHERE
              a.livefeedid    = $feedid AND
              ud.departmentid = a.departmentid AND
              ud.userid       = $userid
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
  
  public function getChat() {
    
    $this->ensureID();
    
    $ret = $this->db->getArray("
      SELECT
        lc.*,
        SUBSTRING_INDEX(lc.anonymoususer, '_', 1) AS anonuserid,
        u.nickname,
        u.nameformat,
        u.nameprefix,
        u.namefirst,
        u.namelast
      FROM livefeed_chat AS lc
      LEFT JOIN users AS u ON(
        lc.userid = u.id
      )
      WHERE lc.livefeedid = '" . $this->id . "'
      ORDER BY lc.id ASC
      LIMIT 0, 200
    ");

    return $ret;

  }
  
  public function getAuthorizeSessionidParam( $cookiedomain, $sessionid, $user = null ) {
    
    return sprintf('?sessionid=%s_%s_%s',
      $cookiedomain,
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
  
  public function getAnonUserID() {
    return $this->bootstrap->getRedis()->incr( $this->getAnonUserIDKey() );
  }

  public function refreshAnonUserID() {
    return $this->bootstrap->getRedis()->persist( $this->getAnonUserIDKey() );
  }

  private function getAnonUserIDKey() {
    // a cookiedomain organization fuggo, igy az anonymuserid is org fuggo
    return $this->bootstrap->config['cookiedomain'] . ':anonymoususerid';
  }

  public function search( $term, $userid, $organizationid ) {
    
    $searchterm  = str_replace( ' ', '%', $term );
    $searchterm  = $this->db->qstr( '%' . $searchterm . '%' );
    $term        = $this->db->qstr( $term );

    $query   = "
      SELECT
        (
          1 +
          IF( l.name = $term, 2, 0 )
        ) AS relevancy,
        l.id,
        l.userid,
        l.organizationid,
        l.name,
        c.ordinalnumber,
        c.starttimestamp,
        c.endtimestamp
      FROM
        livefeeds AS l LEFT JOIN channels AS c ON(
          l.channelid = c.id
        )
      WHERE
        l.name LIKE $searchterm AND
        (
          l.organizationid = '$organizationid' OR
          (
            l.userid         = '$userid' AND
            l.organizationid = '$organizationid'
          )
        )
      ORDER BY relevancy DESC
      LIMIT 20
    ";
    
    return $this->db->getArray( $query );
    
  }
  
}
