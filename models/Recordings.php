<?php
namespace Model;

class InvalidFileTypeException extends \Exception {}
class InvalidLengthException extends \Exception {}
class InvalidVideoResolutionException extends \Exception {}

class Recordings extends \Springboard\Model {
  
  public function updateFulltextCache( $updatemetadata = false ) {
    
    $this->ensureObjectLoaded();
    $values = array(); // TODO assembleCaches
    
    if ( $updatemetadata )
      $values['metadataupdatedtimestamp'] = date('Y-m-d H:i:s');
    
    $this->updateRow( $values );
    
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