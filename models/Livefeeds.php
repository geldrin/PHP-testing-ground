<?php
namespace Model;

class Livefeeds extends \Springboard\Model {
  protected $streamingserver;

  private static $hdsFeatures = array(
    'features_live_hds',
    'features_live_hdss',
  );
  private static $hlsFeatures = array(
    'features_live_hlss',
    'features_live_hls',
  );

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
      WHERE
        channelid IN('" . implode("', '", $channelids ) . "') AND
        (status IS NULL OR status <> 'markedfordeletion')
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
          lf.status NOT IN('finished', 'markedfordeletion') AND
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
      FROM livefeeds
      WHERE
        userid = " . $this->db->qstr( $userid ) . " AND
        (status IS NULL OR status <> 'markedfordeletion')
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
        id AS ix,
        id,
        status,
        keycode,
        contentkeycode,
        recordinglinkid,
        qualitytag,
        isdesktopcompatible,
        isandroidcompatible,
        isioscompatible,
        timestamp,
        weight
      FROM livefeed_streams
      WHERE
        livefeedid = '" . $feedid . "' AND
        (status IS NULL OR status <> 'markedfordeletion')
      ORDER BY weight, id
    ");

  }

  public function getStreamsForBrowser( $browser, $defaultKeycode = null ) {

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

    if ( !$defaultKeycode ) {

      $defaultstream = reset( $narrowedstreams );
      if ( $browser['mobile'] and $browser['tablet'] ) {

        foreach( $narrowedstreams as $stream ) {

          if (
               (
                 ( $browser['mobiledevice'] == 'iphone' and $stream['isioscompatible'] ) or
                 ( $browser['mobiledevice'] == 'android' and $stream['isandroidcompatible'] )
               )
             ) {

            $defaultstream = $stream;
            break;

          }

        }

      }

    } elseif ( $defaultKeycode and !isset( $narrowedstreams[ $defaultKeycode ] ) )
      return false;
    else
      $defaultstream = $narrowedstreams[ $defaultKeycode ];

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

  private function getStreamInfo( $info, $prefix = '' ) {

    $ret = array(
      'streams'    => array(),
      'labels'     => array(
        $info['streams']['defaultstream']['qualitytag'],
      ),
      'parameters' => array(
        array(
          'livefeedstreamid' => $info['streams']['defaultstream']['id'],
          'viewsessionid'    => $this->generateViewSessionid(
            $info['streams']['defaultstream']['id']
          ),
        ),
      ),
    );

    foreach( $info['streams']['streams'] as $stream ) {

      if (
           $info['streams']['defaultstream']['id'] == $stream['id']
         )
        continue;

      $ret['labels'][]     = $stream['qualitytag'];
      $ret['parameters'][] = array(
        'livefeedstreamid' => $stream['id'],
        'viewsessionid'    => $this->generateViewSessionid( $stream['id'] ),
      );

    }

    if ( $this->isHDSEnabled( $prefix, $info ) ) {

      $authorizecode = $this->getAuthorizeSessionid( $info );
      $smilurl       = 'smil:%s.smil/manifest.f4m%s';
      $filename      = $this->id;

      if ( $prefix )
        $filename .= '_' . $prefix;

      $ret['streams'][] = sprintf( $smilurl, $filename, $authorizecode );

    } else {

      if ( isset( $info['streams']['defaultstream'][ $prefix . 'keycode'] ) )
        $ret['streams'][] = $info['streams']['defaultstream'][ $prefix . 'keycode'];

      foreach( $info['streams']['streams'] as $stream ) {

        if (
             $info['streams']['defaultstream']['id'] == $stream['id']
           )
          continue;

        $ret['streams'][] = $stream[ $prefix . 'keycode'];

      }

    }

    return $ret;

  }

  public function getFlashData( $info ) {

    if ( $this->bootstrap->config['forcesecureapiurl'] )
      $apiurl = 'https://' . $info['organization']['domain'] . '/';
    else
      $apiurl = $info['BASE_URI'];

    $apiurl   .=  'jsonapi';
    $flashdata = array(
      'language'               => \Springboard\Language::get(),
      'api_url'                => $apiurl,
      'user_needPing'          => false,
      'feed_id'                => $this->id,
      'recording_title'        => $this->row['name'],
      'recording_type'         => 'live',
      'recording_autoQuality'  => false, // nincs stream resolution adat; off
      'timeline_autoPlay'      => true,
      'user_checkWatching'     => (bool)$info['member']['ispresencecheckforced'],
      'user_checkWatchingTimeInterval' => $info['checkwatchingtimeinterval'],
      'user_checkWatchingConfirmationTimeout' => $info['checkwatchingconfirmationtimeout'],
    );

    $flashdata = $flashdata + $this->bootstrap->config['flashplayer_extraconfig'];

    $streaminfo = $this->getStreamInfo( $info );
    $flashdata['media_streams']          = $streaminfo['streams'];
    $flashdata['media_streamLabels']     = $streaminfo['labels'];
    $flashdata['media_streamParameters'] = $streaminfo['parameters'];

    $streaminfo = $this->getStreamInfo( $info, 'content');
    $flashdata['media_secondaryStreams'] = $streaminfo['streams'];
    $flashdata['content_streamLabels'] = $streaminfo['labels'];

    if ( $info['member'] and $info['member']['id'] ) {
      $flashdata['user_id']          = $info['member']['id'];
      $flashdata['user_needPing']    = true;
      $flashdata['user_pingSeconds'] = $this->bootstrap->config['sessionpingseconds'];
    }

    $flashdata = $flashdata + $this->getMediaServers( $info );

    if ( !$this->row['slideonright'] )
      $flashdata['layout_videoOrientation'] = 'right';

    if ( $this->row['introrecordingid'] )
      $flashdata = $flashdata + $this->getPlaceholderFlashdata( $info );

    return $flashdata;

  }

  public function isHDSEnabled( $prefix = '', $info ) {
    return
      $info['organization']['livehdsenabled'] and
      in_array( $this->row[ $prefix . 'smilstatus'], array('onstorage', 'regenerate') )
    ;
  }

  public function getMediaServers( $info, $hds = null ) {

    $this->ensureObjectLoaded();
    $data = array(
      'media_servers' => array(),
      'media_secondaryServers' => array(),
    );

    $authorizecode = $this->getAuthorizeSessionid( $info );
    $prefix        = $this->row['issecurestreamingforced']? 'sec': '';
    if ( $hds === null )
      $hds = $this->isHDSEnabled( '', $info );

    $prefix = $this->row['issecurestreamingforced']? 'sec': '';
    if ( $hds ) {
      $data['media_servers'][] =
        $this->bootstrap->config['wowza'][ $prefix . 'livesmilurl' ]
      ;
    } else {

      if ( $this->row['issecurestreamingforced'] )
        $data['media_servers'] = array(
          rtrim( $this->bootstrap->config['wowza']['seclivertmpsurl'], '/' ) . $authorizecode,
          rtrim( $this->bootstrap->config['wowza']['seclivertmpeurl'], '/' ) . $authorizecode,
          rtrim( $this->bootstrap->config['wowza']['secliveurl'], '/' ) . $authorizecode,
        );
      else
        $data['media_servers'] = array(
          rtrim( $this->bootstrap->config['wowza']['livertmpurl'], '/' ) . $authorizecode,
          rtrim( $this->bootstrap->config['wowza']['liveurl'], '/' ) . $authorizecode,
        );

    }

    if ( !$this->streamingserver ) {
      $streamingserverModel  = $this->bootstrap->getModel('streamingservers');
      $this->streamingserver = $streamingserverModel->getServerByClientIP(
        $info['ipaddress'],
        'live'
      );
    }

    $streamingserver = $this->streamingserver;
    if ( empty( $streamingserver ) )
      throw new \Exception("No streaming server found, not even the default");

    if ( $streamingserver['type'] == 'wowza' )
      $data['media_serverType'] = 0;
    else if ( $streamingserver['type'] == 'nginx' )
      $data['media_serverType'] = 1;
    else
      throw new \Exception(
        "Unhandled streaming server type: " .
        var_export( $streamingserver['type'], true )
      );

    foreach( $data['media_servers'] as $key => $url )
      $data['media_servers'][ $key ] = sprintf( $url, $streamingserver['server'] );

    $contenthds = $this->isHDSEnabled('content', $info );
    if ( $hds == $contenthds ) {

      $data['media_secondaryServers'] = $data['media_servers'];
      return $data;

    } elseif ( $contenthds )
      $data['media_secondaryServers'][] =
        rtrim( $this->bootstrap->config['wowza'][ $prefix . 'livesmilurl' ], '/' ) . $authorizecode
      ;
    else {

      if ( $this->row['issecurestreamingforced'] )
        $data['media_secondaryServers'] = array(
          rtrim( $this->bootstrap->config['wowza']['seclivertmpsurl'], '/' ) . $authorizecode,
          rtrim( $this->bootstrap->config['wowza']['seclivertmpeurl'], '/' ) . $authorizecode,
          rtrim( $this->bootstrap->config['wowza']['secliveurl'], '/' ) . $authorizecode,
        );
      else
        $data['media_secondaryServers'] = array(
          rtrim( $this->bootstrap->config['wowza']['livertmpurl'], '/' ) . $authorizecode,
          rtrim( $this->bootstrap->config['wowza']['liveurl'], '/' ) . $authorizecode,
        );

    }

    foreach( $data['media_secondaryServers'] as $key => $url )
      $data['media_secondaryServers'][ $key ] = sprintf( $url, $streamingserver['server'] );

    return $data;

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

    $recordingsModel->row['issecurestreamingforced'] = $this->row['issecurestreamingforced'];
    $server = $recordingsModel->getMediaServers(
      $info, $this->isHDSEnabled( '', $info )
    );
    $data['livePlaceholder_servers'] = $server['media_servers'];
    unset( $server );

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
      WHERE
        livefeedid = '" . $this->id . "' AND
        (status IS NULL OR status <> 'markedfordeletion')
      LIMIT 1
    ");

  }

  public function createVCRStream( $recordinglinkid ) {

    $this->ensureID();
    $streamModel = $this->bootstrap->getModel('livefeed_streams');
    $streamModel->insert( array(
        'livefeedid'          => $this->id,
        'recordinglinkid'     => $recordinglinkid,
        'qualitytag'          => 'VCR stream',
        'status'              => 'ready',
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

    return sprintf( $url, $this->streamingserver['server'] );

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

  public function getAllChat() {
    $this->ensureID();
    return $this->db->query("
      SELECT
        lc.*,
        SUBSTRING_INDEX(lc.anonymoususer, '_', 1) AS anonuserid,
        u.externalid,
        u.email,
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
    ");
  }

  public function getChat() {

    $this->ensureID();

    $ret = $this->db->getArray("
      SELECT
        lc.*,
        SUBSTRING_INDEX(lc.anonymoususer, '_', 1) AS anonuserid,
        u.externalid,
        u.email,
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
        l.indexphotofilename,
        c.title AS channeltitle,
        c.subtitle AS channelsubtitle,
        c.ordinalnumber,
        c.starttimestamp,
        c.endtimestamp
      FROM
        livefeeds AS l LEFT JOIN channels AS c ON(
          l.channelid = c.id
        )
      WHERE
        (l.status IS NULL OR l.status <> 'markedfordeletion') AND
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
    $organizationid = $filter['organizationid'];
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
        livefeed_streams AS ls,
        livefeeds AS lf
      WHERE
        lf.id              = ls.livefeedid AND
        lf.organizationid  = '$organizationid' AND
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
        ( $item['timestamp'] - $ret['starttimestamp'] ) / $ret['step']
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

  public function generateViewSessionid( $extra ) {
    $this->ensureObjectLoaded();
    $ts        = microtime(true);
    $user      = $this->bootstrap->getSession('user');
    $sessionid = session_id();

    return md5( $ts . $sessionid . $this->id . $extra );
  }

  public function incrementViewCounters() {
    $this->ensureID();

    $this->db->execute("
      UPDATE livefeeds
      SET
        numberofviews          = numberofviews + 1,
        numberofviewsthisweek  = numberofviewsthisweek + 1,
        numberofviewsthismonth = numberofviewsthismonth + 1
      WHERE id = '" . $this->id . "'
      LIMIT 1
    ");

    // nem pontos, de nem szamit, csak kiiras miatt fontos hogy valtozzon
    if ( $this->row['numberofviews'] and isset( $this->row['numberofviews'] ) )
      $this->row['numberofviews']++;

    return (bool)$this->db->Affected_Rows();

  }

  public function resetViewCounters( $type ) {
    $this->ensureID();

    if ( $type != 'week' and $type != 'month' )
      throw new \Exception('Invalid type passed, expecting "week" or "month"');

    $this->db->execute("
      UPDATE livefeeds
      SET numberofviewsthis" . $type . " = 0
      WHERE id = '" . $this->id . "'
      LIMIT 1
    ");

  }

  public function markAsDeleted() {

    $this->ensureID();
    $this->db->execute("
      UPDATE livefeeds
      SET status = 'markedfordeletion'
      WHERE id = '" . $this->id . "'
      LIMIT 1
    ");
    $this->db->execute("
      UPDATE livefeed_streams
      SET status = 'markedfordeletion'
      WHERE livefeedid = '" . $this->id . "'
    ");

  }

  public function getViewers() {
    $this->ensureID();
    if ( $this->row and isset( $this->row['currentviewers'] ) )
      return $this->row['currentviewers'];

    return $this->db->getOne("
      SELECT currentviewers
      FROM livefeeds
      WHERE id = '" . $this->id . "'
      LIMIT 1
    ");

  }

  public function getStreamingServers( $info ) {
    $where = array();
    if ( $info['organization']['livehdsenabled'] ) {
      $sql = array();
      foreach( self::$hdsFeatures as $field )
        $sql[] = "$field = '1'";

      $where[] = "(" . implode(" OR ", $sql ) . ")";
    }

    if ( $info['organization']['livehlsenabledandroid'] ) {
      $sql = array();
      foreach( self::$hlsFeatures as $field )
        $sql[] = "$field = '1'";

      $where[] = "(" . implode(" OR ", $sql ) . ")";
    }

    if ( !empty( $where ) )
      $where = " AND " . implode(" AND ", $where );
    else
      $where = "";

    return $this->db->getArray("
      SELECT *
      FROM cdn_streaming_servers
      WHERE
        disabled     = '0' AND
        serverstatus = 'ok' AND
        servicetype IN('live', 'live|ondemand')
        $where
      ORDER BY location, shortname
    ");
  }

  public function forceMediaServer( $id ) {
    return $this->streamingserver = $this->db->getRow("
      SELECT *
      FROM cdn_streaming_servers
      WHERE id = '$id'
      LIMIT 1
    ");
  }

  public function searchStatistics( $user, $term, $organizationid, $start, $limit ) {

    $searchterm = str_replace( ' ', '%', $term );
    $searchterm = $this->db->qstr( '%' . $searchterm . '%' );
    $term       = $this->db->qstr( $term );
    $lang       = \Springboard\Language::get();
    $userid     = $user['id'];
    $query      = "
      SELECT
        (
          1 +
          IF( l.name = $term, 2, 0 )
        ) AS relevancy,
        l.id,
        l.userid,
        l.organizationid,
        l.name,
        c.title AS channeltitle,
        c.subtitle AS channelsubtitle,
        c.ordinalnumber,
        c.starttimestamp,
        c.endtimestamp,
        c.indexphotofilename,
        s.value AS channeltype
      FROM
        livefeeds AS l LEFT JOIN channels AS c ON(
          l.channelid = c.id
        )
        LEFT JOIN channel_types AS ct ON(
          ct.id = c.channeltypeid
        )
        LEFT JOIN strings AS s ON(
          s.translationof = ct.name_stringid AND
          s.language      = '$lang'
        )
      WHERE
        (l.status IS NULL OR l.status <> 'markedfordeletion') AND
        (
          l.name LIKE $searchterm OR
          c.title LIKE $searchterm OR
          c.subtitle LIKE $searchterm
        ) AND
        (
          l.organizationid = '$organizationid' OR
          (
            l.userid         = '$userid' AND
            l.organizationid = '$organizationid'
          )
        )
      ORDER BY relevancy DESC, c.starttimestamp DESC
      LIMIT $start, $limit
    ";

    return $this->db->getArray( $query );

  }

  public function getStatisticsData( $info ) {
    $organizationid = $info['organizationid'];
    $startts = $this->db->qstr( $info['datefrom'] );
    $endts   = $this->db->qstr( $info['dateuntil'] );
    $tables  = '';
    $where   = array(
      "vsl.timestampfrom >= $startts",
      "vsl.timestampuntil <= $endts",
      "lf.organizationid = '$organizationid'",
    );

    $extraselect = '';
    if ( $info['extrainfo'] )
      $extraselect = "
        vsl.ipaddress AS sessionipaddress,
        vsl.useragent AS sessionuseragent,
      ";

    if ( !empty( $info['livefeedids'] ) )
      $where[] = "vsl.livefeedid IN('" . implode("', '", $info['livefeedids'] ) . "')";

    if ( !empty( $info['groupids'] ) ) {
      $tables .= ", groups_members AS gm";
      $where[] = "gm.groupid IN('" . implode("', '", $info['groupids'] ) . "')";
      $where[] = "gm.userid = u.id";
    }

    if ( !empty( $info['userids'] ) )
      $where[] = "vsl.vsquserid IN('" . implode("', '", $info['userids'] ) . "')";

    $where = implode(" AND\n  ", $where );
    return $this->db->query("
      SELECT
        u.id AS userid,
        u.email,
        u.externalid,
        c.id AS channelid,
        c.title,
        c.starttimestamp,
        c.endtimestamp,
        $extraselect
        vsl.viewsessionid,
        vsl.startaction,
        vsl.stopaction,
        vsl.timestampfrom AS timestamp,
        vsl.timestampfrom AS watchstarttimestamp,
        vsl.timestampuntil AS watchendtimestamp,
        TIME_TO_SEC( TIMEDIFF(vsl.timestampuntil, vsl.timestampfrom) ) AS watchduration
      FROM
        view_statistics_live AS vsl
        LEFT JOIN users AS u ON(
          u.id = vsl.userid
        ),
        channels AS c,
        livefeeds AS lf
        $tables
      WHERE
        vsl.timestampuntil IS NOT NULL AND
        lf.id = vsl.livefeedid AND
        c.id = lf.channelid AND
        $where
      ORDER BY vsl.id DESC
    ");
  }

  public function getIngressURL() {
    $this->ensureObjectLoaded();
    if ( $this->row['issecurestreamingforced'] )
      return $this->bootstrap->config['wowza']['secliveingressurl3'];
    else
      return $this->bootstrap->config['wowza']['liveingressurl'];
  }

  public function handleStreamTemplate( $groupid ) {
    $this->ensureObjectLoaded();
    $streamModel = $this->bootstrap->getModel('livefeed_streams');

    $profiles = $this->db->getArray("
      SELECT
        lsp.*,
        lspg.weight
      FROM livestream_profiles_groups AS lspg
      LEFT JOIN livestream_profiles AS lsp ON(
        lspg.livestreamprofileid = lsp.id
      )
      WHERE
        lsp.disabled           = '0' AND
        lspg.livestreamgroupid = '$groupid'
      ORDER BY lspg.weight
    ");

    $streamid = null;
    $contentstreamid = null;
    foreach( $profiles as $profile ) {
      $row = array(
        'livefeedid'          => $this->id,
        'qualitytag'          => $profile['qualitytag'],
        'isdesktopcompatible' => $profile['isdesktopcompatible'],
        'isandroidcompatible' => $profile['isandroidcompatible'],
        'isioscompatible'     => $profile['isioscompatible'],
        'weight'              => $profile['weight'],
        'timestamp'           => date('Y-m-d H:i:s'),
      );

      if ( $profile['type'] == 'groupdynamic' ) {
        if ( !$streamid )
          $streamid = $streamModel->generateUniqueKeycode( null, $profile['streamidlength'] );

        if ( $profile['iscontentenabled'] and !$contentstreamid )
          $contentstreamid = $streamModel->generateUniqueKeycode( null, $profile['contentstreamidlength'] );

        $row['keycode' ] = $streamid;
        if ( $profile['iscontentenabled'] )
          $row['contentkeycode' ] = $contentstreamid;
      }

      $prefixes = array('');
      if ( $profile['iscontentenabled'] )
        $prefixes[] = 'content';

      foreach( $prefixes as $prefix ) {
        switch( $profile['type'] ) {
          case 'static':
            $row[ $prefix . 'keycode' ] = $profile[ $prefix . 'streamid' ];
            break;
          case 'dynamic':
            $row[ $prefix . 'keycode' ] = $streamModel->generateUniqueKeycode(
              null, $profile[ $prefix . 'streamidlength']
            );
            break;
        }

        // itt kapja meg a groupdynamic is a suffixot
        $row[ $prefix . 'keycode' ] .= $profile[ $prefix . 'streamsuffix' ];
      }

      $streamModel->insertBatchCollect( $row );
    }

    $streamModel->flushBatchCollect();
  }

  public function getFeatured( $organizationid, $language ) {
    return $this->db->getRow("
      SELECT
        c.id AS channelid,
        c.title,
        c.subtitle,
        c.ordinalnumber,
        c.url,
        c.indexphotofilename AS channelindexphotofilename,
        '' AS location,
        c.starttimestamp,
        c.endtimestamp,
        s.value AS channeltype,
        lf.id AS livefeedid,
        lf.name AS feedname,
        lf.indexphotofilename AS feedindexphotofilename
      FROM livefeeds AS lf
      LEFT JOIN channels AS c ON(
        c.id = lf.channelid
      )
      LEFT JOIN channel_types AS ct ON(
        ct.id = c.channeltypeid
      )
      LEFT JOIN strings AS s ON(
        s.translationof = ct.name_stringid AND
        s.language = '$language'
      )
      WHERE
        lf.isfeatured     = '1' AND
        lf.organizationid = '$organizationid'
      ORDER BY lf.id DESC
      LIMIT 1
    ");
  }
}
