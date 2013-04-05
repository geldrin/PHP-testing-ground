<?php
namespace Model;

class InvalidFileTypeException extends \Exception {}
class InvalidLengthException extends \Exception {}
class InvalidVideoResolutionException extends \Exception {}
class HandleFileException extends \Exception {}

class Recordings extends \Springboard\Model {
  public $apisignature = array(
    'getRow' => array(
      'where' => array(
        'type' => 'string'
      ),
    ),
  );
  
  protected $searchadvancedwhere;
  
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
      
      include_once( $this->bootstrap->config['templatepath'] . 'Plugins/modifier.nameformat.php');
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
        cr.contributorid,
        c.id AS contributorid,
        c.nameprefix,
        c.namefirst,
        c.namelast,
        c.nameformat,
        c.namealias,
        s.value AS rolename
      FROM
        contributors_roles AS cr
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
    /*
    $slides = $this->db->getCol("
      SELECT slidecache
      FROM slides_chapters
      WHERE
        recordingid = '" . $this->id . "' AND
        timing IS NOT NULL
    ");
    
    $cache = implode( ' ', $slides );*/
    $cache = '';
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
           !strlen( $channel->row['indexphotofilename'] ) and
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
  
  public function userHasAccess( $user, $secure = null, $mobile = false ) {
    
    $this->ensureObjectLoaded();
    
    if ( $secure !== null and $this->row['issecurestreamingforced'] != $secure )
      return 'securerestricted';
    
    $bystatus   = $this->isAccessibleByStatus( $user, $mobile );
    $bysettings = $this->isAccessibleBySettings( $user );
    
    if ( $bystatus === true and $bysettings === true )
      return true;
    
    if ( $bystatus !== true )
      return $bystatus;
    else
      return $bysettings;
    
  }
  
  public function insertUploadingRecording( $userid, $organizationid, $languageid, $title, $sourceip, $isintrooutro = 0 ) {
    
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
      'isintrooutro'    => $isintrooutro,
      'timestamp'       => date('Y-m-d H:i:s'),
      'recordedtimestamp' => date('Y-m-d H:i:s'),
      'metadataupdatedtimestamp' => date('Y-m-d H:i:s'),
    ) + $this->metadata;
    
    if ( $isintrooutro ) {
      
      $recording['ispublished'] = 1;
      
    }
    
    return $this->insert( $recording );
    
  }
  
  public function handleFile( $source, $handlefile = 'upload', $postfix = null ) {
    
    $this->ensureObjectLoaded();
    
    if ( !$this->metadata )
      throw new HandleFileException('No metadata for the video found, please ->analyize() it beforehand!');
    
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
      default: throw new HandleFileException('unsupported operation: ' . $handlefile ); break;
    }
    
    if ( !$ret )
      throw new HandleFileException( $handlefile . ' failed from ' . $source . ' to ' . $target );
    
    return $ret;
    
  }
  
  public function upload( &$info ) {
    
    if ( !isset( $info['filepath'] ) or !isset( $info['filename'] ) or !isset( $info['handlefile'] ) )
      throw new \Exception("No filepath and filename passed!");
    
    $iscontent = ( isset( $info['iscontent'] ) and $info['iscontent'] );
    $postfix   = $iscontent? '_content': null;
    
    if ( $iscontent ) {
      
      $this->ensureObjectLoaded();
      $statusfield = 'contentstatus';
      
      if ( !$this->canUploadContentVideo() )
        throw new \Exception(
          'Uploading a content video is denied at this point: ' .
          var_export( $this->row, true )
        );
      
      $this->analyze( $info['filepath'], $info['filename'] );
      $this->addContentRecording(
        null,
        $this->bootstrap->config['node_sourceip']
      );
      
    } else {
      
      if ( !isset( $info['user'] ) or !$info['user']['id'] or !isset( $info['language'] ) )
        throw new \Exception('No language or user passed!');
      
      $isintrooutro = 0;
      if (
           isset( $info['isintrooutro'] ) and
           $info['isintrooutro'] and
           (
             $info['user']['iseditor'] or $info['user']['isadmin'] or
             $info['user']['isclientadmin']
           )
         )
        $isintrooutro = 1;
      
      $statusfield = 'masterstatus';
      $this->analyze( $info['filepath'], $info['filename'] );
      $this->insertUploadingRecording(
        $info['user']['id'],
        $info['user']['organizationid'],
        $info['language'],
        $info['filename'],
        $this->bootstrap->config['node_sourceip'],
        $isintrooutro
      );
      
    }
  
    try {
      
      $this->handleFile( $info['filepath'], $info['handlefile'], $postfix );
      
    } catch( \Exception $e ) {
      
      $this->updateRow( array(
          $statusfield => 'failedmovinguploadedfile',
        )
      );
      
      throw $e;
      
    }
    
    if ( $iscontent ) {
      
      $this->markContentRecordingUploaded();
      
    } else {
      
      $this->updateRow( array(
          'masterstatus' => 'uploaded',
          'status'       => 'uploaded',
        )
      );
      
    }
    
    return true;
    
  }
  
  protected function mediainfoDurationToSeconds( $duration ) {
    
    $duration = strval( $duration );
    if (
         !preg_match(
           '/^(?:(?<hour>\d+)h)? ?(?:(?<min>\d+)mn)? ?(?:(?<sec>\d+)s)? ?(?:(?<milisec>\d+)ms)?$/',
           $duration,
           $matches
         )
       )
      throw new \Exception("Unable to parse mediainfo duration!");
    
    $ret = 0;
    if ( isset( $matches['hour'] ) )
      $ret += $matches['hour'] * 60 * 60;
    
    if ( isset( $matches['min'] ) )
      $ret += $matches['min'] * 60;
    
    if ( isset( $matches['sec'] ) )
      $ret += $matches['sec'];
    
    if ( isset( $matches['milisec'] ) )
      $ret += $matches['milisec'] / 1000;
    
    return $ret;
    
  }
  
  protected function getMediainfoNumericValue( $elem, $isfloat = false, $scale = 1 ) {
    
    $elem = strval( $elem );
    if ( !$elem )
      return null;
    
    $elem = str_replace( ' ', '', $elem );
    if ( $isfloat )
      return floatval( $elem ) * $scale;
    else
      return intval( $elem ) * $scale;
    
  }
  
  public function analyze( $filename, $originalfilename = null ) {
    
    $config = $this->bootstrap->config;
    
    if ( !$originalfilename )
      $originalfilename = $filename;
    
    $cmd = sprintf( $config['mediainfo_identify'], escapeshellarg( $filename ) );
    exec( $cmd, $output, $return );
    $output = implode("\n", $output );
    
    if ( $return )
      throw new \Exception('Mediainfo returned non-zero exit code, output was: ' . $output, $return );
    
    if ( $this->bootstrap->debug )
      var_dump( $output );
    
    $xml     = new \SimpleXMLElement( $output );
    $general = current( $xml->xpath('File/track[@type="General"][1]') );
    $video   = current( $xml->xpath('File/track[@type="Video"][1]') );
    $audio   = current( $xml->xpath('File/track[@type="Audio"][1]') );
    
    if ( !$general or ( !$video and !$audio ) )
      throw new InvalidFileTypeException('Unrecognized file, output was: ' . $output );
    
    if ( $video and $audio )
      $mediatype = 'video';
    elseif ( !$video and $audio )
      $mediatype = 'audio';
    elseif ( $video and !$audio )
      $mediatype = 'videoonly';
    else
      throw new \Exception("Cannot happen wtf, output was: " . $output );
    
    $extension         = \Springboard\Filesystem::getExtension( $originalfilename )?: null;
    $videocontainer    = $general->Format?: $extension;
    $videostreamid     = null;
    $videofps          = null;
    $videocodec        = null;
    $videores          = null;
    $videodar          = null;
    $videobitrate      = null;
    $videobitratemode  = null;
    $videoisinterlaced = null; // nem adunk neki erteket sose, torolni kene?
    $videolength       = null;
    $audiostreamid     = null;
    $audiocodec        = null;
    $audiochannels     = null;
    $audiomode         = null;
    $audioquality      = null;
    $audiofreq         = null;
    $audiobitrate      = null;
    
    if ( $general->Duration )
      $videolength = $this->mediainfoDurationToSeconds( $general->Duration );
    
    if ( $videolength <= $config['recordings_seconds_minlength'] )
      throw new InvalidLengthException('Recording length was less than ' . $config['recordings_seconds_minlength'] );
    
    if ( $video ) {
      
      if ( $video->Duration )
        $videolength = $this->mediainfoDurationToSeconds( $video->Duration );
      else
        throw new InvalidLengthException('Length not found for the media, output was ' . $output );
      
      $videostreamid  = $this->getMediainfoNumericValue( $video->ID );
      $videofps       = $this->getMediainfoNumericValue( $video->Frame_rate, true );
      $videocodec     = $video->Format;
      if ( $video->Format_Info )
        $videocodec  .= ' (' . $video->Format_Info . ')';
      if ( $video->Format_profile )
        $videocodec  .= ' / ' . $video->Format_profile;
      
      if ( $video->Bit_rate_mode == 'Constant' )
        $videobitratemode = 'cbr';
      else
        $videobitratemode = 'vbr';
      
      if ( $video->Width and $video->Height ) {
        
        $videores = sprintf(
          '%sx%s',
          $this->getMediainfoNumericValue( $video->Width ),
          $this->getMediainfoNumericValue( $video->Height )
        );
        
        if ( $video->Display_aspect_ratio )
          $videodar = $video->Display_aspect_ratio;
        
        $videobitrate = $video->Bit_rate ?: $general->Overall_bit_rate;
        
        if ( $videobitrate and stripos( $videobitrate, 'kbps' ) !== false )
          $scale = 1000;
        elseif ( $videobitrate and stripos( $videobitrate, 'mbps' ) !== false )
          $scale = 1000000;
        else
          $scale = 1;
        
        $videobitrate = $this->getMediainfoNumericValue( $videobitrate, false, $scale );
        
      }
      
    }
    
    if ( $audio ) {
      
      $audiocodec    = $audio->Format;
      if ( $audio->Format_Info )
        $audiocodec .= ' ( ' . $audio->Format_Info . ' ) ';
      if ( $audio->Format_profile )
        $audiocodec .= ' / ' . $audio->Format_profile;
      
      $audiostreamid = $this->getMediainfoNumericValue( $audio->ID );
      $audiofreq     = $this->getMediainfoNumericValue( $audio->Sampling_rate, true, 1000 );
      $audiobitrate  = $this->getMediainfoNumericValue( $audio->Bit_rate, false, 1000 );
      $audiochannels = $this->getMediainfoNumericValue( $audio->Channel_s_ );
      
      if ( $audio->Bit_rate_mode == 'Constant' )
        $audiomode   = 'cbr';
      elseif ( $audio->Bit_rate_mode == 'Variable' )
        $audiomode   = 'vbr';
      
      if ( $audio->Compression_mode == 'Lossy' )
        $audioquality = 'lossy';
      elseif ( $audio->Compression_mode == 'Lossless' )
        $audioquality = 'lossless';
      
      if ( !$videolength )
        $videolength = $this->mediainfoDurationToSeconds( $audio->Duration );
      
    }
    
    $info = array(
      'mastermediatype'            => $mediatype,
      'mastervideostreamselected'  => $videostreamid,
      'mastervideoextension'       => $extension,
      'mastervideocontainerformat' => $videocontainer,
      'mastervideofilename'        => basename($originalfilename),
      'mastervideofps'             => $videofps,
      'mastervideocodec'           => $videocodec,
      'mastervideores'             => $videores,
      'mastervideodar'             => $videodar,
      'mastervideobitrate'         => $videobitrate,
      'mastervideobitratemode'     => $videobitratemode,
      'mastervideoisinterlaced'    => $videoisinterlaced,
      'masterlength'               => $videolength,
      'masteraudiostreamselected'  => $audiostreamid,
      'masteraudiocodec'           => $audiocodec,
      'masteraudiochannels'        => $audiochannels,
      'masteraudiobitratemode'     => $audiomode,
      'masteraudioquality'         => $audioquality,
      'masteraudiofreq'            => $audiofreq,
      'masteraudiobitrate'         => $audiobitrate,
    );
    
    foreach( $info as $key => $value )
      $info[ $key ] = gettype( $value ) == 'object' ? strval( $value ): $value;
    
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
  
  public function isAccessibleByStatus( $user, $mobile = false ) {
    
    $this->ensureObjectLoaded();
    $statuses = array(
      'deleted'           => 'recordingdeleted',
      'markedfordeletion' => 'recordingdeleted',
    );
    
    if ( in_array( $this->row['status'], array_keys( $statuses ) ) )
      return $statuses[ $this->row['status'] ];
    
    if (
         ( !$mobile and $this->row['status'] != 'onstorage' ) or
         ( $mobile and $this->row['mobilestatus'] != 'onstorage' )
       ) {
      
      $status = $mobile? $this->row['mobilestatus']: $this->row['status'];
      if ( preg_match( '/^failed.+/i', $status ) )
        return 'recordingerrorconverting';
      else
        return $mobile? 'mobileunavailable': 'recordingconverting';
      
    }
    
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
        elseif ( ( $user['iseditor'] or $user['isclientadmin'] ) and $user['organizationid'] == $this->row['organizationid'] )
          return true;
        
        $recordingid = "'" . $this->id . "'";
        $userid      = "'" . $user['id'] . "'";
        
        if ( $this->row['accesstype'] == 'departments')
          $sql = "
            SELECT
              ud.id
            FROM
              access AS a,
              users_departments AS ud
            WHERE
              a.recordingid   = $recordingid AND
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
  
  public static function getPublicRecordingWhere( $prefix = '', $isintrooutro = '0' ) {
    
    return "
      {$prefix}status       = 'onstorage' AND
      {$prefix}ispublished  = '1' AND
      {$prefix}isintrooutro = '$isintrooutro' AND
      {$prefix}accesstype   = 'public' AND
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
  
  public static function getUnionSelect( $user, $select = null, $from = null, $where = null, $isintrooutro = '0' ) {
    
    if ( $select === null )
      $select = 'r.*';
    if ( $from === null )
      $from = 'recordings AS r';
    
    if ( !isset( $user['id'] ) ) {
      
      $publicwhere = self::getPublicRecordingWhere('r.', $isintrooutro );
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
    
    $isadmin = false;
    if ( $user['isadmin'] or $user['isclientadmin'] or $user['iseditor'] )
      $isadmin = true;
    
    $generalwhere = "
      r.status       = 'onstorage' AND
      r.isintrooutro = '$isintrooutro' AND
      (
        r.ispublished = '1'" . ( $isadmin? '': " OR
        r.userid = '" . $user['id'] . "'" ) . "
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
          $generalwhere " . ( $isadmin? '': " AND
          r.accesstype IN('public', 'registrations')" ) . "
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
          users_departments AS ud
        WHERE
          $where
          $generalwhere AND
          r.accesstype   = 'departments' AND
          a.recordingid  = r.id AND
          a.departmentid = ud.departmentid AND
          ud.userid      = '" . $user['id'] . "'
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
  
  public function getRelatedVideosByKeywords( $limit, $user, $organizationid ){
    
    $this->ensureObjectLoaded();
    if ( !strlen( trim( $this->row['keywords'] ) ) )
      return array();
    
    $keywords     = explode(',', $this->row['keywords'] );
    $keywordwhere = array();
    
    foreach( $keywords as $key => $value ) {
      
      $value = trim( $value );
      if ( !$value )
        continue;
      
      $keyword = $this->db->qstr( '%' . $value . '%' );
      $keywordwhere[] = 'r.keywords LIKE ' . $keyword;
      
    }
    
    $select = "
      r.id AS arraykey,
      r.id,
      r.title,
      r.subtitle,
      r.indexphotofilename,
      r.masterlength,
      r.numberofviews,
      usr.id AS userid,
      usr.nickname,
      usr.nameformat,
      usr.nameprefix,
      usr.namefirst,
      usr.namelast
    ";
    
    $from = "
      recordings AS r,
      users AS usr"
    ;
    
    $where = "
      usr.id = r.userid AND
      r.id <> '" . $this->id . "' AND
      r.organizationid = '$organizationid'"
    ;
    
    if ( !empty( $keywordwhere ) )
      $where .= " AND ( " . implode(' OR ', $keywordwhere ) . " )";
    
    return $this->db->getAssoc(
      self::getUnionSelect( $user, $select, $from, $where ) . "
      ORDER BY RAND()
      LIMIT $limit
    ");
    
  }
  
  public function getRelatedVideosByChannel( $limit, $user, $organizationid, $channelids = null ) {
    
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
    
    $select = "
      r.id AS arraykey,
      r.id,
      r.title,
      r.subtitle,
      r.indexphotofilename,
      r.masterlength,
      r.numberofviews,
      usr.id AS userid,
      usr.nickname,
      usr.nameformat,
      usr.nameprefix,
      usr.namefirst,
      usr.namelast
    ";
    
    $from = "
      recordings AS r,
      users AS usr,
      channels_recordings AS cr"
    ;
    
    $where = "
      cr.channelid IN('" . implode("', '", $channelids ) . "') AND
      r.id = cr.recordingid AND
      usr.id = r.userid AND
      r.id <> '" . $this->id . "' AND
      r.organizationid = '$organizationid'"
    ;
    
    $return = $this->db->getAssoc(
      self::getUnionSelect( $user, $select, $from, $where ) . "
      ORDER BY RAND()
      LIMIT $limit
    ");
    
    if ( count( $return ) < $limit and !$dontrecurse ) {
      
      $parentids    = array();
      $channelModel = $this->bootstrap->getModel('channels');
      foreach( $channelids as $channelid ) {
        
        $parents   = $channelModel->findParents( $channelid );
        $parents[] = $channelid;
        $parentids = array_merge( $parentids, $parents );
        
      }
      
      $parentids = array_unique( $parentids );
      $return = $return + $this->getRelatedVideosByChannel( $limit - count( $return ), $user, $organizationid, $parentids );
      
    }
    
    return $return;
    
  }
  
  public function getRelatedVideosRandom( $limit, $user, $organizationid ) {
    
    $this->ensureID();
    
    $select = "
      r.id AS arraykey,
      r.id,
      r.title,
      r.subtitle,
      r.indexphotofilename,
      r.masterlength,
      r.numberofviews,
      usr.id AS userid,
      usr.nickname,
      usr.nameformat,
      usr.nameprefix,
      usr.namefirst,
      usr.namelast
    ";
    
    $from = "
      recordings AS r,
      users AS usr"
    ;
    
    $where = "
      usr.id = r.userid AND
      r.id <> '" . $this->id . "' AND
      r.organizationid = '$organizationid'"
    ;
    
    return $this->db->getAssoc(
      self::getUnionSelect( $user, $select, $from, $where ) . "
      ORDER BY RAND()
      LIMIT $limit
    ");
    
    
  }
  
  public function getRelatedVideos( $count, $user, $organizationid ) {
    
    $this->ensureObjectLoaded();
    
    $return = array();
    
    if ( count( $return ) < $count )
      $return = $return + $this->getRelatedVideosByChannel( $count - count( $return ), $user, $organizationid );
    
    if ( count( $return ) < $count )
      $return = $return + $this->getRelatedVideosByKeywords( $count - count( $return ), $user, $organizationid );
    
    if ( count( $return ) < $count )
      $return = $return + $this->getRelatedVideosRandom( $count - count( $return ), $user, $organizationid );
    
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
    
    $select = "
      r.*,
      c.title AS channeltitle,
      c.id AS channelid,
      c.weight AS channelweight,
      cr.weight AS channelrecordingweight
    ";
    
    $from = "
      channels AS c,
      channels_recordings AS cr,
      recordings AS r"
    ;
    
    $where =
      "cr.channelid IN ('" . implode("', '", $channelids ) . "') AND
      c.id = cr.channelid AND
      r.id = cr.recordingid"
    ;
    
    return $this->db->getArray("
      " . self::getUnionSelect( $user, $select, $from, $where ) .
      ( strlen( $order ) ? 'ORDER BY ' . $order : '' ) . " " .
      ( is_numeric( $start ) ? 'LIMIT ' . $start . ', ' . $limit : "" )
    );
    
  }
  
  public function addPresenters( $withjobs = true, $organizationid ) {
    
    $this->ensureObjectLoaded();
    $this->row['presenters'] = $this->db->getArray("
      SELECT
        c.*,
        cr.roleid,
        cr.recordingid,
        cr.contributorid,
        cr.jobgroupid
      FROM
        contributors AS c,
        contributors_roles AS cr
      WHERE
        c.id = cr.contributorid AND
        cr.recordingid = '" . $this->id . "' AND
        cr.roleid IN( (SELECT r.id FROM roles AS r WHERE r.organizationid = '$organizationid' AND r.ispresenter = '1') )
      ORDER BY cr.weight
    ");
    
    if ( $withjobs )
      $this->row['presenters'] = $this->addJobsToContributors( $this->row['presenters'], $organizationid );
    
    return $this->row;
    
  }
  
  public function addPresentersToArray( &$array, $withjobs = true, $organizationid ) {
    
    $recordings = array();
    foreach( $array as $key => $recording ) {
      
      if ( isset( $recording['type'] ) and $recording['type'] != 'recording' )
        continue;
      
      // store the index of the recordings in an array keyed by the recordingid
      // so as not to re-index the array
      if ( isset( $recording['id'] ) )
        $recordings[ $recording['id'] ] = $key;
      
    }
    
    if ( empty( $recordings ) )
      return $array;
    
    $contributors = $this->db->getArray("
      SELECT
        c.*,
        cr.roleid,
        cr.recordingid,
        cr.contributorid,
        cr.jobgroupid
      FROM
        contributors AS c,
        contributors_roles AS cr
      WHERE
        c.id = cr.contributorid AND
        cr.recordingid IN('" . implode("', '", array_keys( $recordings ) ) . "') AND
        cr.roleid IN( (SELECT r.id FROM roles AS r WHERE r.organizationid = '$organizationid' AND r.ispresenter = '1') )
      ORDER BY cr.weight
    ");
    
    if ( $withjobs )
      $contributors = $this->addJobsToContributors( $contributors, $organizationid );
    
    foreach( $contributors as $contributor ) {
      
      $key = $recordings[ $contributor['recordingid'] ];
      if ( !isset( $array[ $key ]['presenters'] ) )
        $array[ $key ]['presenters'] = array();
      
      $array[ $key ]['presenters'][] = $contributor;
      
    }
    
    return $array;
    
  }
  
  public function addJobsToContributors( &$contributors, $organizationid ) {
    
    foreach( $contributors as $key => $contributor ) {
      
      $contributors[ $key ]['jobs'] = $this->db->getArray("
        SELECT
          cj.*,
          org.name,
          org.nameshort,
          org.url
        FROM contributors_jobs AS cj
        LEFT JOIN organizations AS org ON cj.organizationid = org.id
        WHERE
          cj.jobgroupid    = '" . $contributor['jobgroupid'] . "' AND
          cj.contributorid = '" . $contributor['contributorid'] . "'
      ");
      
    }
    
    return $contributors;
    
  }
  
  public function getChannelsForUser( $user, $channeltype = null ) {
    
    $this->ensureID();
    $channelModel = $this->bootstrap->getModel('channels');
    $where        = array();
    
    if ( $user['id'] )
      $where[] = "
        (
          c.userid         = '" . $user['id'] . "' AND
          c.organizationid = '" . $user['organizationid'] . "'
        )
      ";
    
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
    
    $data = array(
      'language'              => \Springboard\Language::get(),
      'user_pingURL'          => $info['BASE_URI'] . \Springboard\Language::get() . '/users/ping',
      'user_needPing'         => false,
      'media_servers'         => array(),
      'track_firstPlay'       => $recordingbaseuri . 'track/' . $this->id,
      'recording_title'       => $this->row['title'],
      'recording_subtitle'    => (string)$this->row['subtitle'],
      'recording_description' => (string)$this->row['description'],
      'recording_image'       => \smarty_modifier_indexphoto( $this->row, 'player', $info['STATIC_URI'] ),
      'user_checkWatching'    => (bool)@$info['member']['ispresencecheckforced'],
      'user_checkWatchingTimeInterval' => $info['organization']['presencechecktimeinterval'],
      'user_checkWatchingConfirmationTimeout' => $info['organization']['presencecheckconfirmationtime'],
    );
    
    if ( @$info['member'] and $info['member']['issingleloginenforced'] )
      $data['user_needPing'] = true;
    
    if ( $this->row['issecurestreamingforced'] ) {
      $data['media_servers'][] = $this->getWowzaUrl( 'secrtmpsurl', true, $domain, $sessionid );
      $data['media_servers'][] = $this->getWowzaUrl( 'secrtmpurl',  true, $domain, $sessionid );
      $data['media_servers'][] = $this->getWowzaUrl( 'secrtmpturl', true, $domain, $sessionid );
    } else {
      $data['media_servers'][] = $this->getWowzaUrl( 'rtmpurl',  true, $domain, $sessionid );
      $data['media_servers'][] = $this->getWowzaUrl( 'rtmpturl', true, $domain, $sessionid );
    }
    
    // default bal oldalon van a video, csak akkor allitsuk be ha kell
    if ( !$this->row['slideonright'] )
      $data['layout_videoOrientation'] = 'right';
    
    if ( $data['language'] != 'en' )
      $data['locale'] = $info['STATIC_URI'] . 'js/flash_locale_' . $data['language'] . '.json';
    
    $data['media_streams'] = array( $this->getMediaUrl('default', false, $domain ) );
    
    if ( $this->row['videoreshq'] )
      $data['media_streams'][] = $this->getMediaUrl('default', true, $domain );
    
    $data = $data + $this->getIntroOutroFlashdata( $domain );
    
    if ( $this->row['offsetstart'] )
      $data['timeline_virtualStart'] = $this->row['offsetstart'];
    
    if ( $this->row['offsetend'] )
      $data['timeline_virtualEnd'] = $this->row['offsetend'];
    
    if ( $this->row['contentstatus'] == 'onstorage' and !isset( $info['skipcontent'] ) ) {
      
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
    if ( !empty( $subtitles ) ) {
      
      $defaultsubtitle = $this->getDefaultSubtitleLanguage();
      if ( $defaultsubtitle ) {
        
        $data['subtitle_autoShow'] = true;
        $data['subtitle_default']  = $defaultsubtitle;
        
      }
      
      $data['subtitle_files'] = array();
      foreach( $subtitles as $subtitle ) {
        
        $data['subtitle_files'][ $subtitle['languagecode'] ] =
          $recordingbaseuri . 'getsubtitle/' . $subtitle['id']
        ;
        
      }
      
    }
    
    $relatedvideos = $this->getRelatedVideos(
      $this->bootstrap->config['relatedrecordingcount'],
      $info['member'],
      $info['organization']['id']
    );
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
  
  public function getIntroOutroFlashdata( $domain ) {
    
    $this->ensureObjectLoaded();
    if ( !$this->row['introrecordingid'] and !$this->row['outrorecordingid'] )
      return array();
    
    $ids     = array();
    $data    = array();
    $introid = 0;
    $outroid = 0;
    
    if ( $this->row['introrecordingid'] ) {
      
      $ids[]   = $this->row['introrecordingid'];
      $introid = $this->row['introrecordingid'];
      
    }
    
    if ( $this->row['outrorecordingid'] ) {
      
      $ids[]   = $this->row['outrorecordingid'];
      $outroid = $this->row['outrorecordingid'];
      
    }
    
    $highres = $this->db->getAssoc("
      SELECT id, videoreshq
      FROM recordings
      WHERE id IN('" . implode("', '", $ids ) . "')
    ");
    
    foreach( $ids as $id ) {
      
      if ( $introid == $id )
        $key = 'intro_streams';
      else
        $key = 'outro_streams';
      
      $data[ $key ] = array(
        $this->getMediaUrl('default', false, $domain, null, '', $id )
      );
      
      if ( isset( $highres[ $id ] ) and $highres[ $id ] )
        $data[ $key ][] =
          $this->getMediaUrl('default', true, $domain, null, '', $id )
        ;
      
    }
    
    return $data;
    
  }
  
  public function getStructuredFlashData( $info, $sessionid ) {
    
    $flashdata = $this->transformFlashData(
      $this->getFlashData( $info, $sessionid )
    );
    
    $flashdata['recommendatory'] = $flashdata['recommendatory']['string'];
    
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
      return rtrim( $url, '/' ) . $this->getAuthorizeSessionid( $domain, $sessionid );
      
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
  
  public function getMediaUrl( $type, $highquality, $domain = null, $sessionid = null, $host = '', $id = null ) {
    
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
    
    if ( $id === null )
      $id = $this->id;
    
    return $host . sprintf( $sprintfterm,
      \Springboard\Filesystem::getTreeDir( $id ),
      $id,
      $extension
    );
    
  }
  
  public function getAuthor() {
    
    $this->ensureObjectLoaded();
    return $this->db->getRow("
      SELECT
        id,
        nameprefix,
        namefirst,
        namelast,
        nameformat,
        nickname,
        avatarstatus
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
        s.translationof = l.name_stringid AND
        s.language      = '" . \Springboard\Language::get() . "' AND
        l.id            = st.languageid
      GROUP BY st.languageid
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
        st.isdefault   = '1'
      GROUP BY st.languageid
      LIMIT 1
    ");
    
  }
  
  public function markAsDeleted() {
    
    $this->ensureObjectLoaded();
    
    $this->updateRow( array(
        'status'           => 'markedfordeletion',
        'deletedtimestamp' => date('Y-m-d H:i:s'),
      )
    );
    // TODO delete minden ami ezzel kapcsolatos
    $this->updateChannelIndexPhotos();
    $this->updateCategoryCounters();
    return true;
    
  }
  
  public function getRandomRecordings( $limit, $organizationid, $user ) {
    
    // TODO isfeatured uncomment
    $select = "
      us.nickname,
      us.nameformat,
      us.nameprefix,
      us.namefirst,
      us.namelast,
      us.avatarstatus,
      us.id AS userid,
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
  
  public function getSearchAdvancedWhere( $organizationid, $search ) {
    
    if ( $this->searchadvancedwhere )
      return $this->searchadvancedwhere;
    
    $where = array();
    if ( $search['wholeword'] ) {
      
      $term  = preg_quote( $search['q'] );
      $trans = array(
        'a' => '[aá]',
        'á' => '[aá]',
        'Á' => '[AÁ]',
        'A' => '[AÁ]',
        'e' => '[eé]',
        'é' => '[eé]',
        'E' => '[EÉ]',
        'É' => '[EÉ]',
        'i' => '[ií]',
        'í' => '[ií]',
        'I' => '[IÍ]',
        'Í' => '[IÍ]',
        'o' => '[oóöő]',
        'ó' => '[oóöő]',
        'ö' => '[oóöő]',
        'ő' => '[oóöő]',
        'O' => '[OÓÖŐ]',
        'Ó' => '[OÓÖŐ]',
        'Ö' => '[OÓÖŐ]',
        'Ő' => '[OÓÖŐ]',
        'u' => '[uúüű]',
        'ú' => '[uúüű]',
        'ü' => '[uúüű]',
        'Ű' => '[uúüű]',
        'U' => '[UÚÜŰ]',
        'Ú' => '[UÚÜŰ]',
        'Ü' => '[UÚÜŰ]',
        'Ű' => '[UÚÜŰ]',
      );
      $term = strtr( $term, $trans );
      $term = "REGEXP " . $this->db->qstr( '[[:<:]]' . $term . '[[:>:]]' );
      
    } else {
      
      $term = str_replace( ' ', '%', $search['q'] );
      $term = 'LIKE ' . $this->db->qstr( '%' . $term . '%' );
      
    }
    
    $where[] = "
      (
         r.title       $term OR
         r.subtitle    $term OR
         r.description $term
      )
    ";
    
    if ( strlen( $search['uploaddatefrom'] ) )
      $where[] = "r.timestamp >= " . $this->db->qstr( $search['uploaddatefrom'] );
    
    if ( strlen( $search['uploaddateto'] ) )
      $where[] = "r.timestamp <= " . $this->db->qstr( $search['uploaddateto'] );
    
    if ( strlen( $search['createdatefrom'] ) )
      $where[] = "r.recordedtimestamp >= " . $this->db->qstr( $search['createdatefrom'] );
    
    if ( strlen( $search['createdateto'] ) )
      $where[] = "r.recordedtimestamp <= " . $this->db->qstr( $search['createdateto'] );
    
    if ( intval( $search['languages'] ) ) {
      
      $languageid = $this->db->qstr( intval( $search['languages'] ) );
      $where[]    = "r.languageid = $languageid";
      
    }
    
    if ( strlen( $search['contributorname'] ) ) {
      
      $contributorname = $this->db->qstr( '%' . $search['contributorname'] . '%' );
      if ( strlen( $search['contributorjob'] ) ) {
        
        $contributorjob = $this->db->qstr( '%' . $search['contributorjob'] . '%' );
        $contributorids = $this->db->getCol("
          SELECT DISTINCT c.id
          FROM
            contributors AS c,
            contributors_jobs AS cj
          WHERE
            (
              IF( c.nameformat = 'straight',
                CONCAT_WS(' ', c.nameprefix, c.namelast, c.namefirst ),
                CONCAT_WS(' ', c.nameprefix, c.namefirst, c.namelast )
              ) LIKE $contributorname
            ) AND
            (
              cj.job LIKE $contributorjob
            ) AND
            c.id = cj.contributorid AND
            c.organizationid = '$organizationid'
        ");
        
      } else
        $contributorids = $this->db->getCol("
          SELECT id
          FROM contributors
          WHERE
            IF( nameformat = 'straight',
                CONCAT_WS(' ', nameprefix, namelast, namefirst ),
                CONCAT_WS(' ', nameprefix, namefirst, namelast )
            ) LIKE $contributorname AND
            organizationid = '$organizationid'
        ");
      
    } else
      $contributorids = array();
    
    if ( strlen( $search['contributororganization'] ) ) {
      
      $contributororg  = str_replace( ' ', '%', $search['contributororganization'] );
      $contributororg  = $this->db->qstr( '%' . $contributororg . '%' );
      $organizationids = $this->db->getCol("
        SELECT id
        FROM organizations
        WHERE
          name LIKE $contributororg OR
          nameshort LIKE $contributororg
      ");
      
      if ( !empty( $organizationids ) ) {
        
        $contributorids = $this->db->getCol("
          SELECT id
          FROM contributors
          WHERE
            organizationid IN('" . implode("', '", $organizationids ) . "') " .
          ( empty( $contributorids )?
            '':
            " AND id IN('" . implode("', '", $contributorids ) . "'"
          )
        );
      
      }
      
    }
    
    $recordingids = array();
    if ( !empty( $contributorids ) ) {
      
      $recordingids = $this->db->getCol("
        SELECT DISTINCT recordingid
        FROM contributors_roles
        WHERE
          contributorid IN ('" . implode("', '", $contributorids ) . "')
      ");
      
    }
    
    if ( $search['category'] ) {
      
      $recordingids = array_merge( $recordingids, $this->db->getCol("
        SELECT DISTINCT recordingid
        FROM recordings_categories
        WHERE categoryid = '" . intval( $search['category'] ) . "'
      ") );
      
    }
    
    if ( $search['department'] ) {
      
      $recordingids = array_merge( $recordingids, $this->db->getCol("
        SELECT DISTINCT recordingid
        FROM access
        WHERE departmentid = '" . intval( $search['department'] ) . "'
      ") );
      
    }
    
    
    if ( !empty( $recordingids ) ) {
      $recordingids = array_unique( $recordingids );
      $where[] = "r.id IN('" . implode("', '", $recordingids ) . "')";
    }
    
    $where = implode(' AND ', $where );
    return $this->searchadvancedwhere = array(
      'term'  => $term,
      'where' => $where,
    );
    
  }
  
  public function getSearchAdvancedCount( $user, $organizationid, $search ) {
    
    $where  = $this->getSearchAdvancedWhere( $organizationid, $search );
    
    $where['where'] .= "
      AND
      r.organizationid = '$organizationid'
    ";
    
    $query = "(
        SELECT COUNT(*) FROM
        (
          " . self::getUnionSelect( $user, 'r.id', 'recordings AS r', $where['where'] ) . "
        ) AS subcount
      )
    ";
    
    return $this->db->getOne( $query );
    
  }
  
  public function getSearchAdvancedArray( $user, $organizationid, $search, $start, $limit, $order ) {
    
    $where  = $this->getSearchAdvancedWhere( $organizationid, $search );
    $select = "
      'recording' AS type,
      (
        1 +
        IF( r.title " . $where['term'] . ", 2, 0 ) +
        IF( r.subtitle " . $where['term'] . ", 1, 0 ) +
        IF( r.description " . $where['term'] . ", 1, 0 )
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
    
    $where['where'] .= "
      AND
      r.organizationid = '$organizationid'
    ";
    
    $query = self::getUnionSelect( $user, $select, 'recordings AS r', $where['where'] ) . "
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
  
  public function getRecordingsCount( $where, $user ) {
    
    return $this->db->getOne("
      SELECT COUNT(*)
      FROM (
        " . self::getUnionSelect( $user, null, null, $where ) . "
      ) AS count
    ");
    
  }
  
  public function getRecordingsWithUsers( $start, $limit, $extrawhere, $order, $user, $organizationid ){
    
    $select = "
      r.*,
      usr.id AS userid,
      usr.nickname,
      usr.nameformat,
      usr.nameprefix,
      usr.namefirst,
      usr.namelast
    ";
    
    $from = "
      recordings AS r,
      users AS usr" // azert nem 'u' mert az unionselectben mar van egy 'u'
    ;
    
    $where = "
      usr.id = r.userid AND
      r.organizationid = '$organizationid'
    ";
    
    if ( $extrawhere )
      $where .= " AND ( $extrawhere )";
    
    return $this->db->getArray("
      " . self::getUnionSelect( $user, $select, $from, $where ) .
      ( strlen( $order ) ? 'ORDER BY ' . $order : '' ) . " " .
      ( is_numeric( $start ) ? 'LIMIT ' . $start . ', ' . $limit : "" )
    );
    
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
      INSERT INTO contributors_roles (contributorid, recordingid, roleid )
      VALUES ('" . $data['contributorid'] . "', '" . $this->id . "', '" . $data['roleid'] . "')
    ");
    
    $insertid = $this->db->Insert_ID();
    if ( !$insertid )
      return;
    
    $this->db->query("
      UPDATE contributors_roles
      SET weight = '$insertid'
      WHERE id = '$insertid'
      LIMIT 1
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
  
  public function clearSubtitleWithLanguage( $languageid ) {
    
    $languageid = intval( $languageid );
    if ( !$languageid )
      throw new \Exception("Invalid languageid passed!");
    
    $this->ensureID();
    $this->db->query("
      DELETE FROM subtitles
      WHERE
        recordingid = '" . $this->id . "' AND
        languageid  = '" . $languageid . "'
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
  
  public function getIntroOutroCount( $organizationid ) {
    
    return $this->db->getOne("
      SELECT COUNT(*)
      FROM recordings
      WHERE " . self::getPublicRecordingWhere('', '1') . " AND
        organizationid = '$organizationid'
    ");
    
  }
  
  public function getIntroOutroAssoc( $organizationid ) {
    
    return $this->db->getAssoc("
      SELECT id, title
      FROM recordings
      WHERE " . self::getPublicRecordingWhere('', '1') . " AND
        organizationid = '$organizationid'
      ORDER BY title
    ");
    
  }
  
  public function getIndexPhotos() {
    
    $this->ensureObjectLoaded();
    $indexphotos = array();
    
    if ( !$this->row['numberofindexphotos'] )
      return $indexphotos;
    
    for( $i = 1; $i <= $this->row['numberofindexphotos']; $i++ ) {
      
      $indexphotos[] = preg_replace(
        '/_\d+\.jpg$/',
        '_' . $i . '.jpg',
        $this->row['indexphotofilename']
      );
      
    }
    
    return $indexphotos;
    
  }
  
}
