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
          lf.status   <> 'finished' AND
          lf.external  = '0'
        ) OR (
          lf.external       = '1' AND
          c.id              = lf.channelid AND
          c.isdeleted       = '0' AND
          c.starttimestamp <= NOW() AND
          c.endtimestamp   >= NOW()
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
    
    $authorizecode = $this->getAuthorizeSessionid( $info );
    
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
      'user_checkWatching'     => (bool)$info['member']['ispresencecheckforced'],
      'user_checkWatchingTimeInterval' => $info['checkwatchingtimeinterval'],
      'user_checkWatchingConfirmationTimeout' => $info['checkwatchingconfirmationtimeout'],
    );
    
    if ( $info['member'] and $info['member']['id'] ) {
      $flashdata['user_id'] = $info['member']['id'];
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
      $flashdata = $flashdata + $this->getPlaceholderFlashdata( $info );

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
    $versions = $recordingsModel->getVersions();
    if ( empty( $versions['master']['desktop'] ) )
      throw new \Exception("The placeholder does not have desktopcompatible recordings!");

    if ( $this->row['issecurestreamingforced'] ) {

      $data['livePlaceholder_servers'][] = $recordingsModel->getWowzaUrl(
        'secrtmpsurl', true, $info
      );
      $data['livePlaceholder_servers'][] = $recordingsModel->getWowzaUrl(
        'secrtmpurl',  true, $info
      );
      $data['livePlaceholder_servers'][] = $recordingsModel->getWowzaUrl(
        'secrtmpturl', true, $info
      );

    } else {

      $data['livePlaceholder_servers'][] = $recordingsModel->getWowzaUrl(
        'rtmpurl',  true, $info
      );
      $data['livePlaceholder_servers'][] = $recordingsModel->getWowzaUrl(
        'rtmpturl', true, $info
      );

    }

    $data['livePlaceholder_streams']      = array();
    $data['livePlaceholder_streamLabels'] = array();
    foreach( $versions['master']['desktop'] as $version ) {
      $data['livePlaceholder_streamLabels'] = array( $version['qualitytag'] );
      $data['livePlaceholder_streams'][]    = $recordingsModel->getMediaUrl(
        'default', $version, $info
      );
    }

    $data['intro_servers']      = $data['livePlaceholder_servers'];
    $data['intro_streams']      = $data['livePlaceholder_streams'];
    $data['intro_streamLabels'] = $data['livePlaceholder_streamLabels'];
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
  
  protected function getAuthorizeSessionid( &$info ) {

    if (
         !isset( $info['organization'] ) or
         !isset( $info['sessionid'] ) or
         !$info['sessionid']
       )
      return '';

    $ret = sprintf('?sessionid=%s_%s_%s',
      $info['organization']['id'],
      $info['sessionid'],
      $this->id
    );

    if ( isset( $info['member'] ) and $info['member']['id'] )
      $ret .= '&uid=' . $info['member']['id'];

    return $ret;

  }
  
  public function getMediaUrl( $type, $streamcode, $info ) {
    
    $url = $this->bootstrap->config['wowza'][ $type . 'url' ] . $streamcode;
    $sessionid = $info['sessionid'];
    if ( isset( $info['member'] ) )
      $user = $info['member'];
    else
      $user = null;

    switch( $type ) {
      
      case 'livehttp':
        //http://stream.videosquare.eu/devvsqlive/123456/playlist.m3u8
        $url .=
          '/playlist.m3u8' .
          $this->getAuthorizeSessionid( $info )
        ;
        
        break;
      
      case 'livertsp':
        //rtsp://stream.videosquare.eu/devvsqlive/123456
        $url .= $this->getAuthorizeSessionid( $info );
        
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
      
      case 'departmentsorgroups':
        
        if ( !isset( $user['id'] ) )
          return 'registrationrestricted';
        elseif ( $user['id'] == $this->row['userid'] )
          return true;
        elseif ( $user['iseditor'] and $user['organizationid'] == $this->row['organizationid'] )
          return true;
        
        $feedid = "'" . $this->row['id'] . "'";
        $userid = "'" . $user['id'] . "'";
        
        $hasaccess = $this->db->getOne("
          SELECT (
            SELECT COUNT(*)
            FROM
              access AS a,
              users_departments AS ud
            WHERE
              a.livefeedid    = $feedid AND
              ud.departmentid = a.departmentid AND
              ud.userid       = $userid
            LIMIT 1
          ) + (
            SELECT COUNT(*)
            FROM
              access AS a,
              groups_members AS gm
            WHERE
              a.livefeedid = $feedid AND
              gm.groupid   = a.groupid AND
              gm.userid    = $userid
            LIMIT 1
          ) AS count
        ");
        
        if ( !$hasaccess )
          return 'departmentorgrouprestricted';
        
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
      ORDER BY lc.id DESC
      LIMIT 0, 200
    ");

    $ret = array_reverse( $ret );
    return $ret;

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

  public function getMinStep( $startts, $endts ) {

    $startts = strtotime( $startts );
    $endts   = strtotime( $endts );
    $diff    = abs( $endts - $startts );

    if ( $diff < 1209600 ) // 2 het
      return 300;
    elseif ( $diff < 3024000 ) // 5 het
      return 3600;
    else
      return 86400;

  }

  public function getStatistics( $filter ) {

    $table = 'statistics_live_5min';
    $ret   = array(
      'step'           => 300, // 5perc
      'starttimestamp' => 0,
      'endtimestamp'   => 0,
      'data'           => array(),
      'originalstarttimestamp' => $filter['originalstarttimestamp'],
      'originalendtimestamp'   => $filter['originalendtimestamp'],
    );

    if ( isset( $filter['endtimestamp'] ) ) {
      $endts = min( strtotime( $filter['endtimestamp'] ), time() );
      $filter['endtimestamp'] = date('Y-m-d H:i', $endts );
    }

    if ( isset( $filter['starttimestamp'] ) and isset( $filter['endtimestamp'] ) ) {
      $minstep = $this->getMinStep( $filter['starttimestamp'], $filter['endtimestamp'] );
      if ( $filter['resolution'] < $minstep )
        $filter['resolution'] = $minstep;

      $ret['starttimestamp'] = strtotime( $filter['starttimestamp'] );
      $ret['endtimestamp']   = strtotime( $filter['endtimestamp'] );
      $diff = $ret['starttimestamp'] - $ret['endtimestamp'];

      if ( $filter['resolution'] == 300 ) {
        $table = 'statistics_live_5min';
        $ret['step'] = 300;

        // hozzaigazitani a datumot ha az adott intervallumon kivul esne
        if ( $diff > 1209600 ) { // 2 het
          $ret['starttimestamp'] = $ret['endtimestamp'] - 1209600;
          $filter['starttimestamp'] = date('Y-m-d H:i:s', $ret['starttimestamp'] );
        }

      } elseif ( $filter['resolution'] == 3600 ) {
        $table = 'statistics_live_hourly';
        $ret['step'] = 3600;

        if ( $diff > 3024000 ) { // 5 het
          $ret['starttimestamp'] = $ret['endtimestamp'] - 3024000;
          $filter['starttimestamp'] = date('Y-m-d H:i:s', $ret['starttimestamp'] );
        }

      } elseif ( $filter['resolution'] == 86400 ) {
        $table = 'statistics_live_daily';
        $ret['step'] = 86400;
      }

    }

    // fontos az adatok sorrendje! ha valtoztatasra kerul at kell irni a lov_hu-t
    $where = array();
    $sql   = "
      SELECT
        UNIX_TIMESTAMP(s.timestamp) AS timestamp,
        SUM( s.numberofflashwin )   +
        SUM( s.numberofflashmac )   +
        SUM( s.numberofflashlinux ) +
        SUM( s.numberofunknown )    AS numberofdesktop,
        SUM( s.numberofandroid )    AS numberofandroid,
        SUM( s.numberofiphone )     AS numberofiphone,
        SUM( s.numberofipad )       AS numberofipad
      FROM
        $table AS s,
        livefeed_streams AS ls
      WHERE
        s.livefeedstreamid = ls.id AND
        s.iscontent        = '0'
    ";

    if ( empty( $filter['livefeedids'] ) )
      $filter['livefeedids'] = array( $this->id );

    $where[] = "s.livefeedid IN('" . implode("', '", $filter['livefeedids'] ) . "')";

    if ( !empty( $filter['quality'] ) )
      $where[] = "ls.quality IN('" . implode("', '", $filter['quality'] ) . "')";

    if ( isset( $filter['starttimestamp'] ) )
      $where[] = "s.timestamp >= " . $this->db->qstr( $filter['starttimestamp'] );

    if ( isset( $filter['endtimestamp'] ) )
      $where[] = "s.timestamp <= " . $this->db->qstr( $filter['endtimestamp'] );

    if ( !empty( $where ) )
      $sql .= "AND " . implode(' AND ', $where );

    $sql .= "
      GROUP BY s.timestamp
      ORDER BY s.timestamp, s.id
    ";

    $ret['data'] = $this->db->getArray( $sql );
    if ( empty( $ret['data'] ) )
      return $ret;

    $item = reset( $ret['data'] );
    if ( !isset( $filter['starttimestamp'] ) )
      $ret['starttimestamp'] = $item['timestamp'];
    else {
      // how many "ticks" based on the step is there between the user-provided
      // starttimestamp and the actual timestamp, so we can align the ticks
      $steps = ceil(
        ( $item['timestamp'] + 1 - $ret['starttimestamp'] ) / $ret['step']
      );
      // now subtract those ticks from the start timestamp, so we can
      // achieve the range the user actually requested
      $ret['starttimestamp'] = $item['timestamp'] - ( $steps * $ret['step'] );
    }

    $item = end( $ret['data'] );
    if ( !isset( $filter['endtimestamp'] ) )
      $ret['endtimestamp'] = $item['timestamp'];
    else {
      // same thing, ensure that it ends on a "tick" boundary
      $steps = ceil(
        ( $ret['endtimestamp'] + 1 - $item['timestamp'] ) / $ret['step']
      );
      $ret['endtimestamp'] = $item['timestamp'] + ( $steps * $ret['step'] );
    }

    return $ret;

  }

}
