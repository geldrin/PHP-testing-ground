<?php
namespace Model;

class InvalidFileTypeException extends \Exception {}
class InvalidLengthException extends \Exception {}
class InvalidVideoResolutionException extends \Exception {}

class Recordings extends \Springboard\Model {
  
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
    
    $contributors = $this->getContributorsWithRoles();
    
    $contributornames = array();
    
    if ( !empty( $contributors ) ) {
      
      $jobObj = getObject('contributors_jobs');
      include_once( $this->bootstrap->config['smartypluginpath'] . 'modifier.nameformat.php');
      include_once( $this->bootstrap->config['smartypluginpath'] . 'modifier.title.php');
      foreach( $contributors as $contributor ) {

        if ( $contributor['contributorid'] ) {
          
          $contributornames[] = \smarty_modifier_nameformat( $contributor );
          
          $contributorjobs = $jobObj->getAllJobs( $contributor['contributorid'] );
          foreach( $contributorjobs as $job ) {
            
            $contributornames[] = $job['joboriginal'] . ' ' . $job['jobenglish'];
            $contributornames[] = $job['nameoriginal'];
            $contributornames[] = $job['nameenglish'];
            $contributornames[] = $job['nameshortoriginal'];
            $contributornames[] = $job['nameshortenglish'];
            
          }
          
        } else
          $contributornames[] = \smarty_modifier_title( $contributor, 'name' );
        
      }
      
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
  
  public function getContributorsWithRoles( $wantjobgroups = false ) {
    
    $contributors = $this->db->getArray("
      SELECT
        cr.id,
        cr.organizationid,
        cr.contributorid,
        cr.jobgroupid,
        org.nameenglish,
        org.nameoriginal,
        org.nameshortenglish,
        org.nameshortoriginal,
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
        LEFT JOIN contributors  AS c   ON cr.contributorid  = c.id,
        roles AS r,
        strings AS s
      WHERE
        cr.roleid = r.id AND
        r.name_stringid = s.translationof AND
        cr.recordingid = '" . $this->id . "' AND
        s.language = '" . \Springboard\Language::get() . "'
      ORDER BY
        cr.weight
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
  
  public function userHasAccess( $user ) {
    
    $this->ensureObjectLoaded();
    
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
  
  public function handleFile( $source, $handlefile = 'upload', $postfix = '' ) {
    
    $this->ensureObjectLoaded();
    
    if ( !$this->metadata )
      throw new \Exception('No metadata for the video found, please ->analyize() it beforehand!');
    
    $target =
      $this->bootstrap->config['uploadpath'] . 'recordings/' . $this->id .
      $postfix . '.' . $this->metadata['mastervideoextension']
    ;
    
    switch ( $handlefile ) {
      case 'copy':   $ret = @copy( $source, $target ); break;
      case 'upload': $ret = @move_uploaded_file( $source, $target ); break;
      case 'rename': $ret = @rename( $source, $target ); break;
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
    
    $this->db->execute("
      DELETE FROM recordings_categories
      WHERE recordingid = '" . $this->id . "'
    ");
    
  }
  
  public function addCategories( $categoryids ) {
    $this->insertMultipleIDs( $categoryids, 'recordings_categories', 'categoryid');
  }
  
  public function addGenres( $genreids ) {
    $this->insertMultipleIDs( $genreids, 'recordings_genres', 'genreid');
  }
  
  protected function insertMultipleIDs( $ids, $table, $field ) {
    
    $this->ensureID();
    
    if ( count( $ids ) > 50 )
      throw new \Exception("Tried inserting more than 50 items");
    
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
      DELETE FROM recordings_access
      WHERE recordingid = '" . $this->id . "'
    ");
    
  }
  
  public function restrictOrganizations( $organizationids ) {
    $this->insertMultipleIDs( $organizationids, 'recordings_access', 'organizationid');
  }
  
  public function restrictGroups( $groupids ) {
    $this->insertMultipleIDs( $groupids, 'recordings_access', 'groupid');
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
         isset( $user->id ) and
         (
           $this->row['userid'] == $user->id or
           (
             $user->iseditor and
             $user->organizationid == $this->row['organizationid']
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
      $now          = time();
      
      if ( $visiblefrom > $now or $visibleuntil < $now )
        $timefailed = true;
      
    }
    
    switch( $this->row['accesstype'] ) {
      
      case 'public':
        if ( $timefailed )
          return 'publicrestricted_timefailed';
        break;
      
      case 'registrations':
        
        if ( !isset( $user->id ) )
          return 'registrationrestricted';
        elseif ( $timefailed )
          return 'registrationrestricted_timefailed';
        
        break;
      
      case 'organizations':
      case 'groups':
        
        if ( $this->row['accesstype'] == 'groups')
          $error = 'grouprestricted';
        else
          $error = 'organizationrestricted';
        
        if ( !isset( $user->id ) )
          return $error;
        elseif ( $user->id == $this->row['userid'] )
          return true;
        elseif ( $user->iseditor and $user->organizationid == $this->row['organizationid'] )
          return true;
        
        $recordingid = "'" . $this->id . "'";
        $userid      = "'" . $user->id . "'";
        
        if ( $this->row['accesstype'] == 'organizations')
          $sql = "
            SELECT
              u.id
            FROM
              recordings_access AS ra,
              users AS u
            WHERE
              ra.recordingid = $recordingid AND
              ra.organizationid > 0 AND
              u.organizationid = ra.organizationid AND
              u.id = $userid
            LIMIT 1
          ";
        else
          $sql = "
            SELECT
              gm.userid
            FROM
              recordings_access AS ra,
              groups_members AS gm
            WHERE
              ra.recordingid = $recordingid AND
              ra.groupid > 0 AND
              gm.groupid = ra.groupid AND
              gm.userid = $userid
            LIMIT 1
          ";
        
        $row = $this->db->getRow( $sql );
        
        if ( empty( $row ) )
          return $error;
        elseif ( $timefailed )
          return $error . '_timefailed';
        
        break;
      
      default:
        throw new Exception('Unknown accesstype ' . $this->row['accesstype'] );
        break;
      
    }
    
    return true;
    
  }
  
  public function getPublicRecordingWhere( $prefix = '' ) {
    
    if ( strlen( $prefix ) )
      $prefix .= '.';
    
    return "
      {$prefix}status = 'onstorage' AND
      {$prefix}ispublished = 1 AND
      {$prefix}accesstype = 'public' AND
      (
        {$prefix}visiblefrom IS NULL OR
        {$prefix}visibleuntil IS NULL OR
        (
          {$prefix}visiblefrom  <= NOW() AND
          {$prefix}visibleuntil >= NOW()
        )
      )
    ";
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
        u.nickname
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
        r.titleoriginal,
        r.titleenglish,
        r.subtitleoriginal,
        r.subtitleenglish,
        r.indexphotofilename,
        r.masterlength,
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
        r.status = 'onstorage' AND
        r.ispublished = '1' AND
        r.accesstype = 'public' AND
        (
          r.visiblefrom IS NULL OR
          r.visibleuntil IS NULL OR
          (
            r.visiblefrom  <= NOW() AND
            r.visibleuntil >= NOW()
          )
        )
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
        r.titleoriginal,
        r.titleenglish,
        r.subtitleoriginal,
        r.subtitleenglish,
        r.indexphotofilename,
        r.masterlength,
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
        r.status = 'onstorage' AND
        r.ispublished = '1' AND
        r.accesstype = 'public' AND
        (
          r.visiblefrom IS NULL OR
          r.visibleuntil IS NULL OR
          (
            r.visiblefrom  <= NOW() AND
            r.visibleuntil >= NOW()
          )
        )
      ORDER BY RAND()
      LIMIT $limit
    ");
    
    $return = array();
    foreach( $rs as $recording )
      $return[ $recording['id'] ] = $recording;
    
    if ( count( $return ) < $limit and !$dontrecurse ) {
      
      $parentids  = array();
      $channelObj = getObject('channels');
      foreach( $channelids as $channelid ) {
        
        $parents   = $channelObj->findParents( $channelid );
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
        r.titleoriginal,
        r.titleenglish,
        r.subtitleoriginal,
        r.subtitleenglish,
        r.indexphotofilename,
        r.masterlength,
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
        r.id != '" . $this->id . "' AND
        r.status = 'onstorage' AND
        r.accesstype = 'public' AND
        r.ispublished = '1' AND
        (
          r.visiblefrom IS NULL OR
          r.visibleuntil IS NULL OR
          (
            r.visiblefrom  <= NOW() AND
            r.visibleuntil >= NOW()
          )
        )
      ORDER BY RAND()
      LIMIT $limit
    ");
    
    $return = array();
    foreach( $rs as $recording )
      $return[ $recording['id'] ] = $recording;
    
    return $return;
    
  }
  
  public function getRelatedVideos() {
    
    $this->ensureObjectLoaded();
    
    $return = array();
    
    if ( count( $return ) < NUMBER_OF_RELATED_VIDEOS )
      $return = $return + $this->getRelatedVideosByChannel( NUMBER_OF_RELATED_VIDEOS - count( $return ) );
    
    if ( count( $return ) < NUMBER_OF_RELATED_VIDEOS )
      $return = $return + $this->getRelatedVideosByKeywords( NUMBER_OF_RELATED_VIDEOS - count( $return ) );
    
    if ( count( $return ) < NUMBER_OF_RELATED_VIDEOS )
      $return = $return + $this->getRelatedVideosRandom( NUMBER_OF_RELATED_VIDEOS - count( $return ) );
    
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
  
  public function getCategoryRecordingsCount( $categoryids ) {
    
    return $this->db->getOne("
      SELECT DISTINCT COUNT(r.id)
      FROM
        recordings_categories AS rc,
        recordings AS r
      WHERE
        rc.categoryid IN ('" . implode("', '", $categoryids ) . "') AND
        r.id = rc.recordingid AND
        " . $this->getPublicRecordingWhere()
    );
    
  }
  
  public function getCategoryRecordings( $categoryids, $start = false, $limit = false, $order = false ) {
    
    return $this->db->getArray("
      SELECT DISTINCT
        r.*
      FROM
        recordings_categories AS rc,
        recordings AS r
      WHERE
        rc.categoryid IN ('" . implode("', '", $categoryids ) . "') AND
        r.id = rc.recordingid AND
        " . $this->getPublicRecordingWhere() .
      ( strlen( $order ) ? 'ORDER BY ' . $order : '' ) . " " .
      ( is_numeric( $start ) ? 'LIMIT ' . $start . ', ' . $limit : "" )
    );
    
  }
  
  public function getFlashData() {
    
    $this->ensureObjectLoaded();
    include_once( $this->bootstrap->config['templatepath'] . 'Plugins/modifier.indexphoto.php' );
    
    $baseuri          = ( SSL? 'https://': 'http://' ) . $this->bootstrap->config['baseuri'];
    $recordingbaseuri = $baseuri . \Springboard\Language::get() . '/recordings/';
    
    $data = array(
      'language'  => \Springboard\Language::get(),
      'media'     => array(
        'servers'       => array(
          $this->bootstrap->config['wowza']['rtmpurl'],
          $this->bootstrap->config['wowza']['rtmpturl'],
        ),
      ),
      'share'     => array(
        'recordingURL'  =>
          $recordingbaseuri . 'details/' . $this->id . ',' .
          \Springboard\Filesystem::filenameize( $this->row['title'] )
        ,
        'quickEmbed'    => $baseuri . 'embed/' . $this->id . '.js',
      ),
      'track'     => array(
        'firstPlay'     => $recordingbaseuri . 'track/' . $this->id,
      ),
      'recording' => array(
        'page'          => $baseuri,
        'duration'      => $this->row['masterlength'],
        'title'         => $this->row['title'],
        'subtitle'      => (string)$this->row['subtitle'],
        'description'   => (string)$this->row['description'],
        'image'         => \smarty_modifier_indexphoto( $this->row, 'player' ),
      ),
    );
    
    $data['media']['streams'] = array( $this->getMediaUrl('default', false ) );
    
    if ( $this->row['videoreshq'] )
      $data['media']['streams'][] = $this->getMediaUrl('default', true );
    
    if ( $this->row['contentstatus'] == 'onstorage' ) {
      
      $data['media']['secondaryStreams'] = array( $this->getMediaUrl('content', false ) );
      
      if ( $this->row['contentvideoreshq'] )
        $data['media']['secondaryStreams'][] = $this->getMediaUrl('content', true );
      
    }
    
    return $data;
    
  }
  
  public function getMediaUrl( $type, $highquality ) {
    
    $this->ensureObjectLoaded();
    
    $config    = $this->bootstrap->config;
    $extension = 'mp4';
    $postfix   = '_lq';
    $host      = '';
    $isaudio   = $this->row['mastermediatype'] == 'audio';
    
    if ( $highquality and !$isaudio )
      $postfix = '_hq';
    
    if ( $isaudio ) {
      
      $postfix   = '';
      $extension = 'mp3';
      
    }
    
    switch( $type ) {
      
      case 'mobilehttp':
        //http://stream.videotorium.hu:1935/vtorium/_definst_/mp4:671/2671/2671_2608_mobile.mp4/playlist.m3u8
        $host        = $config['wowza']['httpurl'];
        $sprintfterm = '%3$s:%s/%s_mobile' . $postfix . '.%s/playlist.m3u8';
        
        break;
      
      case 'mobilertsp':
        //rtsp://stream.videotorium.hu:1935/vtorium/_definst_/mp4:671/2671/2671_2608_mobile.mp4
        $host        = $config['wowza']['rtspurl'];
        $sprintfterm = '%3$s:%s/%s_mobile' . $postfix . '.%s';
        
        break;
      
      case 'content':
        
        $sprintfterm = '%s/%s_content' . $postfix . '.%s';
        break;
      
      default:
        
        $sprintfterm = '%s/%s_video' . $postfix . '.%s';
        break;
      
    }
    
    return $host . sprintf( $sprintfterm,
      \Springboard\Filesystem::getTreeDir( $this->id ),
      $this->id,
      $extension
    );
    
  }
  
}
/*

NEM
/api/json/model/recordings/getRow/12
/api/json/model/recordings/upload/title/filepath
/api/json/control/recordings/listing/0/50/timestamp_desc


IGEN
/api?format=json&layer=model&module=recordings&method=getRow&id=12
/api?format=json&layer=model&module=recordings&method=upload&title=title&filepath=filepath


*/