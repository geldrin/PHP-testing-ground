<?php
namespace Model;

class InvalidFileTypeException extends \Exception {}
class InvalidLengthException extends \Exception {}
class InvalidVideoResolutionException extends \Exception {}

class Recordings extends \Springboard\Model {
  public $apisignature = array(
    'getRow' => array(
      'where' => array(
        'type' => 'string'
      ),
    ),
  );
  
  public function resetStats() {
    
    $fields        = array();
    $elementfields = array();
    
    if ( date('N') === '1' ) { // ISO-8601 numeric representation of the day of the week (added   PHP 5.1.0)
      
      $fields[] = 'numberofcommentsthisweek';
      $fields[] = 'numberofviewsthisweek';
      
      $fields[] = 'ratingthisweek';
      $fields[] = 'sumofratingthisweek';
      $fields[] = 'numberofratingsthisweek';
      
    }
    
    if ( date('j') === '1' ) { // Day of the month without leading zeros
      
      $fields[] = 'numberofcommentsthismonth';
      $fields[] = 'numberofviewsthismonth';
      
      $fields[] = 'ratingthismonth';
      $fields[] = 'sumofratingthismonth';
      $fields[] = 'numberofratingsthismonth';
      
    }
    
    $sql = implode( " = '0', ", $fields );
    
    if ( !strlen( $sql ) )
      return false;
    
    $sql .= " = '0' ";
    $sql = "UPDATE recordings SET $sql";
    
    $this->db->execute($sql);
    
    return true;
    
  }
  
  public function resetViewCounters( $type ) {
    
    $this->ensureID();
    
    if ( $type != 'week' and $type != 'month' )
      throw new \Exception('Invalid type passed, expecting "week" or "month"');
    
    $this->db->execute("
      UPDATE recordings
      SET
        numberofviewsthis" . $type . " = 0
      WHERE id = '" . $this->id . "'
      LIMIT 1
    ");
    
  }
  
  public function incrementViewCounters() {
    
    $this->ensureID();
    $this->db->execute("
      UPDATE recordings
      SET
        numberofviews = numberofviews + 1,
        numberofviewsthisweek = numberofviewsthisweek + 1,
        numberofviewsthismonth = numberofviewsthismonth + 1
      WHERE id = '" . $this->id . "'
      LIMIT 1
    ");
    
  }
  
  public function updateMetadataTimestamps( $ids ) {
    
    if ( empty( $ids ) )
      return false;
    
    if ( !is_array( $ids ) )
      $ids = array( $ids );
    
    return $this->db->execute("
      UPDATE recordings
      SET metadataupdatedtimestamp = '" . date('Y-m-d H:i:s') . "'
      WHERE id IN('" . implode("', '", $ids ) . "')
    ");
    
  }
  
  public function updateFulltextCache( $updatemetadata = false ) {
    
    $this->ensureObjectLoaded();
    $values = array(
      'primarymetadatacache' => $this->assemblePrimaryFulltextCache(),
      'additionalcache'      => $this->assembleAdditionalFulltextCache(),
    );
    
    if ( $updatemetadata )
      $values['metadataupdatedtimestamp'] = date('Y-m-d H:i:s');
    
    $this->updateRow( $values );
    
  }
  
  public function assemblePrimaryFulltextCache( $values = array() ) {
    
    $this->ensureObjectLoaded();
    
    if ( empty( $values ) )
      $cache = array(
        @$this->row['title'],
        @$this->row['subtitle'],
        @$this->row['description'],
        @$this->row['technicalnote'],
        @$this->row['keywords'],
      );
    else
      $cache = array(
        @$values['title'],
        @$values['subtitle'],
        @$values['description'],
        @$values['technicalnote'],
        @$values['keywords'],
      );
    
    $contributors     = $this->getContributorsWithRoles();
    $contributornames = array();
    
    if ( !empty( $contributors ) ) {
      
      include_once( $this->bootstrap->config['smartypluginpath'] . 'modifier.nameformat.php');
      foreach( $contributors as $contributor )
        $contributornames[] = \smarty_modifier_nameformat( $contributor );
      
    }
    
    $contributornames = array_unique( $contributornames );
    
    $genres = $this->db->getCol("
      SELECT s.value
      FROM
        genres g,
        recordings_genres rg,
        strings s
      WHERE
        rg.recordingid = '" . $this->id . "' AND
        g.id = rg.genreid AND
        s.translationof = g.name_stringid
    ");
    
    $cache = array_merge( $cache, $contributornames, $genres );
    
    return implode( ' ', $cache );
    
  }
  
  public function getContributorsWithRoles( $wantjobgroups = false, $language = null ) {
    
    if ( !$language )
      $language = \Springboard\Language::get();
    
    $contributors = $this->db->getArray("
      SELECT
        cr.id,
        cr.organizationid,
        cr.contributorid,
        sorgname.value AS organizationname,
        sorgnameshort.value AS organizationnameshort,
        org.url,
        c.id AS contributorid,
        c.nameprefix,
        c.namefirst,
        c.namelast,
        c.nameformat,
        c.namealias,
        s.value AS rolename
      FROM
        contributors_roles AS cr
        LEFT JOIN organizations AS org ON cr.organizationid = org.id
        LEFT JOIN strings AS sorgname ON
          org.name_stringid = sorgname.translationof AND
          sorgname.language = '$language'
        LEFT JOIN strings AS sorgnameshort ON
          org.nameshort_stringid = sorgnameshort.translationof AND
          sorgnameshort.language = '$language'
        LEFT JOIN contributors  AS c ON cr.contributorid  = c.id,
        roles AS r,
        strings AS s
      WHERE
        cr.roleid       = r.id AND
        r.name_stringid = s.translationof AND
        cr.recordingid  = '" . $this->id . "' AND
        s.language      = '$language'
      ORDER BY cr.weight
    ");
    
    if ( $wantjobgroups ) {
      
      $contributorModel = $this->bootstrap->getModel('contributors');
      
      foreach( $contributors as $key => $contributor ) {
        
        $contributorModel->id = $contributor['contributorid'];
        $contributors[ $key ]['jobgroups'] = $contributorModel->getJobGroups();
        
      }
      
    }
    
    return $contributors;
    
  }
  
  public function assembleAdditionalFulltextCache() {
    
    $this->ensureID();
    return ''; // TODO
    $slides = $this->db->getCol("
      SELECT slidecache
      FROM slides_chapters
      WHERE
        recordingid = '" . $this->id . "' AND
        timing IS NOT NULL
    ");
    
    $cache = implode( ' ', $slides );
    
    $documents = $this->db->getCol("
      SELECT documentcache
      FROM attached_documents
      WHERE
        recordingid = '" . $this->id . "' AND
        indexingstatus IN('completed', 'completedempty')
    ");
    
    $cache .= implode( ' ', $documents );
    
    return $cache;
    
  }
  
  function updateChannelIndexPhotos() {
    
    $this->ensureObjectLoaded();
    
    $indexPhotoDone = Array();
    
    if (
         $this->row === null or // toroltek
         $this->row['status'] != 'onstorage' or // nincs onstorage eleme
         $this->row['ispublished'] != 1 // nincs metaadata vagy kikapcsolva
       ) {
      
      // indexphoto
      $rs = $this->db->query("
        SELECT id
        FROM channels
        WHERE
          indexphotofilename LIKE
            'recordings/" . \Springboard\Filesystem::getTreeDir( $this->id ) . "/%' OR
          indexphotofilename LIKE
            'images/videothumb\_audio\_placeholder.png?rid=" . $this->id . "&reid=%'
      ");
      
      $channel = $this->bootstrap->getModel('channels');
      
      foreach( $rs as $fields ) {
        
        $channel->select( $fields['id'] );
        $channel->updateIndexFilename( true );
        $indexPhotoDone[] = $fields['id'];
        
      }
    }
    
    // felvetelszamlalok
    $rs = $this->db->query("
      SELECT channelid
      FROM channels_recordings
      WHERE recordingid = '" . $this->id . "'
    ");
    
    if ( !isset( $channel ) )
      $channel = $this->bootstrap->getModel('channels');
    
    foreach( $rs as $fields ) {
      
      $channel->select( $fields['channelid'] );
      $channel->updateVideoCounters();
      // ha az elozo korben meg nem erintettuk a csatornat,
      // es meg nincs indexkepe, akkor keszitsunk neki
      if ( 
           !strlen( $this->row['indexphotofilename'] ) and
           !in_array( $fields['channelid'], $indexPhotoDone )
         )
        $channel->updateIndexFilename();
      
    }
    
  }
  
  public function updateCategoryCounters( $categoryids = null ) {
    
    if ( $categoryids === null ) {
      
      $this->ensureID();
      
      $categoryids = $this->db->getCol("
        SELECT categoryid
        FROM recordings_categories
        WHERE recordingid = '" . $this->id . "'
      ");
      
    }
    
    $category = $this->bootstrap->getModel('categories');
    
    foreach( $categoryids as $categoryid ) {
      
      $category->select( $categoryid );
      $category->updateVideoCounters();
      
    }
    
  }
  
  public function getIndexPhotoFromChannels( $channelids = array(), $needpublic = null ) {
    
    if ( empty( $channelids ) )
      return '';
    
    $where = '';
    
    if ( $needpublic )
      $where = " AND r.accesstype = 'public' ";
    
    return $this->db->getOne("
      SELECT
        r.indexphotofilename
      FROM
        recordings AS r,
        channels_recordings AS cr
      WHERE
        cr.channelid IN ('" . implode("', '", $channelids ) . "') AND
        r.id = cr.recordingid AND
        LENGTH( r.indexphotofilename ) > 0
        $where
      ORDER BY r.timestamp DESC
      LIMIT 1
    ");
    
  }
  
  public function userHasAccess( $user, $secure = null ) {
    
    $this->ensureObjectLoaded();
    
    if ( $secure !== null and $this->row['issecurestreamingforced'] != $secure )
      return 'securerestricted';
    
    $bystatus   = $this->isAccessibleByStatus( $user );
    $bysettings = $this->isAccessibleBySettings( $user );
    
    if ( $bystatus === true and $bysettings === true )
      return true;
    
    if ( $bystatus !== true )
      return $bystatus;
    else
      return $bysettings;
    
  }
  
  public function insertUploadingRecording( $userid, $organizationid, $languageid, $title, $sourceip ) {
    
    $recording = array(
      'userid'          => $userid,
      'organizationid'  => $organizationid,
      'languageid'      => $languageid,
      'title'           => $title,
      'mediatype'       => $this->metadata['mastermediatype'],
      'status'          => 'uploading',
      'masterstatus'    => 'uploading',
      'accesstype'      => 'public',
      'mastersourceip'  => $sourceip,
      'timestamp'       => date('Y-m-d H:i:s'),
      'recordedtimestamp' => date('Y-m-d H:i:s'),
      'metadataupdatedtimestamp' => date('Y-m-d H:i:s'),
      
    
    ) + $this->metadata;
    
    return $this->insert( $recording );
    
  }
  
  public function handleFile( $source, $handlefile = 'upload', $postfix = null ) {
    
    $this->ensureObjectLoaded();
    
    if ( !$this->metadata )
      throw new \Exception('No metadata for the video found, please ->analyize() it beforehand!');
    
    if ( $postfix === null ) {
      
      if ( $this->metadata['mastermediatype'] == 'audio' )
        $postfix = '_audio';
      else
        $postfix = '_video';
      
    }
    
    $target =
      $this->bootstrap->config['uploadpath'] . 'recordings/' . $this->id .
      $postfix . '.' . $this->metadata['mastervideoextension']
    ;
    
    switch ( $handlefile ) {
      case 'copy':   $ret = copy( $source, $target ); break;
      case 'upload': $ret = move_uploaded_file( $source, $target ); break;
      case 'rename': $ret = rename( $source, $target ); break;
      default: throw new \Exception('unsupported operation: ' . $handlefile ); break;
    }
    
    if ( !$ret )
      throw new \Exception( $handlefile . ' failed from ' . $source . ' to ' . $target );
    
    return $ret;
    
  }
  
  public function analyze( $filename, $originalfilename = null ) {
    
    $config = $this->bootstrap->config;
    
    if ( !$originalfilename )
      $originalfilename = $filename;
    
    $cmd = sprintf( $config['mplayer_identify'], escapeshellarg( $filename ) );
    exec( $cmd, $output, $return );
    $output = implode("\n", $output );
    
    if ( $return )
      throw new \Exception('MPlayer returned non-zero exit code, output was: ' . $output, $return );
    
    if ( preg_match('/Seek failed/', $output ) )
      throw new InvalidFileTypeException('Got unrecognized file, output was: ' . $output, $return );
    
    if ( $this->bootstrap->debug )
      var_dump( $output );
    
    preg_match_all('/(ID_.+)=(.*)\n/m', $output, $matches );
    
    $data = array();
    foreach( $matches[1] as $key => $value )
      $data[ $value ] = $matches[2][ $key ];
    
    if ( isset( $data['ID_VIDEO_ID'] ) and !isset( $data['ID_AUDIO_ID'] ) and !isset( $data['ID_AUDIO_CODEC'] ) )
      $mediatype = 'videoonly';
    elseif ( isset( $data['ID_VIDEO_ID'] ) )
      $mediatype = 'video';
    else
      $mediatype = 'audio';
    
    if ( ( $pos = strrpos( $originalfilename, '.') ) !== false ) {
      
      $extension      = substr( $originalfilename, $pos + 1 );
      $videocontainer = $extension;
      
    } else {
      
      $videocontainer = @$data['ID_AUDIO_CODEC'];
      $extension      = null;
      
    }
    
    $videofps       = ( @$data['ID_VIDEO_FPS'] > 60? 25: @$data['ID_VIDEO_FPS'] );
    $videocodec     = @$data['ID_VIDEO_FORMAT'];
    $videobitrate   = @$data['ID_VIDEO_BITRATE'];

    // 2Mbps video bitrate is assumed when mplayer gives 0
    if ( $mediatype != "audio" and !$videobitrate )
      $videobitrate = 2000000;
    
    $videores = null;
    if ( @$data['ID_VIDEO_WIDTH'] ) {
      
      $videowidth  = $data['ID_VIDEO_WIDTH'];
      $videoheight = $data['ID_VIDEO_HEIGHT'];
      
    } elseif ( @$data['ID_CLIP_INFO_NAME1'] == 'width' and @$data['ID_CLIP_INFO_NAME2'] == 'height' ) {
      
      $videowidth  = $data['ID_CLIP_INFO_VALUE1'];
      $videoheight = $data['ID_CLIP_INFO_VALUE2'];
      
    }
    
    if ( isset( $videowidth ) and strlen( $videowidth ) and strlen( $videoheight ) ) {
      
      $videores = $videowidth . 'x' . $videoheight;
      
      if ( $videowidth > 1920 or $videoheight > 1080 )
        throw new InvalidVideoResolutionException('Video bigger than 1920x1080');
      
    }
    
    if ( ( $key = array_search('duration', $matches[2] ) ) ) // no ID_LENGTH for flv-s, get it from the metadata
      $videolength = $matches[2][ $key + 1 ]; // "ID_CLIP_INFO_NAME0 + 1 == ID_CLIP_INFO_VALUE0
    elseif ( @$data['ID_LENGTH'] )
      $videolength = $data['ID_LENGTH'];
    else
      throw new InvalidLengthException('Length not found for the media, output was ' . $output );
    
    if ( $videolength <= $config['recordings_seconds_minlength'] )
      throw new InvalidLengthException('Recording length was less than ' . $config['recordings_seconds_minlength'] );
    
    $audiofreq     = @$data['ID_AUDIO_RATE'];
    $audiobitrate  = @$data['ID_AUDIO_BITRATE'];
    $audiochannels = @$data['ID_AUDIO_NCH'];
    $audiocodec    = @$data['ID_AUDIO_CODEC'];

    // 128Kbps audio bitrate is assumed when mplayer gives 0
    if ( $audiobitrate == 0 )
      $audiobitrate = 128000;

    if ( $audiocodec ) {
      
      $audiomode    = 'vbr';
      $audioquality = 'lossy';
      
    } else {
      
      $audiomode    = null;
      $audioquality = null;
      
    }
    
    $info = array(
      'mastermediatype'            => $mediatype,
      'mastervideoextension'       => $extension,
      'mastervideocontainerformat' => $videocontainer,
      'mastervideofilename'        => basename( $originalfilename ),
      'mastervideofps'             => $videofps,
      'mastervideocodec'           => $videocodec,
      'mastervideores'             => $videores,
      'mastervideobitrate'         => $videobitrate,
      'masterlength'               => floor( $videolength ),
      'masteraudiocodec'           => $audiocodec,
      'masteraudiochannels'        => $audiochannels,
      'masteraudiobitratemode'     => $audiomode,
      'masteraudioquality'         => $audioquality,
      'masteraudiofreq'            => $audiofreq,
      'masteraudiobitrate'         => $audiobitrate,
    );
    
    return $this->metadata = $info;
    
  }
  
  public function clearGenres() {
    
    $this->ensureID();
    
    $this->db->execute("
      DELETE FROM recordings_genres
      WHERE recordingid = '" . $this->id . "'
    ");
    
  }
  
  public function clearCategories() {
    
    $this->ensureID();
    
    $categoryids = $this->db->getCol("
      SELECT categoryid
      FROM recordings_categories
      WHERE recordingid = '" . $this->id . "'
    ");
    
    $this->db->execute("
      DELETE FROM recordings_categories
      WHERE recordingid = '" . $this->id . "'
    ");
    
    $this->updateCategoryCounters( $categoryids );
    
  }
  
  public function addCategories( $categoryids ) {
    $this->insertMultipleIDs( $categoryids, 'recordings_categories', 'categoryid');
    $this->updateCategoryCounters();
  }
  
  public function addGenres( $genreids ) {
    $this->insertMultipleIDs( $genreids, 'recordings_genres', 'genreid');
  }
  
  protected function insertMultipleIDs( $ids, $table, $field ) {
    
    $this->ensureID();
    
    $values = array();
    foreach( $ids as $id )
      $values[] = "('" . intval( $id ) . "', '" . $this->id . "')";
    
    $this->db->execute("
      INSERT INTO $table ($field, recordingid)
      VALUES " . implode(', ', $values ) . "
    ");
    
  }
  
  public function clearAccess() {
    
    $this->ensureID();
    
    $this->db->execute("
      DELETE FROM access
      WHERE recordingid = '" . $this->id . "'
    ");
    
  }
  
  public function restrictDepartments( $departmentids ) {
    $this->insertMultipleIDs( $departmentids, 'access', 'departmentid');
  }
  
  public function restrictGroups( $groupids ) {
    $this->insertMultipleIDs( $groupids, 'access', 'groupid');
  }
  
  public function isAccessibleByStatus( $user ) {
    
    $this->ensureObjectLoaded();
    $statuses = array(
      'markedfordeletion' => 'recorddeleted',
    );
    
    if ( in_array( $this->row['status'], $statuses ) )
      return $statuses[ $this->row['status'] ];
    
    if ( $this->row['status'] != 'onstorage' )
      return 'recordingconverting';
    
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
    elseif ( !$this->row['ispublished'] )
      return 'recordingisnotpublished';
    
    return true;
    
  }
  
  public function isAccessibleBySettings( $user ) {
    
    $this->ensureObjectLoaded();
    
    $timefailed = false;
    if ( $this->row['visibleuntil'] ) {
      
      $visiblefrom  = strtotime( $this->row['visiblefrom'] );
      $visibleuntil = strtotime( $this->row['visibleuntil'] );
      $now          = strtotime( date('Y-m-d', time() ) );
      
      if ( $visiblefrom > $now or $visibleuntil < $now )
        $timefailed = true;
      
    }
    
    switch( $this->row['accesstype'] ) {
      
      case 'public':
        if ( $timefailed )
          return 'publicrestricted_timefailed';
        break;
      
      case 'registrations':
        
        if ( !isset( $user['id'] ) )
          return 'registrationrestricted';
        elseif ( $timefailed )
          return 'registrationrestricted_timefailed';
        
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
        
        $recordingid = "'" . $this->id . "'";
        $userid      = "'" . $user['id'] . "'";
        
        if ( $this->row['accesstype'] == 'departments')
          $sql = "
            SELECT
              u.id
            FROM
              access AS a,
              users AS u
            WHERE
              a.recordingid  = $recordingid AND
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
              a.recordingid = $recordingid AND
              gm.groupid    = a.groupid AND
              gm.userid     = $userid
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
  
  public static function getPublicRecordingWhere( $prefix = '' ) {
    
    return "
      {$prefix}status      = 'onstorage' AND
      {$prefix}ispublished = '1' AND
      {$prefix}accesstype  = 'public' AND
      (
        {$prefix}visiblefrom  IS NULL OR
        {$prefix}visibleuntil IS NULL OR
        (
          {$prefix}visiblefrom  <= CURRENT_DATE() AND
          {$prefix}visibleuntil >= CURRENT_DATE()
        )
      ) 
    ";
    
  }
  
  public static function getUnionSelect( $user, $select = 'r.*', $from = 'recordings AS r', $where = null ) {
    
    if ( !isset( $user['id'] ) ) {
      
      $publicwhere = self::getPublicRecordingWhere('r.');
      if ( $where )
        $publicwhere = ' AND ' . $publicwhere;
      
      return "
        (
          SELECT $select
          FROM $from
          WHERE $where $publicwhere
        )
      ";
      
    }
    
    $generalwhere = "
      r.status      = 'onstorage' AND
      (
        r.ispublished = '1' OR
        r.userid = '" . $user['id'] . "'
      ) AND
      (
        r.visiblefrom  IS NULL OR
        r.visibleuntil IS NULL OR
        (
          r.visiblefrom  <= CURRENT_DATE() AND
          r.visibleuntil >= CURRENT_DATE()
        )
      )
    ";
    
    if ( $where )
      $generalwhere = ' AND ' . $generalwhere;
    
    $sql = "
      (
        SELECT $select
        FROM $from
        WHERE
          $where
          $generalwhere AND
          r.accesstype IN('public', 'registrations')
      ) UNION DISTINCT (
        SELECT $select
        FROM
          $from,
          access AS a,
          groups_members AS gm
        WHERE
          $where
          $generalwhere AND
          r.accesstype  = 'groups' AND
          a.recordingid = r.id AND
          a.groupid     = gm.groupid AND
          gm.userid     = '" . $user['id'] . "'
      ) UNION DISTINCT (
        SELECT $select
        FROM
          $from,
          access AS a,
          users AS u
        WHERE
          $where
          $generalwhere AND
          r.accesstype   = 'departments' AND
          a.recordingid  = r.id AND
          a.departmentid = u.departmentid AND
          u.id           = '" . $user['id'] . "'
      )
    ";
    
    return $sql;
    
  }
  
  public function addRating( $rating ) {
    
    $this->ensureID();
    
    $this->db->execute("
      UPDATE recordings
      SET
        numberofratings = numberofratings + 1,
        numberofratingsthisweek = numberofratingsthisweek + 1,
        numberofratingsthismonth = numberofratingsthismonth + 1,
        sumofrating = sumofrating + " . $rating . ",
        sumofratingthisweek = sumofratingthisweek + " . $rating . ",
        sumofratingthismonth = sumofratingthismonth + " . $rating . ",
        rating = sumofrating / numberofratings,
        ratingthisweek = sumofratingthisweek / numberofratingsthisweek,
        ratingthismonth = sumofratingthismonth / numberofratingsthismonth
      WHERE
        id = '" . $this->id . "'
    ");
    
    if ( $this->db->Affected_Rows() ) {
      
      $this->select( $this->id );
      return true;
      
    } else
      return false;
    
  }
  
  public function incrementCommentCount() {
    
    $this->ensureID();
    $this->db->execute("
      UPDATE recordings
      SET
        numberofcomments = numberofcomments + 1,
        numberofcommentsthisweek = numberofcommentsthisweek + 1,
        numberofcommentsthismonth = numberofcommentsthismonth + 1
      WHERE
        id = '" . $this->id . "'
    ");
    
    return (bool)$this->db->Affected_Rows();
    
  }
  
  public function incrementViews() {
    
    $this->ensureID();
    $this->db->execute("
      UPDATE recordings
      SET
        numberofviews = numberofviews + 1,
        numberofviewsthisweek = numberofviewsthisweek + 1,
        numberofviewsthismonth = numberofviewsthismonth + 1
      WHERE
        id = '" . $this->id . "'
    ");
    
    return (bool)$this->db->Affected_Rows();
    
  }
  
  public function addComment( $values ) {
    
    $this->ensureID();
    
    $commentModel = $this->bootstrap->getModel('comments');
    $commentModel->insert( $values );
    
  }
  
  public function getComments( $start = 0, $limit = 10 ) {
    
    $this->ensureID();
    
    $comments = $this->db->getArray("
      SELECT
        c.id,
        c.timestamp,
        c.text,
        c.userid,
        u.nickname,
        u.nameformat,
        u.nameprefix,
        u.namefirst,
        u.namelast
      FROM
        comments AS c,
        users AS u
      WHERE
        c.recordingid = '" . $this->id . "' AND
        c.userid      = u.id AND
        c.moderated   = '0'
      ORDER BY
        c.id DESC
      LIMIT $start, $limit
    ");
    
    foreach( $comments as $key => $value ) {
      // TODO user name format
      $comments[ $key ]['nickname'] = htmlspecialchars( $value['nickname'], ENT_QUOTES, 'UTF-8' );
      $comments[ $key ]['text']     = nl2br( htmlspecialchars( $value['text'], ENT_QUOTES, 'UTF-8' ) );
      
    }
    
    return $comments;
    
  }
  
  public function getCommentsCount() {
    
    $this->ensureID();
    
    return $this->db->getOne("
      SELECT COUNT(*)
      FROM comments
      WHERE
        moderated   = '0' AND
        recordingid = '" . $this->id . "'
    ");
    
  }
  
  public function getRelatedVideosByKeywords( $limit = NUMBER_OF_RELATED_VIDEOS ){
    
    $this->ensureObjectLoaded();
    if ( !strlen( trim( $this->row['keywords'] ) ) )
      return array();
    
    $keywords    = explode(',', $this->row['keywords'] );
    $where       = array();
    
    foreach( $keywords as $key => $value ) {
      
      $keyword = $this->db->qstr( '%' . trim( $value ) . '%' );
      $where[] = 'r.keywords LIKE ' . $keyword;
      
    }
    
    $where  = implode(' OR ', $where );
    $rs = $this->db->query("
      SELECT
        r.id,
        r.title,
        r.subtitle,
        r.indexphotofilename,
        r.masterlength,
        r.numberofviews,
        u.id AS userid,
        u.nickname,
        u.nameformat,
        u.nameprefix,
        u.namefirst,
        u.namelast
      FROM
        recordings AS r,
        users AS u
      WHERE 
        ( $where ) AND
        u.id = r.userid AND
        r.id <> '" . $this->id . "' AND
        " . self::getPublicRecordingWhere('r.') . "
      LIMIT $limit
    ");
    
    $return = array();
    foreach( $rs as $recording )
      $return[ $recording['id'] ] = $recording;
    
    return $return;
    
  }
  
  public function getRelatedVideosByChannel( $limit, $channelids = null ) {
    
    $this->ensureID();
    $dontrecurse = true;
    if ( $channelids === null ) {
      
      $dontrecurse = false;
      $channelids  = $this->db->getCol("
        SELECT channelid
        FROM channels_recordings
        WHERE recordingid = '" . $this->id . "'
      ");
      
    }
    
    $rs = $this->db->query("
      SELECT
        r.id,
        r.title,
        r.subtitle,
        r.indexphotofilename,
        r.masterlength,
        r.numberofviews,
        u.id AS userid,
        u.nickname,
        u.nameformat,
        u.nameprefix,
        u.namefirst,
        u.namelast
      FROM
        recordings AS r,
        users AS u,
        channels_recordings AS cr
      WHERE
        cr.channelid IN('" . implode("', '", $channelids ) . "') AND
        r.id = cr.recordingid AND
        u.id = r.userid AND
        r.id <> '" . $this->id . "' AND
        " . self::getPublicRecordingWhere('r.') . "
      ORDER BY RAND()
      LIMIT $limit
    ");
    
    $return = array();
    foreach( $rs as $recording )
      $return[ $recording['id'] ] = $recording;
    
    if ( count( $return ) < $limit and !$dontrecurse ) {
      
      $parentids    = array();
      $channelModel = $this->bootstrap->getModel('channels');
      foreach( $channelids as $channelid ) {
        
        $parents   = $channelModel->findParents( $channelid );
        $parents[] = $channelid;
        $parentids = array_merge( $parentids, $parents );
        
      }
      
      $parentids = array_unique( $parentids );
      $return = $return + $this->getRelatedVideosByChannel( $limit - count( $return ), $parentids );
      
    }
    
    return $return;
    
  }
  
  public function getRelatedVideosRandom( $limit ) {
    
    $this->ensureID();
    $rs = $this->db->query("
      SELECT
        r.id,
        r.title,
        r.subtitle,
        r.indexphotofilename,
        r.masterlength,
        r.numberofviews,
        u.id AS userid,
        u.nickname,
        u.nameformat,
        u.nameprefix,
        u.namefirst,
        u.namelast
      FROM
        recordings AS r,
        users AS u
      WHERE
        u.id = r.userid AND
        r.id <> '" . $this->id . "' AND
        " . self::getPublicRecordingWhere('r.') . "
      ORDER BY RAND()
      LIMIT $limit
    ");
    
    $return = array();
    foreach( $rs as $recording )
      $return[ $recording['id'] ] = $recording;
    
    return $return;
    
  }
  
  public function getRelatedVideos( $count ) {
    
    $this->ensureObjectLoaded();
    
    $return = array();
    
    if ( count( $return ) < $count )
      $return = $return + $this->getRelatedVideosByChannel( $count - count( $return ) );
    
    if ( count( $return ) < $count )
      $return = $return + $this->getRelatedVideosByKeywords( $count - count( $return ) );
    
    if ( count( $return ) < $count )
      $return = $return + $this->getRelatedVideosRandom( $count - count( $return ) );
    
    return $return;
    
  }
  
  public function canUploadContentVideo() {
    
    $this->ensureObjectLoaded();
    
    if (
         (
           !$this->row['contentstatus'] or
           $this->row['contentstatus'] == 'markedfordeletion'
         ) and
         $this->row['contentmasterstatus'] != 'copyingtostorage'
       )
      return true;
    else
      return false;
    
  }
  
  public function addContentRecording( $isinterlaced = 0, $sourceip ) {
    
    $this->ensureObjectLoaded();
    
    if ( !$this->metadata )
      throw new Exception('No metadata for the video found, please ->analyize() it beforehand!');
    
    if ( $this->metadata['mastermediatype'] == 'audio' )
      throw new InvalidFileTypeException('The file provided contains only audio, that is not supported');
    
    $values = array(
      'contentstatus'            => 'uploading',
      'contentvideoisinterlaced' => $isinterlaced,
      'contentmastersourceip'    => $sourceip,
    );
    
    foreach( $this->metadata as $key => $value ) {
      
      $key = str_replace('master', 'contentmaster', $key );
      $values[ $key ] = $value;
      
    }
    
    $this->oldcontentstatuses = array(
      'contentstatus'       => $this->row['contentstatus'],
      'contentmasterstatus' => $this->row['contentmasterstatus'],
    );
    
    return $this->updateRow( $values );
    
  }
  
  public function markContentRecordingUploaded() {
    
    $this->ensureObjectLoaded();
    if ( empty( $this->oldcontentstatuses ) )
      throw new Exception('No oldcontentstatuses found, was there an addContentRecording before this?');
    
    // uj feltoltes
    $contentstatus = array(
      'contentstatus'       => 'uploaded',
      'contentmasterstatus' => 'uploaded',
    );
    
    // contentmasterstatus not null -> nem "friss" feltoltes
    if ( $this->oldcontentstatuses['contentmasterstatus'] )
      $contentstatus['contentstatus'] = 'reconvert';
    
    $this->updateRow( $contentstatus );
    
  }
  
  public function getCategoryRecordingsCount( $user, $categoryids ) {
    
    $from = "
      recordings_categories AS rc,
      recordings AS r"
    ;
    
    $where =
      "rc.categoryid IN ('" . implode("', '", $categoryids ) . "') AND
      r.id = rc.recordingid"
    ;
    
    if ( !isset( $user['id'] ) ) 
      return $this->db->getOne("
        SELECT DISTINCT COUNT(r.id)
        FROM $from
        WHERE
          $where AND
          " . self::getPublicRecordingWhere('r.')
    );
    
    return $this->db->getOne("
      SELECT COUNT(*)
      FROM (
        " . self::getUnionSelect( $user, 'r.*', $from, $where ) . "
      ) AS count
    ");
    
  }
  
  public function getCategoryRecordings( $user, $categoryids, $start = false, $limit = false, $order = false ) {
    
    $from = "
      recordings_categories AS rc,
      recordings AS r"
    ;
    
    $where =
      "rc.categoryid IN ('" . implode("', '", $categoryids ) . "') AND
      r.id = rc.recordingid"
    ;
    
    return $this->db->getArray("
      " . self::getUnionSelect( $user, 'r.*', $from, $where ) .
      ( strlen( $order ) ? 'ORDER BY ' . $order : '' ) . " " .
      ( is_numeric( $start ) ? 'LIMIT ' . $start . ', ' . $limit : "" )
    );
    
  }
  
  public function getChannelRecordingsCount( $user, $channelids ) {
    
    $from = "
      channels_recordings AS cr,
      recordings AS r"
    ;
    
    $where =
      "cr.channelid IN ('" . implode("', '", $channelids ) . "') AND
      r.id = cr.recordingid"
    ;
    
    if ( !isset( $user['id'] ) ) 
      return $this->db->getOne("
        SELECT DISTINCT COUNT(r.id)
        FROM $from
        WHERE
          $where AND
          " . self::getPublicRecordingWhere('r.')
    );
    
    return $this->db->getOne("
      SELECT COUNT(*)
      FROM (
        " . self::getUnionSelect( $user, 'r.*', $from, $where ) . "
      ) AS count
    ");
    
  }
  
  public function getChannelRecordings( $user, $channelids, $start = false, $limit = false, $order = false ) {
    
    $from = "
      channels_recordings AS cr,
      recordings AS r"
    ;
    
    $where =
      "cr.channelid IN ('" . implode("', '", $channelids ) . "') AND
      r.id = cr.recordingid"
    ;
    
    return $this->db->getArray("
      " . self::getUnionSelect( $user, 'r.*', $from, $where ) .
      ( strlen( $order ) ? 'ORDER BY ' . $order : '' ) . " " .
      ( is_numeric( $start ) ? 'LIMIT ' . $start . ', ' . $limit : "" )
    );
    
  }
  
  public function getChannelsForUser( $user, $channeltype = null ) {
    
    $this->ensureID();
    $channelModel = $this->bootstrap->getModel('channels');
    $where        = array();
    
    if ( $user['id'] )
      $where[] = "c.userid = '" . $user['id'] . "'";
    
    if ( $user['isclientadmin'] )
      $where[] = "c.organizationid = '" . $user['organizationid'] . "'";
    
    $where = implode(" OR ", $where );
    
    if ( $channeltype ) {
      
      $typeids = $channelModel->cachedGetIDsByType( $channeltype );
      
      if ( !empty( $typeids ) ) {
        
        if ( $where )
          $where = "( $where ) AND ";
        
        $where .= " c.channeltypeid IN('" . implode("', '", $typeids ) . "')";
        
      }
      
    }
    
    $channels = $channelModel->getChannelTree(
      false,
      false,
      $where
    );
    
    $activechannelids = $this->db->getCol("
      SELECT channelid
      FROM channels_recordings
      WHERE
        userid      = '" . $user['id'] . "' AND
        recordingid = '" . $this->id . "'
    ");
    
    $channels = $this->markChannelsActive( $channels, $activechannelids );
    return $channels;
    
  }
  
  protected function markChannelsActive( &$channels, &$activechannelids ) {
    
    if ( empty( $activechannelids ) )
      return $channels;
    
    foreach( $channels as $key => $channel ) {
      
      $channels[ $key ]['active'] = in_array( $channel['id'], $activechannelids );
      
      if ( !empty( $channel['children'] ) )
        $channels[ $key ]['children'] =
          $this->markChannelsActive( $channel['children'], $activechannelids );
        ;
      
    }
    
    return $channels;
    
  }
  
  public function getFlashData( $info, $sessionid ) {
    
    $this->ensureObjectLoaded();
    include_once( $this->bootstrap->config['templatepath'] . 'Plugins/modifier.indexphoto.php' );
    
    $recordingbaseuri = $info['BASE_URI'] . \Springboard\Language::get() . '/recordings/';
    $domain           = $info['organization']['domain'];
    $typeprefix       = ( $this->row['issecurestreamingforced'] )? 'sec': '';
    
    $data = array(
      'language'              => \Springboard\Language::get(),
      'media_servers'         => array(
        $this->getWowzaUrl( $typeprefix . 'rtmpurl', true, $domain, $sessionid ),
        $this->getWowzaUrl( $typeprefix . 'rtmpturl', true, $domain, $sessionid ),
      ),
      'track_firstPlay'       => $recordingbaseuri . 'track/' . $this->id,
      'media_length'          => $this->row['masterlength'],
      'recording_title'       => $this->row['title'],
      'recording_subtitle'    => (string)$this->row['subtitle'],
      'recording_description' => (string)$this->row['description'],
      'recording_image'       => \smarty_modifier_indexphoto( $this->row, 'player', $info['STATIC_URI'] ),
      
    );
    
    // default bal oldalon van a video, csak akkor allitsuk be ha kell
    if ( !$this->row['slideonright'] )
      $data['layout_videoOrientation'] = 'right';
    
    if ( $data['language'] != 'en' )
      $data['locale'] = $info['STATIC_URI'] . 'js/flash_locale_' . $data['language'] . '.json';
    
    $data['media_streams'] = array( $this->getMediaUrl('default', false, $domain ) );
    
    if ( $this->row['videoreshq'] )
      $data['media_streams'][] = $this->getMediaUrl('default', true, $domain );
    
    if ( $this->row['offsetstart'] )
      $data['timeline_virtualStart'] = $this->row['offsetstart'];
    
    if ( $this->row['offsetend'] )
      $data['timeline_virtualEnd'] = $this->row['offsetend'];
    
    if ( $this->row['contentstatus'] == 'onstorage' ) {
      
      $data['content_length']         = $this->row['contentmasterlength'];
      $data['media_secondaryStreams'] = array( $this->getMediaUrl('content', false, $domain ) );
      
      if ( $this->row['contentvideoreshq'] ) {
        
        $data['media_secondaryStreams'][] = $this->getMediaUrl('content', true, $domain );
        
        // ha van HQ content, de nincs HQ "default" verzio akkor ketszer
        // kell szerepeljen a default verzio
        if ( count( $data['media_streams'] ) == 1 )
          $data['media_streams'][] = reset( $data['media_streams'] );
        
      }
      
      if ( $this->row['contentoffsetstart'] )
        $data['timeline_contentVirtualStart'] = $this->row['contentoffsetstart'];
      
      if ( $this->row['contentoffsetend'] )
        $data['timeline_contentVirtualEnd'] = $this->row['contentoffsetend'];
      
    }
    
    $subtitles = $this->getSubtitleLanguages();
    
    if ( count( $subtitles ) == 1 )
      $defaultsubtitle = $subtitles[0]['languagecode'];
    else
      $defaultsubtitle = $this->getDefaultSubtitleLanguage();
    
    $autoshowsubtitle = true;
    if (
         ( $data['language'] == 'hu' and $defaultsubtitle == 'hun' ) or
         ( $data['language'] == 'en' and $defaultsubtitle == 'eng' )
       )
      $autoshowsubtitle = false;
    
    if ( $autoshowsubtitle )
      $data['subtitle_autoShow'] = true;
    
    if ( $defaultsubtitle )
      $data['subtitle_default'] = $defaultsubtitle;
    
    if ( !empty( $subtitles ) ) {
      
      $data['subtitle_files'] = array();
      foreach( $subtitles as $subtitle ) {
        
        $data['subtitle_files'][ $subtitle['languagecode'] ] =
          $recordingbaseuri . 'getsubtitle/' . $subtitle['id']
        ;
        
      }
      
    }
    
    $relatedvideos = $this->getRelatedVideos( $this->bootstrap->config['relatedrecordingcount'] );
    $data['recommendatory_string'] = array();
    foreach( $relatedvideos as $video ) {
      
      $data['recommendatory_string'][] = array(
        'title'       => $video['title'],
        'subtitle'    => $video['subtitle'],
        'image'       => \smarty_modifier_indexphoto( $video, 'wide', $info['STATIC_URI'] ),
        'url'         =>
          $recordingbaseuri . 'details/' . $video['id'] . ',' .
          \Springboard\Filesystem::filenameize( $video['title'] )
        ,
      );
      
    }
    
    return $data;
    
  }
  
  public function getStructuredFlashData( $info, $sessionid ) {
    
    $flashdata = $this->transformFlashData(
      $this->getFlashData( $info, $sessionid )
    );
    
    $flashdata['recommendatory']        = $flashdata['recommendatory']['string'];
    $flashdata['recording']['duration'] = $flashdata['media']['length'];
    unset( $flashdata['media']['length'] );
    
    return $flashdata;
    
  }
  
  protected function transformFlashData( $data ) {
    
    $flashdata = array();
    foreach( $data as $key => $value ) {
      
      $key = explode('_', $key );
      if ( is_array( $value ) )
        $value = $this->transformFlashData( $value );
      
      if ( count( $key ) == 1 )
        $flashdata[ $key[0] ] = $value;
      elseif ( count( $key ) == 2 ) {
        
        if ( !isset( $flashdata[ $key[0] ] ) )
          $flashdata[ $key[0] ] = array();
        
        $flashdata[ $key[0] ][ $key[1] ] = $value;
        
      } else
        throw new \Exception('key with more then two underscores!');
      
    }
    
    return $flashdata;
    
  }
  
  protected function getWowzaUrl( $type, $needextraparam = false, $domain = null, $sessionid = null ) {
    
    $url = $this->bootstrap->config['wowza'][ $type ];
    
    if ( !$needextraparam )
      return $url;
    else {
      
      $this->ensureID();
      return $url . $this->getAuthorizeSessionid( $domain, $sessionid );
      
    }
    
  }
  
  protected function getAuthorizeSessionid( $domain, $sessionid ) {
    
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
  
  public function getMediaUrl( $type, $highquality, $domain = null, $sessionid = null, $host = '' ) {
    
    $this->ensureObjectLoaded();
    
    $typeprefix = '';
    $extension  = 'mp4';
    $postfix    = '_lq';
    $isaudio    = $this->row['mastermediatype'] == 'audio';
    
    if ( $highquality and !$isaudio )
      $postfix = '_hq';
    
    if ( $this->row['issecurestreamingforced'] )
      $typeprefix = 'sec';
    
    if ( $isaudio ) {
      
      $postfix   = '';
      $extension = 'mp3';
      
    }
    
    switch( $type ) {
      
      case 'mobilehttp':
        //http://stream.videotorium.hu:1935/vtorium/_definst_/mp4:671/2671/2671_2608_mobile.mp4/playlist.m3u8
        $host        = $this->getWowzaUrl( $typeprefix . 'httpurl');
        $sprintfterm =
          '%3$s:%s/%s_mobile' . $postfix . '.%s/playlist.m3u8' .
          $this->getAuthorizeSessionid( $domain, $sessionid )
        ;
        
        break;
      
      case 'mobilertsp':
        //rtsp://stream.videotorium.hu:1935/vtorium/_definst_/mp4:671/2671/2671_2608_mobile.mp4
        $host        = $this->getWowzaUrl( $typeprefix . 'rtspurl');
        $sprintfterm =
          '%3$s:%s/%s_mobile' . $postfix . '.%s' .
          $this->getAuthorizeSessionid( $domain, $sessionid )
        ;
        
        break;
      
      case 'direct':
        
        if ( $isaudio )
          $sprintfterm = 'files/recordings/%s/%s_audio.%s';
        else
          $sprintfterm = 'files/recordings/%s/%s_video' . $postfix . '.%s';
        
        break;
      
      case 'content':
        
        $sprintfterm = '%3$s:%s/%s_content' . $postfix . '.%s';
        break;
      
      default:
        
        if ( $isaudio )
          $sprintfterm = '%3$s:%s/%s_audio.%s';
        else
          $sprintfterm = '%3$s:%s/%s_video' . $postfix . '.%s';
        
        break;
      
    }
    
    return $host . sprintf( $sprintfterm,
      \Springboard\Filesystem::getTreeDir( $this->id ),
      $this->id,
      $extension
    );
    
  }
  
  public function getAuthor() {
    
    $this->ensureObjectLoaded();
    return $this->db->getRow("
      SELECT nickname
      FROM users
      WHERE id = '" . $this->row['userid'] . "'
      LIMIT 1
    ");
    
  }
  
  public function getSubtitleLanguages() {
    
    $this->ensureID();
    return $this->db->getArray("
      SELECT
        st.id,
        s.value AS language,
        l.shortname AS languagecode
      FROM
        subtitles AS st,
        strings AS s,
        languages AS l
      WHERE
        st.recordingid  = '" . $this->id . "' AND
        s.translationof = st.languageid AND
        s.language      = '" . \Springboard\Language::get() . "' AND
        l.id            = st.languageid
    ");
    
  }
  
  public function getDefaultSubtitleLanguage() {
    
    $this->ensureID();
    return $this->db->getOne("
      SELECT l.shortname AS languagecode
      FROM
        subtitles AS st,
        languages AS l
      WHERE
        st.recordingid = '" . $this->id . "' AND
        l.id           = st.languageid AND
        st.isdefault   = '0'
      LIMIT 1
    ");
    
  }
  
  public function markAsDeleted() {
    
    $this->ensureObjectLoaded();
    
    $this->updateRow( array(
        'status' => 'markedfordeletion',
      )
    );
    // TODO delete minden ami ezzel kapcsolatos
    $this->updateChannelIndexPhotos();
    $this->updateCategoryCounters();
    return true;
    
  }
  
  public function getRandomRecordings( $limit, $organizationid, $user ) {
    
    // TODO isfeatured uncomment, users avatar
    $select = "
      us.nickname,
      us.nameformat,
      us.nameprefix,
      us.namefirst,
      us.namelast,
      r.id,
      r.title,
      r.indexphotofilename
    ";
    
    $tables = "
      recordings AS r,
      users AS us
    ";
    
    $where = "
      us.id = r.userid AND
      r.organizationid = '" . $organizationid . "'
    ";
    
    return $this->db->getArray(
      self::getUnionSelect( $user, $select, $tables, $where ) . "
      ORDER BY RAND()
      LIMIT $limit
    ");
    
  }
  
  public function getSearchAllCount( $user, $organizationid, $searchterm ) {
    
    $searchterm  = str_replace( ' ', '%', $searchterm );
    $searchterm  = $this->db->qstr( '%' . $searchterm . '%' );
    $where   = "
      (
        r.primarymetadatacache LIKE $searchterm OR
        r.additionalcache LIKE $searchterm
      ) AND
      r.organizationid = '$organizationid'
    ";
    
    $query   = "
      SELECT
      (
        SELECT
          COUNT(*)
        FROM channels
        WHERE
          ispublic = 1 AND 
          numberofrecordings > 0 AND
          (
            title         LIKE $searchterm OR
            subtitle      LIKE $searchterm OR
            ordinalnumber LIKE $searchterm OR
            description   LIKE $searchterm OR
            url           LIKE $searchterm
          ) AND
          isliveevent = 0 AND
          organizationid = '$organizationid'
      )
      +
      (
        SELECT COUNT(*) FROM
        (
          " . self::getUnionSelect( $user, 'r.id', 'recordings AS r', $where ) . "
        ) AS subcount
      ) AS count
    ";
    
    return $this->db->getOne( $query );
    
  }
  
  public function getSearchAllArray( $user, $organizationid, $searchterm, $start, $limit, $order ) {
    
    $searchterm  = str_replace( ' ', '%', $searchterm );
    $searchterm  = $this->db->qstr( '%' . $searchterm . '%' );
    $select = "
      'recording' AS type,
      (
        1 +
        IF( r.title LIKE $searchterm, 2, 0 ) +
        IF( r.subtitle LIKE $searchterm, 1, 0 ) +
        IF( r.description LIKE $searchterm, 1, 0 ) +
        IF( r.primarymetadatacache LIKE $searchterm, 1, 0 )
      ) AS relevancy,
      r.id,
      '1' AS parentid,
      r.userid,
      r.organizationid,
      r.title,
      r.subtitle,
      r.description,
      '' AS url,
      r.indexphotofilename,
      '0' AS channeltypeid,
      r.recordedtimestamp,
      r.numberofviews,
      r.rating,
      '0' AS numberofrecordings
    ";
    $where  = "
      (
        r.primarymetadatacache LIKE $searchterm OR
        r.additionalcache LIKE $searchterm
      ) AND
      r.organizationid = '$organizationid'
    ";
    
    $query   = "
      (
        SELECT
          'channel' AS type,
          (
            2 +
            IF( title LIKE $searchterm, 2, 0 ) +
            IF( subtitle LIKE $searchterm, 1, 0 ) +
            IF( description LIKE $searchterm, 1, 0 )
          ) AS relevancy,
          id,
          parentid,
          userid,
          organizationid,
          title,
          subtitle,
          description,
          url,
          indexphotofilename,
          channeltypeid,
          starttimestamp AS recordedtimestamp,
          '0' AS numberofviews,
          '0' AS rating,
          numberofrecordings
        FROM channels
        WHERE
          ispublic = 1 AND 
          numberofrecordings > 0 AND
          (
            title         LIKE $searchterm OR
            subtitle      LIKE $searchterm OR
            ordinalnumber LIKE $searchterm OR
            description   LIKE $searchterm OR
            url           LIKE $searchterm
          ) AND
          isliveevent = 0 AND
          organizationid = '$organizationid'
      ) UNION ALL
      " . self::getUnionSelect( $user, $select, 'recordings AS r', $where ) . "
      ORDER BY $order
    ";
    
    if ( $start !== null )
      $query .= 'LIMIT ' . $start . ', ' . $limit;
    
    return $this->db->getArray( $query );
    
  }
  
  public function hasSubtitle() {
    
    $this->ensureID();
    return $this->db->getOne("
      SELECT COUNT(*)
      FROM subtitles
      WHERE recordingid = '" . $this->id . "'
    ");
    
  }
  
  public function getRecordingsCount( $where ) {
    
    return $this->db->getOne("
      SELECT COUNT(*)
      FROM recordings AS r
      WHERE
        ( $where ) AND
        " . self::getPublicRecordingWhere('r.') . "
      LIMIT 1
    ");
    
  }
  
  public function getRecordingsWithUsers( $start, $limit, $where, $orderby ){
    
    return $this->db->getArray("
      SELECT
        r.id,
        r.title,
        r.subtitle,
        r.indexphotofilename,
        r.masterlength,
        r.numberofviews,
        u.id AS userid,
        u.nickname,
        u.nameformat,
        u.nameprefix,
        u.namefirst,
        u.namelast
      FROM
        recordings AS r,
        users AS u
      WHERE 
        ( $where ) AND
        u.id = r.userid AND
        " . self::getPublicRecordingWhere('r.') . "
      ORDER BY $orderby
      LIMIT $start, $limit
    ");
    
  }
  
  public function getAttachments( $publiconly = true ) {
    
    $this->ensureObjectLoaded();
    $where = array(
      "recordingid = '" . $this->id . "'",
      "status <> 'markedfordeletion'",
    );
    
    if ( $publiconly ) {
      
      $where[] = "status = 'onstorage'";
      $where[] = "isdownloadable = '1'";
      
    }
    
    $where = implode(' AND ', $where );
    return $this->db->getArray("
      SELECT *
      FROM attached_documents
      WHERE $where
      ORDER BY title
    ");
    
  }
  
  public function linkContributor( $data ) {
    
    $this->ensureID();
    $this->db->query("
      INSERT INTO contributors_roles (organizationid, contributorid, recordingid )
      VALUES ('" . $data['organizationid'] . "', '" . $data['contributorid'] . "', '" . $this->id . "')
    ");
    
  }
  
  public function clearDefaultSubtitle() {
    
    $this->ensureID();
    $this->db->query("
      UPDATE subtitles
      SET isdefault = '0'
      WHERE recordingid = '" . $this->id . "'
    ");
    
  }
  
  public function addToChannel( $channelid, $user ) {
    
    $this->ensureID();
    $existingid = $this->db->getOne("
      SELECT id
      FROM channels_recordings
      WHERE
        channelid   = '$channelid' AND
        recordingid = '" . $this->id . "' AND
        userid      = '" . $user['id'] . "'
    ");
    
    if ( $existingid )
      return false;
    else
      $this->db->query("
        INSERT INTO channels_recordings (channelid, recordingid, userid)
        VALUES ('$channelid', '" . $this->id . "', '" . $user['id'] . "')
      ");
      
    $this->updateChannelIndexPhotos();
    $this->updateCategoryCounters();

    return true;
    
  }
  
  public function removeFromChannel( $channelid, $user ) {
    
    $this->ensureID();
    $this->db->query("
      DELETE FROM channels_recordings
      WHERE
        channelid   = '$channelid' AND
        recordingid = '" . $this->id . "' AND
        userid      = '" . $user['id'] . "'
    ");
    $this->updateChannelIndexPhotos();
    $this->updateCategoryCounters();
    
  }
  
}
