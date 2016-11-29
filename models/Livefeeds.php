<?php
namespace Model;

class Livefeeds extends \Springboard\Model {
  private $transcoderCache = array();

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
        livefeedid = '$feedid' AND
        (status IS NULL OR status <> 'markedfordeletion')
      ORDER BY weight, id
    ");
  }

  public function deleteStreams() {

    $this->ensureID();
    $this->db->execute("
      DELETE FROM livefeed_streams
      WHERE livefeedid = '" . $this->id . "'
    ");

  }

  public function getVCRReclinkID() {
    $this->ensureObjectLoaded();
    if ( $this->row['status'] === 'markedfordeletion' )
      return null;

    return $this->row['recordinglinkid'];
  }

  public function createVCRStream( $recordinglinkid ) {

    $this->ensureID();
    $link = $this->db->getRow("
      SELECT *
      FROM recording_links
      WHERE id = '$recordinglinkid'
      LIMIT 1
    ");

    if ( !$link )
      throw new \Exception("recording_links row with id $recordinglinkid not found for feed #" . $this->id );

    if ( !$link['livestreamgroupid'] )
      throw new \Exception("recording_links row with id $recordinglinkid does not contain a valid livestreamgroupid for feed #" . $this->id );

    $this->handleStreamTemplate( $link['livestreamgroupid'], $recordinglinkid );
  }

  public function modifyVCRStream( $recordinglinkid ) {
    throw new \Exception("this functionality is now invalid");
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

  public function isAccessible( $user, $organization, $secure = null, $token = null ) {

    $this->ensureObjectLoaded();

    if (
         $this->row['userid'] == $user['id'] or
         \Model\Userroles::userHasPrivilege(
           $user,
           'general_ignoreAccessRestrictions',
           'or',
           'isclientadmin', 'iseditor', 'isadmin'
         )
       )
      return true;

    if ( $this->isAccessibleByInvitation( $user, $organization ) )
      return true;

    /*
      megnezzuk hogy a token valid e, ha a tokennek nincs ertelme eppen akkor
      null-t ad vissza, ha van ertelme akkor vagy true-t vagy 'tokeninvalid'-ot
    */
    $bytoken = \TokenAuth\TokenAuth::tokenAccessCheck(
      $token, $organization, $this->row
    );
    if ( $bytoken !== null )
      return $bytoken;

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
        elseif (
                 \Model\Userroles::userHasPrivilege(
                   $user,
                   'general_accessDepartmentOrGroupObjects',
                   'iseditor'
                 ) and
                 $user['organizationid'] == $this->row['organizationid']
               )
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

  public function canDeleteFeed( $feed = null ) {

    if ( !$feed ) {

      $this->ensureObjectLoaded();
      $feed = $this->row;

    }

    if ( $feed['feedtype'] != 'vcr' )
      return true;

    if ( $feed['status'] and $feed['status'] != 'ready' )
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
    if ( $this->row['transcoderid'] ) {

      $trid = $this->row['transcoderid'];
      if ( isset( $this->transcoderCache[ $trid ] ) )
        return $this->transcoderCache[ $trid ];

      $url = $this->db->getOne("
        SELECT ingressurl
        FROM livestream_transcoders
        WHERE id = '$trid'
        LIMIT 1
      ");

      // biztosra megyunk hogy van a vegen per
      $url = rtrim( $url, '/' );
      $url .= '/';
      return $this->transcoderCache[ $trid ] = $url;
    }

    if ( $this->row['issecurestreamingforced'] )
      return $this->bootstrap->config['wowza']['secliveingressurl3'];
    else
      return $this->bootstrap->config['wowza']['liveingressurl'];
  }

  public function getAllIngressURLs( $streams ) {
    $ingressurl = $this->getIngressURL();
    $ret = array();

    foreach( $streams as $stream ) {
      if ( !isset( $ret['video'] ) and $stream['isdesktopcompatible'] ) {
        // video: ingressurl + keycode _ előtti része
        $pos = strpos( $stream['keycode'], '_' );
        if ( $pos === false )
          $keycode = $stream['keycode'];
        else
          $keycode = substr( $stream['keycode'], 0, $pos );

        $ret['video'] = $ingressurl . $keycode;
      }

      if ( !isset( $ret['presentation'] ) and $stream['isdesktopcompatible'] ) {
        // prezi: ingressurl + contentkeycode _ előtti része
        strpos( $stream['contentkeycode'], '_' );
        if ( $pos === false )
          $keycode = $stream['contentkeycode'];
        else
          $keycode = substr( $stream['contentkeycode'], 0, $pos );

        $ret['presentation'] = $ingressurl . $keycode;
      }

      // mobil streamet keresunk (non-desktop)
      if ( !isset( $ret['mobile'] ) and !$stream['isdesktopcompatible'] ) {
        // mobile: ingressurl + keycode UTOLSÓ _ előtti része
        $pos = strrpos( $stream['keycode'], '_' );
        $keycode = substr( $stream['keycode'], 0, $pos );
        $ret['mobile'] = $ingressurl . $keycode;
      }
    }

    return $ret;
  }

  public function handleStreamTemplate( $groupid, $linkid = null ) {
    $this->ensureObjectLoaded();

    $transcoderid = $this->db->getOne("
      SELECT transcoderid
      FROM livestream_groups
      WHERE id = '$groupid'
      LIMIT 1
    ");

    $this->updateRow( array(
        'livestreamgroupid' => $groupid,
        'transcoderid'      => $transcoderid,
      )
    );

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

      if ( $linkid )
        $row['recordinglinkid'] = $linkid;

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
      FROM livefeeds
      WHERE id IN(" . implode(", ", $ids ) . ")
    ");
  }

  public function handleVCRExtraInfo( $start, $userid ) {
    $this->ensureObjectLoaded();
    $row = array();

    if ( !$this->row['recordinglinkid'] )
      throw new \Exception("recordinglinkid invalid for feed #" . $this->id );

    $liveRecModel = $this->bootstrap->getModel('livefeed_recordings');

    if ( $start ) {
      $transcoderid = $this->db->getOne("
        SELECT lg.transcoderid
        FROM
          recording_links AS rl,
          livestream_groups AS lg
        WHERE
          rl.id = '" . $this->row['recordinglinkid'] . "' AND
          lg.id = rl.livestreamgroupid
        LIMIT 1
      ");

      if ( !$transcoderid )
        throw new \Exception(
          "transcoderid invalid for feed #" . $this->id . ", linkid #" .
          $this->row['recordinglinkid']
        );

      if ( $this->row['livefeedrecordingid'] )
        throw new \Exception("livefeedrecordingid set for starting feed #" . $this->id );

      $row['livefeedid'] = $this->id;
      $row['userid'] = $userid;
      $row['starttimestamp'] = date('Y-m-d H:i:s');
      $row['livestreamtranscoderid'] = $transcoderid;
      $row['status'] = 'started';

      $liveRecModel->insert( $row );
      $this->updateRow( array(
          'livefeedrecordingid' => $liveRecModel->id,
        )
      );

    } else {
      if ( !$this->row['livefeedrecordingid'] )
        throw new \Exception("livefeedrecordingid invalid for feed #" . $this->id );

      $row['endtimestamp'] = date('Y-m-d H:i:s');
      $row['recordinglinkid'] = $this->row['recordinglinkid'];
      $row['vcrconferenceid'] = $this->row['vcrconferenceid'];
      $row['status'] = 'finishing';

      $liveRecModel->select( $this->row['livefeedrecordingid'] );
      if ( !$liveRecModel->row )
        throw new \Exception("livefeed_recordings.id not found for feed #" . $this->id );

      $liveRecModel->updateRow( $row );
      $this->db->query("
        UPDATE livefeeds
        SET livefeedrecordingid = NULL
        WHERE id = '" . $this->id . "'
        LIMIT 1
      ");

    }
  }

  private function tryExecuteUniqueSQL( $callback, $args ) {
    // elso arg a mienk, garantaljuk
    array_unshift( $args, 0 );

    $i = 0;
    while( $i <= 10 ) {
      $i++;

      try {
        $args[0] = $i;
        $sql = call_user_func_array( $callback, $args );
        $ret = $this->db->execute( $sql );
      } catch( \Exception $e ) {

        $errno = $this->db->ErrorNo();
        // mysql unique constraint error code 1586/1062/893
        if ( $errno == 1586 or $errno == 1062 or $errno == 893 )
          continue; // re-try
        else // valami mas hiba, re-throw
          throw $e;

      }

      return $ret;
    }

    throw new \Exception('could not execute query in 10 tries: ' . $sql );
  }

  private function generatePIN() {
    $len = $this->bootstrap->config['livepinlength'];
    $min = pow( 10, $len - 1 );
    $max = pow( 10, $len ) - 1;
    return mt_rand( $min, $max );
  }

  public function regeneratePIN( $pin = 0 ) {
    $this->ensureID();
    $id = $this->id;

    if ( !$pin )
      $pin = $this->generatePIN();

    $this->tryExecuteUniqueSQL(
      array( $this, '_regenPINCallback'),
      array( $id, $pin )
    );
  }

  private function _regenPINCallback( $trynum, $id, $pin ) {
    if ( $trynum !== 1 )
      $pin = $this->generatePIN();

    return "
      UPDATE livefeeds
      SET pin = '$pin'
      WHERE id = '$id'
      LIMIT 1
    ";
  }

  public function insert( $values ) {
    if ( !isset( $values['pin'] ) or !$values['pin'] )
      $values['pin'] = $this->generatePIN();

    $this->rs  = $this->select( -1 );

    $rs = $this->tryExecuteUniqueSQL(
      array( $this, '_insertCallback'),
      array( $values )
    );

    $this->rs  = null;
    $this->id  = $this->sqlInsertID( $rs );
    $this->row = $values;
    $this->row[ $this->primarykey ] = $this->id;

    return $this->row;
  }

  private function _insertCallback( $trynum, $values ) {
    if ( $trynum !== 1 )
      $values['pin'] = $this->generatePIN();

    return $this->sqlInsert( $values );
  }

  public function selectByPIN( $pin ) {
    $pin = $this->db->qstr( $pin );
    $ret = $this->db->getRow("
      SELECT *
      FROM livefeeds
      WHERE pin = $pin
      LIMIT 1
    ");

    if ( !empty( $ret ) ) {
      $this->row = $ret;
      $this->id = $ret['id'];
    }

    return $ret;
  }

  public function getInviteCount() {
    $this->ensureID();
    $id = $this->id;
    return $this->db->getOne("
      SELECT COUNT(*)
      FROM livefeed_teacherinvites
      WHERE livefeedid = '$id'
      LIMIT 1
    ");
  }

  public function getInviteArray( $start, $limit, $order ) {
    $this->ensureID();
    $id = $this->id;

    $ret = $this->db->getArray("
      SELECT *
      FROM livefeed_teacherinvites
      WHERE livefeedid = '$id'
      ORDER BY $order
      LIMIT $start, $limit
    ");

    if ( empty( $ret ) )
      return $ret;

    foreach( $ret as $key => $row )
      $this->getInviteInfo( $ret[ $key ] );

    return $ret;
  }

  public function getInviteInfo( &$row ) {
    $emails = array();

    $userids = \Springboard\Tools::explodeIDs(',', $row['userids'] );
    if ( !empty( $userids ) ) {
      $row['users'] = $this->db->getArray("
        SELECT
          usr.id,
          IF(
            usr.nickname IS NULL OR LENGTH(usr.nickname) = 0,
            CONCAT(usr.namelast, '.', usr.namefirst),
            usr.nickname
          ) AS nickname,
          usr.nameformat,
          usr.nameprefix,
          usr.namefirst,
          usr.namelast,
          usr.email
        FROM users AS usr
        WHERE usr.id IN('" . implode("', '", $userids ) . "')
      ");

      foreach( $row['users'] as $user )
        $emails[ $user['email'] ] = true;
    }

    $row['emails'] = \Springboard\Tools::explodeAndTrim(
      "\n", $row['emails']
    );

    foreach( $row['emails'] as $email )
      $emails[ $email ] = true;

    $row['emailcount'] = count( $emails );
    return $emails;
  }

}
