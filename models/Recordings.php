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

  public $metadata = array();
  protected $searchadvancedwhere;
  protected $streamingserver;
  protected $commentcount = array();

  public function getLength() {
    $this->ensureObjectLoaded();
    return max( $this->row['masterlength'], $this->row['contentmasterlength'] );
  }

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
        numberofviewsthis" . $type . " = 0,
        combinedratingper" . $type . " = 0
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
        numberofviewsthismonth = numberofviewsthismonth + 1,
        " . $this->getCombinedRatingSQL() . "
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

      $this->bootstrap->includeTemplatePlugin('nameformat');
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
        rg.recordingid  = '" . $this->id . "' AND
        g.id            = rg.genreid AND
        s.translationof = g.name_stringid
    ");

    $categories = $this->db->getCol("
      SELECT s.value
      FROM
        categories AS c,
        recordings_categories AS rc,
        strings AS s
      WHERE
        rc.recordingid  = '" . $this->id . "' AND
        c.id            = rc.categoryid AND
        s.translationof = c.name_stringid
    ");

    $cache = array_merge( $cache, $contributornames, $genres, $categories );

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
    $slides = $this->db->getCol("
      SELECT ocrtext
      FROM ocr_frames
      WHERE
        status      = 'onstorage' AND
        recordingid = '" . $this->id . "' AND
        ocrtext IS NOT NULL AND
        LENGTH(ocrtext) > 0
    ");

    $cache = implode( ' ', $slides );
    $documents = $this->db->getCol("
      SELECT CONCAT_WS(' ', title, masterfilename, documentcache)
      FROM attached_documents
      WHERE
        recordingid = '" . $this->id . "' AND
        status NOT IN('markedfordeletion', 'deleted') AND
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
         $this->row['approvalstatus'] != 'approved' // nincs metaadata vagy kikapcsolva
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

    $defaultindexphoto = $this->row['indexphotofilename'];
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
        $channel->updateIndexFilename( false, $defaultindexphoto );

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

  public function userHasAccess( $user, $secure = null, $mobile = false, $organization = null ) {

    $this->ensureObjectLoaded();
    /*
      tehat a logika a kovetkezo:
      ha a kapcsolat non-secure (ergo non-https-en) keresztul jott akkor azonnal
      tiltjuk ha secure kapcsolat nezese be van kapcsolva
    */
    if ( $secure !== null and $this->row['issecurestreamingforced'] != $secure )
      return 'securerestricted';

    /*
      ha nincs lekonvertalva vagy meg vazlat allapotban van akkor tiltjuk rogton
      (de a letrehozo usernek/adminnak engedjuk)
    */
    $bystatus = $this->isAccessibleByStatus( $user, $mobile );
    if ( $bystatus !== true )
      return $bystatus;

    /*
      aztan megnezzuk beallitas (recordings/modifysharing Hozzáférés) alapjan
      hogy engedve van e, meg nem dontunk a visszateresi ertekrol egyelore
    */
    $bysettings = $this->isAccessibleBySettings( $user );

    /*
      ha nem fer hozza beallitas alapjan akkor megnezzuk hogy invitacio alapjan
      hozzafer e, ha igen akkor itt abbahagyjuk, es biztos hozzafer
    */
    if ( $bysettings !== true ) {

      // ennek a vissza teresi erteke nem erdekel minket, csak az hogy true e
      $byinvitation = $this->isAccessibleByInvitation( $user, $organization );
      if ( $byinvitation === true )
        return true;

    }

    /*
      a user lehet hogy bealitas alapjan hozzaferhetne a felvetelhez, de meg
      muszaj megnezni hogy a felvetel tartozik e kurzusba es az alapjan
      hozzafer e a user
      ennek a vissza teresi erteke csak akkor fontos ha true (be van a felvetel
      sorolva kurzusba, es hozzafer a user) vagy non-null (be van sorolva
      kurzusba, de nem fer hozza)
      ha null, akkor nem tartozik a felvetel kurzusba es a beallitas szerinti
      hozzaferes szamit
    */
    $bycoursecompletion = $this->isAccessibleByCourseCompletion( $user, $organization );
    if ( $bycoursecompletion === true )
      return true;
    elseif ( $bycoursecompletion !== null )
      return $bycoursecompletion;

    /*
      ha idaig eljutottunk akkor a felvetel nem tartozott kurzusba, es a
      felhasznalonak nem volt ra meghivoja, visszaadjuk a beallitas alapjan
      kapott visszateresi erteket
    */
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
      'approvalstatus'  => 'draft',
      'mastersourceip'  => $sourceip,
      'isintrooutro'    => $isintrooutro,
      'timestamp'       => date('Y-m-d H:i:s'),
      'recordedtimestamp' => date('Y-m-d H:i:s'),
      'metadataupdatedtimestamp' => date('Y-m-d H:i:s'),
    ) + $this->metadata;

    if ( $isintrooutro ) {

      $recording['approvalstatus'] = 'approved';

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

  private function normalizeHTMLEntitiesCallback( $matches ) {
    // html entityket vissza alakitjuk majd toroljuk az invalid utf sequenceket
    $valid = preg_replace(
      '/[^\x{0009}\x{000a}\x{000d}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}]+/u',
      '',
      mb_convert_encoding( $matches[0], 'utf-8', 'HTML-ENTITIES' )
    );

    // html enttityt vissza, majd keresunk olyan utf sequencet amit muszaj
    // escapelni
    $needSpecialChars = preg_match(
      '/[\x{0022}\x{0026}\x{0027}\x{003e}\x{003e}]/u',
      mb_convert_encoding( $valid, 'utf-8', 'HTML-ENTITIES' )
    );

    if ( $needSpecialChars )
      $valid = htmlspecialchars( $valid, ENT_QUOTES | ENT_XML1, 'utf-8' );

    return $valid;
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

    // mediainfo invalid xmlt adhat vissza, itt tisztitjuk meg
    $normalizedXML = preg_replace_callback(
      '/(&#[xX]?[0-9a-fA-F]+;)/',
      array( $this, 'normalizeHTMLEntitiesCallback' ),
      $output
    );

    libxml_use_internal_errors( true );
    $xml = new \SimpleXMLElement( $normalizedXML );
    libxml_use_internal_errors( false );

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

    if ( property_exists( $general, 'Duration' ))
      $videolength = $this->getMediainfoNumericValue( $general->Duration[0], $isfloat = true );
    elseif ( property_exists( $video, 'Duration' ))
      $videolength = $this->getMediainfoNumericValue( $video->Duration[0], $isfloat = true );
    elseif ( property_exists( $audio, 'Duration' ))
      $videolength = $this->getMediainfoNumericValue( $audio->Duration[0], $isfloat = true );
    else
      throw new InvalidLengthException('Length not found for the media, output was ' . $output );

    $videolength = round($videolength / 1000, 2, PHP_ROUND_HALF_UP); // mert milisec

    if ( $videolength <= $config['recordings_seconds_minlength'] )
      throw new InvalidLengthException('Recording length was less than ' . $config['recordings_seconds_minlength'] );

    if ( $video ) {
      $videostreamid  = $this->getMediainfoNumericValue( $video->ID[0] );
      $videocodec     = $video->Format;

      if ( $video->Frame_rate )
        $videofps = $this->getMediainfoNumericValue( $video->Frame_rate[0], true );
      else
        $videofps = 25.0;
      // $videofps       = $this->getMediainfoNumericValue( $video->Frame_rate, true );

      if ( $video->Format_Info )
        $videocodec  .= ' (' . $video->Format_Info . ')';
      if ( $video->Format_profile )
        $videocodec  .= ' / ' . $video->Format_profile;

      if ( $video->Bit_rate_mode ) {
        if ( is_array( $video->Bit_rate_mode ) ) {
          // sometimes it's placed inside of a subarray, sometimes not, needs to be checked every time.
          $videobitratemode = $video->Bit_rate_mode[1] == 'Constant' ? 'CBR' : 'VBR';
        } else {
          $videobitratemode = $video->Bit_rate_mode;
        }
      } else {
        $videobitratemode = null;
      }
      if ( $video->Width and $video->Height ) {

        $videores = sprintf(
          '%sx%s',
          $this->getMediainfoNumericValue( $video->Width ),
          $this->getMediainfoNumericValue( $video->Height )
        );

        if ( $video->Display_aspect_ratio )
          $videodar = $this->getMediainfoNumericValue( $video->Display_aspect_ratio, true );

        $videobitrate =
          $this->getMediainfoNumericValue( $video->Bit_rate )?:
          $this->getMediainfoNumericValue( $general->Overall_bit_rate )
        ;

        if ( $video->Scan_type )
          $videoisinterlaced = $video->Scan_type[1] == 'Progressive' ? 0 : 1;
        elseif ( $video->Interlacement )
          $videoisinterlaced = $video->Interlacement[1] == 'Progressive' ? 0 : 1;
        else
          $videoisinterlaced = 0;
      }

    }

    if ( $audio ) {

      $audiocodec    = $audio->Format;
      if ( $audio->Format_Info )
        $audiocodec .= ' ( ' . $audio->Format_Info . ' ) ';
      if ( $audio->Format_profile )
        $audiocodec .= ' / ' . $audio->Format_profile;

      $audiostreamid = $this->getMediainfoNumericValue( $audio->ID[0] );
      $audiofreq     = $this->getMediainfoNumericValue( $audio->Sampling_rate[0] );
      $audiobitrate  = $this->getMediainfoNumericValue( $audio->Bit_rate[0] );
      $audiochannels = $this->getMediainfoNumericValue( $audio->Channel_s_[1] );
      $audiomode     = current( $audio->Bit_rate_mode )?: null;

      if ( $audio->Compression_mode[0] == 'Lossy' )
        $audioquality = 'lossy';
      elseif ( $audio->Compression_mode[0] == 'Lossless' )
        $audioquality = 'lossless';

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

    if ( isset( $statuses[ $this->row['status'] ] ) )
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
    elseif ( $this->row['approvalstatus'] != 'approved' )
      return 'recordingisnotpublished';

    return true;

  }

  public function isAccessibleByInvitation( $user, $organization ) {

    if ( !$user['id'] )
      return null;

    $this->ensureID();
    return (bool)$this->db->getOne("
      SELECT COUNT(*)
      FROM users_invitations
      WHERE
        registereduserid = '" . $user['id'] . "' AND
        recordingid      = '" . $this->id . "' AND
        status           <> 'deleted' AND
        organizationid   = '" . $organization['id'] . "'
      LIMIT 1
    ");

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

      case 'departmentsorgroups':

        if ( !isset( $user['id'] ) )
          return 'registrationrestricted';
        elseif ( $user['id'] == $this->row['userid'] )
          return true;
        elseif ( ( $user['iseditor'] or $user['isclientadmin'] ) and $user['organizationid'] == $this->row['organizationid'] )
          return true;

        $recordingid = "'" . $this->id . "'";
        $userid      = "'" . $user['id'] . "'";

        $hasaccess = $this->db->getOne("
          SELECT (
            SELECT COUNT(*)
            FROM
              access AS a,
              users_departments AS ud
            WHERE
              a.recordingid   = $recordingid AND
              ud.departmentid = a.departmentid AND
              ud.userid       = $userid
            LIMIT 1
          ) +
          (
            SELECT COUNT(*)
            FROM
              access AS a,
              groups_members AS gm
            WHERE
              a.recordingid = $recordingid AND
              gm.groupid    = a.groupid AND
              gm.userid     = $userid
            LIMIT 1
          ) AS count
        ");

        if ( !$hasaccess )
          return 'departmentorgrouprestricted';
        elseif ( $timefailed )
          return 'departmentorgrouprestricted_timefailed';

        break;

      default:
        throw new \Exception('Unknown accesstype ' . $this->row['accesstype'] );
        break;

    }

    return true;

  }

  public function isAccessibleByCourseCompletion( $user, $organization ) {

    $this->ensureObjectLoaded();
    // inside handlefile, skip the check? TODO reevaluate
    if ( $organization === null )
      return true;

    $coursetypeid = $this->bootstrap->getModel('channels')->cachedGetCourseTypeID(
      $organization['id']
    );

    // no course type set up for the organization, default allow
    if ( !$coursetypeid )
      return true;

    // replicating the check from isAccessibleByStatus
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

    // the course channels where the recording is a member
    $coursechannelids = $this->db->getCol("
      SELECT c.id
      FROM
        channels_recordings AS cr,
        channels AS c
      WHERE
        cr.recordingid      = '" . $this->id . "' AND
        cr.channelid        = c.id AND
        c.organizationid    = '" . $organization['id'] . "' AND
        c.channeltypeid     = '$coursetypeid' AND
        c.isdeleted         = '0'
    ");

    // recording not a member of any course
    if ( empty( $coursechannelids ) )
      return null;

    // recording is a member of a course, only users who have access to it can
    // view it
    if ( !$user['id'] )
      return 'registrationrestricted';

    // channels where the user must view all of the previous recordings
    // to be able to view this one
    $usercourses = $this->db->getCol("
      SELECT DISTINCT c.id
      FROM channels AS c
      LEFT JOIN users_invitations AS ui ON (
        ui.registereduserid = '" . $user['id'] . "' AND
        ui.channelid        = c.id AND
        ui.organizationid   = '" . $organization['id'] . "' AND
        ui.status           <> 'deleted'
      )
      LEFT JOIN access AS a ON (
        a.channelid = c.id AND
        (
          a.departmentid IS NOT NULL OR
          a.groupid IS NOT NULL
        )
      )
      WHERE
        c.channeltypeid     = '$coursetypeid' AND
        c.organizationid    = '" . $organization['id'] . "' AND
        (
          ui.id IS NOT NULL OR
          (
            c.accesstype = 'departmentsorgroups' AND
            (
              a.groupid IN(
                (
                  SELECT groupid
                  FROM groups_members
                  WHERE userid = '" . $user['id'] . "'
                )
              ) OR a.departmentid IN(
                (
                  SELECT departmentid
                  FROM users_departments
                  WHERE userid = '". $user['id'] ."'
                )
              )
            )
          )
        )
    ");

    // user not a member of the course, cannot watch
    if ( empty( $usercourses ) )
      return 'courserestricted';

    $recordings = $this->getUserChannelRecordingsWithProgress(
      $usercourses, $user, $organization
    );

    foreach( $recordings as $recording ) {

      // if we arrived here, all dependencies were satisfied
      if ( $recording['id'] == $this->id )
        return true;
      // a dependency has not been watched
      else if ( $recording['positionpercent'] < $organization['elearningcoursecriteria'] )
        return 'coursedependencyrestricted';

    }

    // recording not a member of a channel the user has access to, disallow
    return 'courserestricted';

  }

  public static function getWatchedPositionPercentSQL( $recprefix = 'r.', $progressprefix = 'rvp.', $alias = 'positionpercent' ) {
    return "
      (
        FLOOR(
          (
            IFNULL( {$progressprefix}position, 0 ) /
            GREATEST(
              IFNULL({$recprefix}masterlength, 0),
              IFNULL({$recprefix}contentmasterlength, 0)
            )
          ) * 100
        )
      ) AS {$alias}
    ";
  }

  public function getUserChannelRecordingsWithProgress( $channelids, $user, $organization, $distinct = true, $includeuser = false ) {

    if ( $includeuser ) {
      $select = ",
        usr.id AS userid,
        IF(
          usr.nickname IS NULL OR LENGTH(usr.nickname) = 0,
          CONCAT(usr.namelast, '.', usr.namefirst),
          usr.nickname
        ) AS nickname,
        usr.nameformat,
        usr.nameprefix,
        usr.namefirst,
        usr.namelast
      ";
      $table = ",
        users AS usr
      ";
      $where = "
        usr.id = r.userid AND
      ";
    } else {
      $select = '';
      $table  = '';
      $where  = '';
    }

    return $this->db->getArray("
      SELECT
        " . self::getRecordingSelect('r.') . ",
        cr.channelid,
        " . self::getWatchedPositionPercentSQL() . ",
        IFNULL(rvp.position, 0) AS lastposition
        $select
      FROM
        channels_recordings AS cr,
        recordings AS r
        LEFT JOIN recording_view_progress AS rvp ON(
          r.id       = rvp.recordingid AND
          rvp.userid = '" . $user['id'] . "'
        )
        $table
      WHERE
        $where
        cr.channelid IN('" . implode("', '", $channelids ) . "') AND
        r.id             = cr.recordingid AND
        r.isintrooutro   = '0' AND
        r.approvalstatus = 'approved' AND
        r.status         = 'onstorage' AND -- TODO live
        r.organizationid = '" . $organization['id'] . "' AND
        r.status         = 'onstorage'
      " . ( $distinct? "GROUP BY r.id": "") . "
      ORDER BY cr.weight
    ");
  }

  public static function getPublicRecordingWhere( $prefix = '', $isintrooutro = '0', $accesstype = 'public' ) {

    return "
      {$prefix}status       = 'onstorage' AND
      {$prefix}approvalstatus = 'approved' AND
      {$prefix}isintrooutro = '$isintrooutro' AND
      {$prefix}accesstype   = '$accesstype' AND
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

  public static function getRecordingSelect( $prefix = '' ) {
    return "
      {$prefix}*,
      IF(
        {$prefix}isfeatured <> 0 AND
        (
          {$prefix}featureduntil IS NULL OR
          {$prefix}featureduntil >= NOW()
        ),
        1,
        0
      ) AS currentlyfeatured
    ";
  }

  public static function getUnionSelect( $user, $select = null, $from = null, $where = null, $isintrooutro = null, $group = '' ) {

    if ( $select === null )
      $select = self::getRecordingSelect('r.');
    if ( $from === null )
      $from = 'recordings AS r';
    if ( $isintrooutro === null )
      $isintrooutro = '0';

    if ( !isset( $user['id'] ) ) {

      $publicwhere = self::getPublicRecordingWhere('r.', $isintrooutro );
      if ( $where )
        $publicwhere = ' AND ' . $publicwhere;

      return "
        (
          SELECT DISTINCT $select
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
        r.approvalstatus = 'approved'" . ( $isadmin? '': " OR
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
        $group
      ) UNION DISTINCT (
        SELECT $select
        FROM
          $from,
          access AS a,
          groups_members AS gm
        WHERE
          $where
          $generalwhere AND
          r.accesstype  = 'departmentsorgroups' AND
          a.recordingid = r.id AND
          a.groupid     = gm.groupid AND
          gm.userid     = '" . $user['id'] . "'
        $group
      ) UNION DISTINCT (
        SELECT $select
        FROM
          $from,
          access AS a,
          users_departments AS ud
        WHERE
          $where
          $generalwhere AND
          r.accesstype   = 'departmentsorgroups' AND
          a.recordingid  = r.id AND
          a.departmentid = ud.departmentid AND
          ud.userid      = '" . $user['id'] . "'
        $group
      ) UNION DISTINCT ( -- a hozzaferheto csatornak felveteleit is
        SELECT $select
        FROM
          $from,
          channels_recordings AS ccr,
          users_invitations AS ui
        WHERE
          $where
          $generalwhere AND
          ccr.channelid       = ui.channelid AND
          ccr.recordingid     = r.id AND
          ui.registereduserid = '" . $user['id'] . "' AND
          ui.status          <> 'deleted'
        $group
      ) UNION DISTINCT (
        SELECT $select
        FROM
          $from,
          users_invitations AS ui
        WHERE
          $where
          $generalwhere AND
          r.id                = ui.recordingid AND
          ui.registereduserid = '" . $user['id'] . "' AND
          ui.status          <> 'deleted'
        $group
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
        ratingthismonth = sumofratingthismonth / numberofratingsthismonth,
        " . $this->getCombinedRatingSQL() . "
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

  public function insertComment( $comment, $perpage ) {

    $this->ensureID();
    $commentModel = $this->bootstrap->getModel('comments');

    $comment['recordingid'] = $this->id;
    $comment['timestamp']   = date('Y-m-d H:i:s');

    $comment = $this->ensureCommentReply( $comment );

    $iterations = 0;
    while( $iterations < 100 ) {
      $iterations++;

      try {
        $comment['sequenceid'] = $this->db->getOne("
          SELECT IFNULL(MAX(sequenceid) + 1, 1)
          FROM comments
          WHERE recordingid = '" . $this->id . "'
        ");

        // exceptiont fog dobni ha mar van ilyen sequenceid,
        $commentModel->insert( $comment );
        break; // nem volt exception, kilepunk a loopbol

      } catch( \Exception $e ) {
        continue;
      }

    }

    if ( $commentModel->row['replyto'] )
      $commentModel->row['replypage'] =
        ceil( $commentModel->row['replyto'] / $perpage )
      ;

    $this->incrementCommentCount();
    return $commentModel->row;

  }

  private function ensureCommentReply( &$comment ) {

    $this->ensureID();
    $rs = $this->db->execute("
      SELECT
        c.sequenceid,
        IF(
          u.nickname IS NULL OR LENGTH(u.nickname) = 0,
          CONCAT(u.namelast, '.', u.namefirst),
          u.nickname
        ) AS nickname
      FROM
        comments AS c,
        users AS u
      WHERE
        c.recordingid = '" . $this->id . "' AND
        u.id          = c.userid
    ");

    foreach( $rs as $value ) {
      $validids[ $value['sequenceid'] ] = true;
      if ( !isset( $nicknametoids[ $value['nickname'] ] ) )
        $nicknametoids[ $value['nickname'] ] = array();

      $nicknametoids[ $value['nickname'] ][] = $value['sequenceid'];
    }

    $found = preg_match( '/^@([^:]+):/', trim( $comment['text'] ), $match );
    if ( !$found ) {
      $comment['replyto'] = 0;
      return $comment;
    } else
      $nick = $match[1];

    // a user sajat maga irta be a user nickjet, a legutolso commentre
    // linkelunk amit a target user irt
    if ( !$comment['replyto'] and isset( $nicknametoids[ $nick ] ) )
      $comment['replyto'] =
        $nicknametoids[ $nick ][ count( $nicknametoids[ $nick ] ) - 1 ]
      ;
    elseif( // ellenorizzuk hogy a replyto megfelel a valosagnak
            $comment['replyto'] and
            ( // nincs ilyen nick vagy nincs ilyen commentid
              !isset( $nicknametoids[ $nick ] ) or
              !isset( $validids[ $comment['replyto'] ] )
            )
          )
      $comment['replyto'] = 0;

    return $comment;

  }

  public function getComments( $start = 0, $limit = 10 ) {

    $this->ensureID();
    $rs = $this->db->execute("
      SELECT
        c.id,
        c.sequenceid,
        c.replyto,
        c.timestamp,
        c.text,
        c.userid,
        IF(
            c.userid IS NOT NULL,
            IF(
              u.nickname IS NULL OR LENGTH(u.nickname) = 0,
              CONCAT(u.namelast, '.', u.namefirst),
              u.nickname
            ),
            CONCAT('anonymous_', au.id)
        ) AS nickname,
        u.nameformat,
        u.nameprefix,
        u.namefirst,
        u.namelast,
        u.avatarstatus,
        u.avatarfilename
      FROM comments AS c
      LEFT JOIN users AS u ON (
        c.userid = u.id
      )
      LEFT JOIN anonymous_users AS au ON(
        c.anonymoususerid = au.id
      )
      WHERE
        c.recordingid = '" . $this->id . "' AND
        c.moderated   = '0'
      ORDER BY c.id ASC
      LIMIT $start, $limit
    ");

    $ret = array();

    foreach( $rs as $value )
      $ret[] = $value;

    return $ret;

  }

  public function getCommentsCount() {

    $this->ensureID();
    if ( isset( $this->commentcount[ $this->id ] ) )
      return $this->commentcount[ $this->id ];

    return $this->commentcount[ $this->id ] = $this->db->getOne("
      SELECT COUNT(*)
      FROM comments
      WHERE
        moderated   = '0' AND
        recordingid = '" . $this->id . "'
    ");

  }

  public function getCommentsPageCount( $perpage ) {
    $commentcount = $this->getCommentsCount();
    return ceil( $commentcount / $perpage );
  }

  public function getCommentsPage( $perpage, $page ) {
    // mert az oldalak 1-basedek, de a LIMIT 0 based
    $page--;
    $start = $page * $perpage;
    if ( $start < 0 )
      return array();

    $ret      = array();
    $comments = $this->getComments( $start, $perpage );
    // gyakorlatilag array_reverse csak kitoltjuk a replypage-et is
    for ( $i = count( $comments ) - 1; $i >= 0; $i-- ) {

      if ( $comments[ $i ]['replyto'] )
        $comments[ $i ]['replypage'] =
          ceil( $comments[ $i ]['replyto'] / $perpage )
        ;

      $ret[] = $comments[ $i ];
      unset( $comments[ $i ] );
    }

    return $ret;
  }

  public function getRelatedVideosByCourse( $limit, $user, $organization ){

    if ( !$user['id'] )
      return array();

    $this->ensureID();

    $coursetypeid = $this->bootstrap->getModel('channels')->cachedGetCourseTypeID(
      $organization['id']
    );

    if ( !$coursetypeid )
      return array();

    // the course channels where the recording is a member
    $coursechannelids = $this->db->getCol("
      SELECT c.id
      FROM
        channels_recordings AS cr,
        channels AS c
      WHERE
        cr.recordingid      = '" . $this->id . "' AND
        cr.channelid        = c.id AND
        c.organizationid    = '" . $organization['id'] . "' AND
        c.channeltypeid     = '$coursetypeid' AND
        c.isdeleted         = '0'
    ");

    // recording not a member of any course
    if ( empty( $coursechannelids ) )
      return array();

    $usercourses = $this->db->getCol("
      SELECT ui.channelid
      FROM
        users_invitations AS ui
      WHERE
        ui.channelid IN('" . implode("', '", $coursechannelids ) . "') AND
        ui.registereduserid = '" . $user['id'] . "' AND
        ui.organizationid   = '" . $organization['id'] . "' AND
        ui.status           <> 'deleted'
    ");

    // user not a member of the course
    if ( empty( $usercourses ) )
      return array();

    $found      = false;
    $ret        = array();
    $recordings = $this->getUserChannelRecordingsWithProgress(
      $usercourses, $user, $organization, true, true
    );

    foreach( $recordings as $recording ) {

      if ( !$found and $recording['id'] == $this->id ) {
        $found = true;
        continue;
      }

      if ( !$found )
        continue;

      $ret[ $recording['id'] ] = array(
        'id'                  => $recording['id'],
        'title'               => $recording['title'],
        'subtitle'            => $recording['subtitle'],
        'indexphotofilename'  => $recording['indexphotofilename'],
        'masterlength'        => $recording['masterlength'],
        'contentmasterlength' => $recording['contentmasterlength'],
        'numberofviews'       => $recording['numberofviews'],
        'userid'              => $recording['userid'],
        'nickname'            => $recording['nickname'],
        'nameformat'          => $recording['nameformat'],
        'nameprefix'          => $recording['nameprefix'],
        'namefirst'           => $recording['namefirst'],
        'namelast'            => $recording['namelast'],
        'timestamp'           => $recording['timestamp'],
        'recordedtimestamp'   => $recording['recordedtimestamp'],
      );

    }

    return $ret;

  }

  public function getRelatedVideosByKeywords( $limit, $user, $organization ){

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
      r.contentmasterlength,
      r.numberofviews,
      r.timestamp,
      r.recordedtimestamp,
      usr.id AS userid,
      IF(
        usr.nickname IS NULL OR LENGTH(usr.nickname) = 0,
        CONCAT(usr.namelast, '.', usr.namefirst),
        usr.nickname
      ) AS nickname,
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
      r.organizationid = '" . $organization['id'] . "'"
    ;

    if ( !empty( $keywordwhere ) )
      $where .= " AND ( " . implode(' OR ', $keywordwhere ) . " )";

    return $this->db->getAssoc(
      self::getUnionSelect( $user, $select, $from, $where ) . "
      ORDER BY RAND()
      LIMIT $limit
    ");

  }

  public function getRelatedVideosByChannel( $limit, $user, $organization, $channelids = null ) {

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
      r.contentmasterlength,
      r.numberofviews,
      r.timestamp,
      r.recordedtimestamp,
      usr.id AS userid,
      IF(
        usr.nickname IS NULL OR LENGTH(usr.nickname) = 0,
        CONCAT(usr.namelast, '.', usr.namefirst),
        usr.nickname
      ) AS nickname,
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
      r.organizationid = '" . $organization['id'] . "'"
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
      $return = $return + $this->getRelatedVideosByChannel( $limit - count( $return ), $user, $organization, $parentids );

    }

    return $return;

  }

  public function getRelatedVideosRandom( $limit, $user, $organization ) {

    $this->ensureID();

    $select = "
      r.id AS arraykey,
      r.id,
      r.title,
      r.subtitle,
      r.indexphotofilename,
      r.masterlength,
      r.contentmasterlength,
      r.numberofviews,
      r.timestamp,
      r.recordedtimestamp,
      usr.id AS userid,
      IF(
        usr.nickname IS NULL OR LENGTH(usr.nickname) = 0,
        CONCAT(usr.namelast, '.', usr.namefirst),
        usr.nickname
      ) AS nickname,
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
      r.organizationid = '" . $organization['id'] . "'"
    ;

    return $this->db->getAssoc(
      self::getUnionSelect( $user, $select, $from, $where ) . "
      ORDER BY RAND()
      LIMIT $limit
    ");


  }

  public function getRelatedVideos( $count, $user, $organization ) {

    $this->ensureObjectLoaded();

    $return = $this->getRelatedVideosByCourse( $count, $user, $organization );
    if ( !empty( $return ) ) {// ha kurzusba tartozik  akkor csak azokat adjuk vissza
      $return = $this->addPresentersToArray( $return, true, $organization['id'] );
      return $return;
    }

    if ( count( $return ) < $count )
      $return = $return + $this->getRelatedVideosByChannel( $count - count( $return ), $user, $organization );

    if ( count( $return ) < $count )
      $return = $return + $this->getRelatedVideosByKeywords( $count - count( $return ), $user, $organization );

    if ( count( $return ) < $count )
      $return = $return + $this->getRelatedVideosRandom( $count - count( $return ), $user, $organization );

    $return = $this->addPresentersToArray( $return, true, $organization['id'] );
    return $return;

  }

  public function canUploadContentVideo() {

    $this->ensureObjectLoaded();

    if (
         (
           !$this->row['contentstatus'] or
           $this->row['contentstatus'] == 'deleted'
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
      throw new \Exception('No metadata for the video found, please ->analyize() it beforehand!');

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
      throw new \Exception('No oldcontentstatuses found, was there an addContentRecording before this?');

    // uj feltoltes
    $contentstatus = array(
      'contentstatus'       => 'uploaded',
      'contentmasterstatus' => 'uploaded',
    );

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
        SELECT COUNT(DISTINCT r.id)
        FROM $from
        WHERE
          $where AND
          " . self::getPublicRecordingWhere('r.')
    );

    return $this->db->getOne("
      SELECT COUNT(*)
      FROM (
        " . self::getUnionSelect( $user, null, $from, $where ) . "
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
      " . self::getUnionSelect( $user, null, $from, $where ) .
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
        " . self::getUnionSelect( $user, null, $from, $where ) . "
      ) AS count
    ");

  }

  public function getChannelRecordings( $user, $channelids, $start = false, $limit = false, $order = false ) {

    $select = "
      " . self::getRecordingSelect('r.') . ",
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
      c.id        = cr.channelid AND
      c.isdeleted = '0' AND
      r.id        = cr.recordingid"
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

  public function addPresentersToArray( &$recordings, $withjobs = true, $organizationid ) {
    if ( empty( $recordings ) )
      return $recordings;

    $idtoindexmap = array();
    foreach( $recordings as $key => $recording ) {

      if ( isset( $recording['type'] ) and $recording['type'] != 'recording' )
        continue;

      // store the index of the recordings in an array keyed by the recordingid
      // so as not to re-index the array
      if ( isset( $recording['id'] ) )
        $idtoindexmap[ $recording['id'] ] = $key;

    }

    if ( empty( $idtoindexmap ) )
      return $recordings;

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
        cr.recordingid IN('" . implode("', '", array_keys( $idtoindexmap ) ) . "') AND
        cr.roleid IN( (SELECT r.id FROM roles AS r WHERE r.organizationid = '$organizationid' AND r.ispresenter = '1') )
      ORDER BY cr.weight
    ");

    if ( $withjobs )
      $contributors = $this->addJobsToContributors( $contributors, $organizationid );

    foreach( $contributors as $contributor ) {

      $key = $idtoindexmap[ $contributor['recordingid'] ];
      if ( !isset( $recordings[ $key ]['presenters'] ) )
        $recordings[ $key ]['presenters'] = array();

      $recordings[ $key ]['presenters'][] = $contributor;

    }

    return $recordings;

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
    $where        = array(
      "c.isliveevent = '0'"
    );

    if ( !$user['id'] )
      throw new \Exception("No user given");

    if ( !$user['isadmin'] and !$user['isclientadmin'] and !$user['iseditor'] )
      $where[] = "c.userid = '" . $user['id'] . "'"; // csak a sajat csatornait

    if ( !empty( $where ) )
      $where  = '( ' . implode(" OR ", $where ) . " ) AND ";
    else
      $where  = '';

    $where .= "c.organizationid = '" . $user['organizationid'] . "'";

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
        recordingid = '" . $this->id . "'" . (
        ( !$user['isadmin'] and !$user['isclientadmin'] and !$user['iseditor'] )
        ? " AND userid      = '" . $user['id'] . "'"
        : ""
      )
    );

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

  public function getVersions( $ids = array() ) {
    $this->ensureObjectLoaded();

    if ( empty( $ids ) )
      $ids[] = $this->id;

    $rs = $this->db->query("
      SELECT
        rv.*,
        ep.shortname AS encodingshortname,
        ep.mediatype,
        ep.type AS encodingtype
      FROM
        recordings_versions AS rv,
        encoding_profiles AS ep
      WHERE
        rv.recordingid IN('" . implode("', '", $ids ) . "') AND
        rv.status = 'onstorage' AND
        ep.id     = rv.encodingprofileid
      ORDER BY rv.bandwidth, rv.encodingorder
    ");

    $ret = array(
      'master'  => array(
        'desktop' => array(),
        'mobile'  => array(),
      ),
      'content' => array(
        'desktop' => array(),
        'mobile'  => array(),
      ),
      'audio'   => array(),
    );

    $hascontent  = $this->row['contentstatus'] == 'onstorage';
    $pipversions = array();

    foreach( $rs as $version ) {
      if ( $version['resolution'] ) {
        $res = explode('x', strtolower( $version['resolution'] ));
        $version['dimensions'] = $res[0] * $res[1];
      } else
        $version['dimensions'] = 0;

      if ( $version['encodingshortname'] == 'audio' ) {
        $ret['audio'][] = $version;
        continue;
      }

      if ( $version['iscontent'] )
        $key = 'content';
      else
        $key = 'master';

      if ( $version['isdesktopcompatible'] )
        $ret[ $key ]['desktop'][] = $version;

      if ( $version['ismobilecompatible'] ) {

        // pip verziok kizarolag iscontent = 0-ak ergo master kulcsa alatt lesznek
        if ( $hascontent and $version['encodingtype'] == 'pip' )
          $pipversions[] = $version;
        else
          $ret[ $key ]['mobile'][] = $version;

      }

    }

    if ( !empty( $pipversions ) )
      $ret['master']['mobile'] = $pipversions;

    if ( $this->row['mastermediatype'] == 'audio' )
      $ret['master']['desktop'] = $ret['master']['mobile'] = $ret['audio'];

    if ( $this->row['contentmastermediatype'] == 'audio' )
      $ret['content']['desktop'] = $ret['content']['mobile'] = $ret['audio'];

    return $ret;

  }

  public function getFlashData( $info ) {

    $this->ensureObjectLoaded();
    $this->bootstrap->includeTemplatePlugin('indexphoto');

    if ( isset( $info['versions'] ) )
      $versions = $info['versions'];
    else
      $versions       = $this->getVersions();

    $recordingbaseuri = $info['BASE_URI'] . \Springboard\Language::get() . '/recordings/';

    if ( $this->bootstrap->config['forcesecureapiurl'] )
      $apiurl = 'https://' . $info['organization']['domain'] . '/';
    else
      $apiurl = $info['BASE_URI'];

    $apiurl .=  'jsonapi';
    $data    = array(
      'language'              => \Springboard\Language::get(),
      'api_url'               => $apiurl,
      'user_needPing'         => false,
      'track_firstPlay'       => true,
      'recording_id'          => $this->id,
      'recording_title'       => $this->row['title'],
      'recording_subtitle'    => (string)$this->row['subtitle'],
      'recording_description' => (string)$this->row['description'],
      'recording_duration'    => $this->getLength(),
      'recording_image'       => \smarty_modifier_indexphoto( $this->row, 'player', $info['STATIC_URI'] ),
      'user_checkWatching'    => (bool)@$info['member']['ispresencecheckforced'],
      'user_checkWatchingTimeInterval' => $info['organization']['presencechecktimeinterval'],
      'user_checkWatchingConfirmationTimeout' => $info['organization']['presencecheckconfirmationtime'],
      'recording_timeout' => $info['organization']['viewsessiontimeoutminutes'] * 60, // masodpercbe
    );

    if ( $this->row['mastermediatype'] == 'audio' )
      $data['recording_isAudio'] = true;

    if ( isset( $info['member'] ) and $info['member']['id'] ) {
      $data['user_id']          = $info['member']['id'];
      $data['user_needPing']    = true;
      $data['user_pingSeconds'] = $this->bootstrap->config['sessionpingseconds'];
      $data['recording_checkTimeout'] = true; // nezzuk hogy timeoutolt e a felvetel
    }

    $data = $data + $this->bootstrap->config['flashplayer_extraconfig'];

    $hds  = $this->isHDSEnabled( $info );
    $data = $data + $this->getMediaServers( $info, $hds );

    // default bal oldalon van a video, csak akkor allitsuk be ha kell
    if ( !$this->row['slideonright'] )
      $data['layout_videoOrientation'] = 'right';

    if ( $data['language'] != 'en' )
      $data['locale'] = $info['STATIC_URI'] . 'js/flash_locale_' . $data['language'] . '.json';

    if ( !empty( $versions['master']['desktop'] ) ) {
      $data['media_streams']          = array();
      $data['media_streamLabels']     = array();
      $data['media_streamParameters'] = array();
      $data['media_streamDimensions'] = array();

      if ( $hds )
        $data['media_streams'][]      =
          $this->getMediaUrl('smil', null, $info )
        ;

      foreach( $versions['master']['desktop'] as $version ) {
        $data['media_streamLabels'][]     = $version['qualitytag'];
        $data['media_streamParameters'][] = array(
          'recordingversionid' => $version['id'],
          'viewsessionid'      => $this->generateViewSessionid( $version['id'] ),
        );
        if ( $version['dimensions'] )
          $data['media_streamDimensions'][] = $version['dimensions'];
        else
          $data['recording_autoQuality'] = false;

        if ( !$hds )
          $data['media_streams'][]        =
            $this->getMediaUrl('default', $version, $info )
          ;

      }
    }

    if (
         !isset( $info['skipcontent'] ) and
         !empty( $versions['content']['desktop'] )
       ) {

      if ( $this->row['contentoffsetstart'] )
        $data['timeline_contentVirtualStart'] = $this->row['contentoffsetstart'];

      if ( $this->row['contentoffsetend'] )
        $data['timeline_contentVirtualEnd'] = $this->row['contentoffsetend'];

      $data['content_streams']      = array();
      $data['content_streamLabels'] = array();
      $data['content_streamDimensions'] = array();
      if ( $hds )
        $data['content_streams'][]    =
          $this->getMediaUrl('contentsmil', null, $info )
        ;

      foreach( $versions['content']['desktop'] as $version ) {
        $data['content_streamLabels'][] = $version['qualitytag'];
        if ( $version['dimensions'] )
          $data['content_streamDimensions'][] = $version['dimensions'];
        else
          $data['recording_autoQuality'] = false;

        if ( !$hds )
          $data['content_streams'][]      =
            $this->getMediaUrl('content', $version, $info )
          ;

      }

    }

    $data = $data + $this->getIntroOutroFlashdata( $info );

    if ( $this->row['offsetstart'] )
      $data['timeline_virtualStart'] = $this->row['offsetstart'];

    if ( $this->row['offsetend'] )
      $data['timeline_virtualEnd'] = $this->row['offsetend'];

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

    if ( !$info['organization']['isrecommendationdisabled'] ) {

      if ( isset( $info['relatedvideos'] ) )
        $relatedvideos = $info['relatedvideos'];
      else
        $relatedvideos = $this->getRelatedVideos(
          $this->bootstrap->config['relatedrecordingcount'],
          $info['member'],
          $info['organization']
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

    }

    if ( isset( $info['attachments'] ) and $info['attachments'] ) {
      $this->bootstrap->includeTemplatePlugin('attachmenturl');

      $data['attachments_string'] = array();
      foreach( $info['attachments'] as $attachment )
        $data['attachments_string'][] = array(
          'title'    => $attachment['title'],
          'filename' => $attachment['masterfilename'],
          'url'      => smarty_modifier_attachmenturl(
            $attachment, $this->row, $info['STATIC_URI']
          ),
        );

    }

    if ( $this->row['isseekbardisabled'] and @$info['member'] and $info['member']['id'] )
      $data = array_merge( $data, $this->getSeekbarOptions( $info ) );

    return $data;

  }

  // csak a getflashdata hivja
  private function getSeekbarOptions( $info ) {

    $this->ensureObjectLoaded();
    $user = $info['member'];

    if ( !$this->row['isseekbardisabled'] or !$user or !$user['id'] )
      return array();

    // lekerjuk a globalis progresst, mert ha mar egyszer megnezett egy felvetelt
    // akkor onnantol nem erdekel minket semmi, barmit megnezhet ujra
    $timeout     = $info['organization']['viewsessiontimeoutminutes'];
    $needreset   = false;
    $watched     = false;
    $row         = $this->db->getRow("
      SELECT
        id,
        position AS lastposition,
        IF(
          timestamp < DATE_SUB(NOW(), INTERVAL $timeout MINUTE),
          1,
          0
        ) AS expired
      FROM recording_view_progress
      WHERE
        userid      = '" . $user['id'] . "' AND
        recordingid = '" . $this->id . "'
      ORDER BY id DESC
      LIMIT 1
    ");

    if ( $row ) {
      $watched   = $this->isRecordingWatched( $info['organization'], $row['lastposition'] );
      $needreset = (bool)$row['expired'];

      // ha lejart de nem nezte meg akkor reset
      if ($needreset and !$watched) {
        $row['lastposition'] = 0;
        $seekbardisabled = true;
        $this->db->execute("
          UPDATE recording_view_progress
          SET position = 0
          WHERE id = '" . $row['id'] . "'
          LIMIT 1
        ");
      }
    }

    if ( !$watched and $info['organization']['iselearningcoursesessionbound'] ) {

      // ha session-bound akkor csak az adott sessionben allitjuk vissza
      // a felvetel poziciojat, csak akkor ha nem nezte vegig
      $row = $this->db->getRow("
        SELECT positionuntil AS lastposition
        FROM recording_view_sessions
        WHERE
          userid      = '" . $user['id'] . "' AND
          recordingid = '" . $this->id . "' AND
          sessionid   = " . $this->db->qstr( $info['sessionid'] ) . "
        ORDER BY id DESC
        LIMIT 1
      ");

    }

    if ( !$row )
      $row = array('lastposition' => 0);

    $seekbardisabled = !$watched; // ha megnezte akkor nem kell seekbar
    $options = array(
      'timeline_seekbarDisabled'          => $seekbardisabled,
      'timeline_lastPlaybackPosition'     => (int) $row['lastposition'],
      'timeline_lastPositionTimeInterval' =>
        $this->bootstrap->config['recordingpositionupdateseconds']
      ,
    );

    if (
         $seekbardisabled and
         (
           $user['isadmin'] or
           $user['isclientadmin'] or
           $user['iseditor']
         )
       )
      $options['timeline_seekbarVisible'] = true;

    return $options;

  }

  public function isHDSEnabled( $info ) {
    return
      $info['organization']['ondemandhdsenabled'] and
      in_array( $this->row['smilstatus'], array('onstorage', 'regenerate') )
    ;
  }

  public function getMediaServers( $info, $hds = null ) {

    $this->ensureObjectLoaded();
    $data = array(
      'media_servers' => array(),
    );

    $prefix = $this->row['issecurestreamingforced']? 'sec': '';
    if ( $hds === null )
      $hds = $this->isHDSEnabled( $info );

    if ( $hds ) {
      $data['media_servers'][] = $this->getWowzaUrl( $prefix . 'smilurl', false, $info );
    } else {

      if ( $prefix )
        $data['media_servers'][] = $this->getWowzaUrl( 'secrtmpsurl', true, $info );

      $data['media_servers'][] = $this->getWowzaUrl( $prefix . 'rtmpurl',  true, $info );
      $data['media_servers'][] = $this->getWowzaUrl( $prefix . 'rtmpturl', true, $info );

    }

    // a getWowzaUrl beallitja, de azert menjunk biztosra
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

    return $data;

  }

  public function getIntroOutroFlashdata( $info, $hds = null ) {

    $this->ensureObjectLoaded();
    if ( !$this->row['introrecordingid'] and !$this->row['outrorecordingid'] )
      return array();

    if ( $hds === null )
      $hds   = $this->isHDSEnabled( $info );

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

    $versions = $this->getVersions( $ids );
    if ( empty( $versions['master']['desktop'] ) )
      throw new \Exception("The intro/outro does not have desktopcompatible non-content recordings!");

    $type = $hds? 'smil': 'default';
    foreach( $versions['master']['desktop'] as $version ) {

      if ( $version['recordingid'] == $introid )
        $key = 'intro_streams';
      else if ( $version['recordingid'] == $outroid )
        $key = 'outro_streams';
      else // not possible
        throw new \Exception("Invalid version in getIntroOutroFlashdata, neither intro nor outro!");

      $data[ $key ] = array(
        $this->getMediaUrl( $type, $version, $info )
      );

    }

    return $data;

  }

  public function getStructuredFlashData( $info ) {

    $flashdata = $this->transformFlashData(
      $this->getFlashData( $info )
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

  public function getWowzaUrl( $type, $needextraparam = false, $info = null ) {

    $url = $this->bootstrap->config['wowza'][ $type ];

    if ( $needextraparam ) {

      $this->ensureID();
      $url =
        rtrim( $url, '/' ) .
        $this->getAuthorizeSessionid( $info )
      ;

    }

    if ( !$this->streamingserver ) {

      $streamingserverModel  = $this->bootstrap->getModel('streamingservers');
      $this->streamingserver = $streamingserverModel->getServerByClientIP(
        $info['ipaddress'],
        'ondemand'
      );

    }

    return sprintf( $url, $this->streamingserver['server'] );

  }

  protected function getAuthorizeSessionid( &$info ) {

    $ret = sprintf('?sessionid=%s_%s_%s',
      $info['organization']['id'],
      $info['sessionid'],
      $this->id
    );

    if ( isset( $info['member'] ) and $info['member']['id'] )
      $ret .= '&uid=' . $info['member']['id'];

    return $ret;

  }

  public function getMediaUrl( $type, $version, $info, $id = null ) {

    $this->ensureObjectLoaded();
    $cookiedomain = $info['organization']['cookiedomain'];
    $sessionid    = $info['sessionid'];
    $host         = '';
    $extension    = 'mp4';
    $authtoken    = $this->getAuthorizeSessionid( $info );
    $extratoken   = '';

    if ( $version ) {
      $extension   = \Springboard\Filesystem::getExtension( $version['filename'] );

      if ( $authtoken )
        $extratoken = '&';
      else
        $extratoken = '?';

      $extratoken .=
        'recordingversionid=' . $version['id'] .
        '&viewsessionid=' . $this->generateViewSessionid( $version['id'] )
      ;

    }

    $user = null;
    if ( isset( $info['member'] ) )
      $user = $info['member'];

    $typeprefix = '';
    if ( $this->row['issecurestreamingforced'] )
      $typeprefix = 'sec';

    switch( $type ) {

      case 'mobilehttp':
        //http://stream.videosquare.hu:1935/vtorium/_definst_/mp4:671/2671/2671_2608_mobile.mp4/playlist.m3u8
        $host        = $this->getWowzaUrl( $typeprefix . 'httpurl');
        $sprintfterm =
          '%3$s:%s/%s/playlist.m3u8' .
          $authtoken .
          $extratoken
        ;

        break;

      case 'mobilertsp':
        //rtsp://stream.videosquare.hu:1935/vtorium/_definst_/mp4:671/2671/2671_2608_mobile.mp4
        $host        = $this->getWowzaUrl( $typeprefix . 'rtspurl');
        $sprintfterm =
          '%3$s:%s/%s' .
          $authtoken .
          $extratoken
        ;

        break;

      case 'direct':
        $host = $info['STATIC_URI'];
        $sprintfterm = 'files/recordings/%s/%s';
        break;

      case 'smil':
      case 'contentsmil':
        if ( !$version )
          $version   = array(
            'filename'    => '',
            'recordingid' => $this->id,
          );

        $extension   = 'smil';
        $postfix     = $type == 'contentsmil'? '_content': '';
        $sprintfterm =
          '%3$s:%s/' . $version['recordingid'] . $postfix . '.%3$s/manifest.f4m' .
          $authtoken
        ;

        break;

      case 'content':
      default:
        $sprintfterm = '%3$s:%s/%s';
        break;

    }

    return $host . sprintf( $sprintfterm,
      \Springboard\Filesystem::getTreeDir( $version['recordingid'] ),
      $version['filename'],
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
        IF(
          nickname IS NULL OR LENGTH(nickname) = 0,
          CONCAT(namelast, '.', namefirst),
          nickname
        ) AS nickname,
        avatarstatus,
        avatarfilename
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

    if ( $this->row['isintrooutro'] ) {

      $this->db->execute("
        UPDATE recordings
        SET introrecordingid = NULL
        WHERE introrecordingid = '" . $this->id . "'
      ");
      $this->db->execute("
        UPDATE livefeeds
        SET introrecordingid = NULL
        WHERE introrecordingid = '" . $this->id . "'
      ");

      $this->db->execute("
        UPDATE recordings
        SET outrorecordingid = NULL
        WHERE outrorecordingid = '" . $this->id . "'
      ");

    }

    // TODO delete minden ami ezzel kapcsolatos
    $this->updateChannelIndexPhotos();
    $this->updateCategoryCounters();
    return true;

  }

  public function markContentAsDeleted() {

    $this->ensureObjectLoaded();

    $this->updateRow( array(
        'contentstatus'           => 'markedfordeletion',
        'contentdeletedtimestamp' => date('Y-m-d H:i:s'),
      )
    );

    return true;

  }

  public function getRandomRecordings( $limit, $organizationid, $user ) {

    $select = "
      IF(
        us.nickname IS NULL OR LENGTH(us.nickname) = 0,
        CONCAT(us.namelast, '.', us.namefirst),
        us.nickname
      ) AS nickname,
      us.nameformat,
      us.nameprefix,
      us.namefirst,
      us.namelast,
      us.avatarstatus,
      us.avatarfilename,
      us.id AS userid,
      r.id,
      r.title,
      r.indexphotofilename,
      r.isfeatured,
      r.masterlength,
      r.contentmasterlength,
      r.timestamp,
      r.recordedtimestamp
    ";

    $tables = "
      recordings AS r,
      users AS us
    ";

    $where = "
      us.id            = r.userid AND
      r.organizationid = '" . $organizationid . "' AND
      r.isfeatured     <> 0 AND
      (
        r.featureduntil IS NULL OR
        r.featureduntil >= NOW()
      ) AND
      r.isintrooutro   = 0
    ";

    return $this->db->getArray(
      self::getUnionSelect( $user, $select, $tables, $where ) . "
      ORDER BY isfeatured DESC, RAND()
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
          " . \Model\Channels::getWhere( $user ) . " AND
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
      '0' AS numberofrecordings,
      r.status,
      r.approvalstatus,
      r.masterlength,
      r.contentmasterlength
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
          numberofrecordings,
          '' AS status,
          'approved' AS approvalstatus,
          '0' AS masterlength,
          '0' AS contentmasterlength
        FROM channels
        WHERE
          " . \Model\Channels::getWhere( $user ) . " AND
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

    $ret = $this->db->getArray( $query );
    return $this->searchAddSlidesToArray( $searchterm, $ret );

  }

  public function getSearchAdvancedWhere( $organizationid, $search ) {

    if ( $this->searchadvancedwhere )
      return $this->searchadvancedwhere;

    $where = array();
    if ( strlen( $search['q'] ) ) {

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

    }

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
    $ret   = array(
      'where' => $where,
      'empty' => false,
    );

    if ( isset( $term ) )
      $ret['term'] = $term;
    elseif ( empty( $recordingids ) )
      $ret['empty'] = true;

    return $this->searchadvancedwhere = $ret;

  }

  public function getSearchAdvancedCount( $user, $organizationid, $search ) {

    $where  = $this->getSearchAdvancedWhere( $organizationid, $search );
    if ( $where['empty'] )
      return 0;

    if ( strlen( trim( $where['where'] ) ) )
      $where['where'] .= ' AND ';

    $where['where'] .= "r.organizationid = '$organizationid'";

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
    if ( $where['empty'] )
      return array();

    $select = "
      'recording' AS type,
      (
        1 " .
        (
          isset( $where['term'] ) ? "
            +
            IF( r.title " . $where['term'] . ", 2, 0 ) +
            IF( r.subtitle " . $where['term'] . ", 1, 0 ) +
            IF( r.description " . $where['term'] . ", 1, 0 )
          ": ""
        ) . "
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
      r.timestamp,
      r.metadataupdatedtimestamp,
      r.numberofviews,
      r.rating,
      r.masterlength,
      r.contentmasterlength,
      '0' AS numberofrecordings
    ";

    if ( strlen( trim( $where['where'] ) ) )
      $where['where'] .= ' AND ';

    $where['where'] .= "r.organizationid = '$organizationid'";

    $query = self::getUnionSelect( $user, $select, 'recordings AS r', $where['where'] ) . "
      ORDER BY $order
    ";

    if ( $start !== null )
      $query .= 'LIMIT ' . $start . ', ' . $limit;

    $ret = $this->db->getArray( $query );
    if ( isset( $where['term'] ) ) {
      if ( strpos( $where['term'], 'LIKE ') === 0 )
        $searchterm = substr( $where['term'], strlen('LIKE ') );
      else
        $searchterm = $where['term'];

      $ret = $this->searchAddSlidesToArray( $searchterm, $ret );
    }

    return $ret;
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

  private function getRecordingsSQL( $organizationid ) {

    $select = "
      " . self::getRecordingSelect('r.') . ",
      usr.id AS userid,
      IF(
        usr.nickname IS NULL OR LENGTH(usr.nickname) = 0,
        CONCAT(usr.namelast, '.', usr.namefirst),
        usr.nickname
      ) AS nickname,
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

    return array(
      'select' => $select,
      'from'   => $from,
      'where'  => $where,
    );
  }

  public function getRecordingsWithUsers( $start, $limit, $extrawhere, $order, $user, $organizationid ){

    $sql = $this->getRecordingsSQL( $organizationid );

    if ( $extrawhere )
      $sql['where'] .= " AND ( $extrawhere )";

    return $this->db->getArray("
      " . self::getUnionSelect( $user, $sql['select'], $sql['from'], $sql['where'] ) .
      ( strlen( $order ) ? 'ORDER BY ' . $order : '' ) . " " .
      ( is_numeric( $start ) ? 'LIMIT ' . $start . ', ' . $limit : "" )
    );

  }

  public function getAttachments( $publiconly = true ) {

    $this->ensureObjectLoaded();
    $where = array(
      "recordingid = '" . $this->id . "'",
      "status NOT IN('markedfordeletion', 'deleted')",
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
        recordingid = '" . $this->id . "'" . (
        ( !$user['isadmin'] and !$user['isclientadmin'] and !$user['iseditor'] )
        ? " AND userid      = '" . $user['id'] . "'"
        : ""
      )
    );

    if ( $existingid )
      return false;
    else {
      $this->db->query("
        INSERT INTO channels_recordings (channelid, recordingid, userid)
        VALUES ('$channelid', '" . $this->id . "', '" . $user['id'] . "')
      ");
      $insertedid = $this->db->Insert_ID();
      $this->db->query("
        UPDATE channels_recordings
        SET weight = '$insertedid'
        WHERE id = '$insertedid'
        LIMIT 1
      ");
    }

    $this->updateChannelIndexPhotos();
    $this->updateCategoryCounters();

    $channelModel = $this->bootstrap->getModel('channels');
    $channelModel->id = $channelid;
    $channelModel->updateModification();

    return true;

  }

  public function removeFromChannel( $channelid, $user ) {

    $this->ensureID();
    $this->db->query("
      DELETE FROM channels_recordings
      WHERE
        channelid   = '$channelid' AND
        recordingid = '" . $this->id . "'" . (
        ( !$user['isadmin'] and !$user['isclientadmin'] and !$user['iseditor'] )
        ? " AND userid      = '" . $user['id'] . "'"
        : ""
      )
    );

    $this->updateChannelIndexPhotos();
    $this->updateCategoryCounters();

    $channelModel = $this->bootstrap->getModel('channels');
    $channelModel->id = $channelid;
    $channelModel->updateModification();

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

  public function getGroupRecordingsCount( $groupid ) {

    return $this->db->getOne("
      SELECT COUNT(*)
      FROM
        access AS a,
        recordings AS r
      WHERE
        a.groupid     = '$groupid' AND
        r.id          = a.recordingid AND
        " . self::getPublicRecordingWhere('r.', '0', 'groups')
    );

  }

  public function getGroupRecordings( $groupid, $start = false, $limit = false, $order = false ) {

    return $this->db->getArray("
      SELECT
        " . self::getRecordingSelect('r.') . ",
        a.id AS accessid
      FROM
        access AS a,
        recordings AS r
      WHERE
        a.groupid = '$groupid' AND
        r.id      = a.recordingid AND
        " . self::getPublicRecordingWhere('r.', '0', 'groups') . "
      ORDER BY $order
      LIMIT $start, $limit
    ");

  }

  public function updateLastPosition( $organization, $userid, $lastposition, $sessionid ) {

    $this->ensureID();
    $timeout   = $organization['viewsessiontimeoutminutes'];
    $updatesec = $this->bootstrap->config['recordingpositionupdateseconds'];
    $extrasec  = $organization['viewsessionallowedextraseconds'];
    $ret       = array(
      'success' => true,
    );

    $row       = $this->db->getRow("
      SELECT
        rvp.id,
        rvp.position,
        IF(
          rvp.timestamp < DATE_SUB(NOW(), INTERVAL $timeout MINUTE),
          1,
          0
        ) AS expired,
        r.masterlength,
        r.contentmasterlength
      FROM
        recording_view_progress AS rvp,
        recordings AS r
      WHERE
        rvp.userid      = '$userid' AND
        rvp.recordingid = '" . $this->id . "' AND
        r.id            = rvp.recordingid
      LIMIT 1
    ");

    $progressModel = $this->bootstrap->getModel('recording_view_progress');
    $record        = array(
      'recordingid' => $this->id,
      'userid'      => $userid,
      'timestamp'   => date('Y-m-d H:i:s'),
      'position'    => $lastposition,
    );

    // ha a lastposition ennel nagyobb akkor nem csinalunk semmit
    if ( $row and $lastposition > $row['position'] + $updatesec + $extrasec ) {
      $ret['success'] = false;
      $this->updateSession( $organization, $userid, $lastposition, $sessionid );
      $this->endTrans();
      return $ret;
    } elseif ( !$row ) { // nincs meg progress report, insert

      $progressModel->insert( $record );
      $ret['success'] = true;

      // le kell kerdeznunk a recording hosszat
      $row = $this->db->getRow("
        SELECT
          '$lastposition' AS position,
          '0' AS expired,
          r.masterlength,
          r.contentmasterlength
        FROM recordings AS r
        WHERE r.id = '" . $this->id . "'
        LIMIT 1
      ");

    } elseif ( $row['position'] < $lastposition and !$row['expired'] ) {
      // minden oke, update

      $progressModel->id = $row['id'];
      unset( $record['timestamp'] ); // nem updatelhetunk timestampet hogy kideruljon hogy kifutottunk az idobol
      $progressModel->updateRow( $record );
      $ret['success'] = true;
      $row['position'] = $lastposition;

    } elseif ( $row['expired'] ) { // reset

      // csak akkor resetelunk ha nem nezte vegig
      if ( !$this->isRecordingWatched( $organization, $row['position'], $row ) ) {
        // tul sok kimaradas volt, reseteljuk nullara a poziciot, kezdje elorol
        // ez updateli a timestamp-et is, ergo ujra kezdjuk a timeoutot is
        $ret['success'] = false;
        $row['position'] = $record['position'] = 0;
        $progressModel->id  = $row['id'];
        $progressModel->updateRow( $record );
      }

    } // ami maradt hogy a jelentett ertek <= mint a jelenlegi ertek

    $ret['watched'] = $this->isRecordingWatched(
      $organization, $row['position'], $row, $ret
    );

    $success = $this->updateSession( $organization, $userid, $lastposition, $sessionid );
    if ( !$success and !$ret['watched'] ) {
      // lejart a view_session, mig a view_progress nem es nem nezte vegig a user
      // akkor tortenhet ha a browser megnyitva marad es ugyanaz marad a 
      // viewsessionid is napokig
      // megintcsak tul sok kimaradas volt, reset nullara
      $ret['success'] = false;
      $row['position'] = $record['position'] = 0;
      $progressModel->id  = $row['id'];
      $progressModel->updateRow( $record );
    }

    $this->endTrans();
    return $ret;

  }

  private function updateSession( $organization, $userid, $position, $sessionid ) {

    $this->ensureID();
    $recordingid = $this->db->qstr( $this->id );
    $userid      = $this->db->qstr( $userid );
    $sessionid   = $this->db->qstr( $sessionid );
    $timestamp   = $this->db->qstr( date('Y-m-d H:i:s') );
    $position    = $this->db->qstr( $position );
    $timeout     = $organization['viewsessiontimeoutminutes'];

    $this->startTrans();
    $existing = $this->db->getRow("
      SELECT
        id,
        IF(timestampfrom < DATE_SUB($timestamp, INTERVAL $timeout MINUTE), 1, 0) AS expired
      FROM recording_view_sessions
      WHERE
        sessionid   = $sessionid AND
        recordingid = $recordingid
      ORDER BY id DESC
      LIMIT 1
    ");

    if ( !$existing ) {
      $this->db->execute("
        INSERT INTO recording_view_sessions
          (
            recordingid,
            userid,
            sessionid,
            timestampfrom,
            timestampuntil,
            positionfrom,
            positionuntil
          ) VALUES
          (
            $recordingid,
            $userid,
            $sessionid,
            $timestamp,
            $timestamp,
            $position,
            $position
          )
      ");
    } elseif ( !$existing['expired'] ) {
      $this->db->execute("
        UPDATE recording_view_sessions SET
          timestampuntil = IF( $position <= positionuntil, timestampuntil, $timestamp),
          positionuntil  = IF( $position <= positionuntil, positionuntil, $position)
        WHERE id = '" . $existing['id'] . "'
        LIMIT 1
      ");
    } elseif ( $existing['expired'] )
      return false;

    return true;
  }

  public function searchStatistics( $user, $searchterm, $organizationid, $start = 0, $limit = 20 ) {

    $searchterm  = str_replace( ' ', '%', $searchterm );
    $searchterm  = $this->db->qstr( '%' . $searchterm . '%' );
    $select = "
      u.id AS userid,
      u.externalid,
      IF(
        u.nickname IS NULL OR LENGTH(u.nickname) = 0,
        CONCAT(u.namelast, '.', u.namefirst),
        u.nickname
      ) AS nickname,
      u.email,
      u.nameprefix,
      u.namefirst,
      u.namelast,
      u.nameformat,
      r.id,
      r.title,
      r.subtitle,
      r.indexphotofilename,
      r.timestamp,
      r.recordedtimestamp,
      r.mediatype,
      GREATEST(r.masterlength, r.contentmasterlength) AS length,
      (
        1 +
        IF( r.title LIKE $searchterm, 2, 0 ) +
        IF( r.subtitle LIKE $searchterm, 1, 0 ) +
        IF( r.description LIKE $searchterm, 1, 0 ) +
        IF( r.primarymetadatacache LIKE $searchterm, 1, 0 )
      ) AS relevancy,
      IF(
        r.isfeatured <> 0 AND
        (
          r.featureduntil IS NULL OR
          r.featureduntil >= NOW()
        ),
        1,
        0
      ) AS currentlyfeatured
    ";

    $from = "
      users AS u,
      recordings AS r"
    ;

    $where = "
      r.organizationid = '$organizationid' AND
      r.userid = u.id AND
      (
        r.primarymetadatacache LIKE $searchterm OR
        r.additionalcache LIKE $searchterm
      )
    ";

    $ret = $this->db->getArray("
      " . self::getUnionSelect( $user, $select, $from, $where ) . "
      ORDER BY relevancy DESC
      LIMIT $start, $limit
    ");

    $this->addPresentersToArray( $ret, false, $organizationid );
    return $ret;
  }

  public function search( $searchterm, $userid, $organizationid ) {

    $searchterm  = str_replace( ' ', '%', $searchterm );
    $searchterm  = $this->db->qstr( '%' . $searchterm . '%' );

    $query   = "
      SELECT
        (
          1 +
          IF( r.title LIKE $searchterm, 2, 0 ) +
          IF( r.subtitle LIKE $searchterm, 1, 0 ) +
          IF( r.description LIKE $searchterm, 1, 0 ) +
          IF( r.primarymetadatacache LIKE $searchterm, 1, 0 )
        ) AS relevancy,
        r.id,
        r.userid,
        r.organizationid,
        r.title,
        r.subtitle,
        r.description,
        r.indexphotofilename,
        r.recordedtimestamp,
        r.numberofviews,
        r.rating,
        r.status,
        r.isfeatured,
        IF(
          r.isfeatured <> 0 AND
          (
            r.featureduntil IS NULL OR
            r.featureduntil >= NOW()
          ),
          1,
          0
        ) AS currentlyfeatured
      FROM recordings AS r
      WHERE
        r.status NOT IN('markedfordeletion', 'deleted') AND
        (
          r.primarymetadatacache LIKE $searchterm OR
          r.additionalcache LIKE $searchterm
        ) AND
        (
          r.organizationid = '$organizationid' OR
          (
            r.userid         = '$userid' AND
            r.organizationid = '$organizationid'
          )
        )
      ORDER BY relevancy DESC
      LIMIT 20
    ";

    return $this->db->getArray( $query );

  }

  public function getDownloadInfo( $staticuri, $user, $organization ) {
    $this->ensureObjectLoaded();
    $canDownload = false;

    if (
         $organization['caneditordownloadrecordings'] and
         $user and
         ( $user['iseditor'] or $user['isadmin'] or $user['isclientadmin'] )
       )
      $canDownload = true;

    if (
         !$this->row['isdownloadable'] and !$this->row['isaudiodownloadable'] and
         !$canDownload
       )
      return array();

    $ret      = array();
    $treedir  = \Springboard\Filesystem::getTreeDir( $this->id );
    $basedir  = $staticuri . 'files/recordings/' . $treedir . '/';
    $versions = $this->db->getArray("
      SELECT
        rv.filename,
        rv.qualitytag,
        ep.type
      FROM
        recordings_versions AS rv,
        encoding_profiles AS ep
      WHERE
        rv.recordingid       = '" . $this->id . "' AND
        rv.status            = 'onstorage' AND
        rv.encodingprofileid = ep.id
      ORDER BY rv.bandwidth DESC
    ");

    // ha letolthet a user akkor a tomb elejere rakjuk a master fileokat
    if ( $canDownload ) {
      $urlTemplate = "master/<id>_<type>.<ext>";
      $urlData = array(
        '<id>'       => $this->id,
        '<type>'     => 'content',
        '<ext>'      => $this->row['contentmastervideoextension'],
      );

      if ( $this->row['contentmastervideofilename'] ) {
        // 111_content.mp4

        array_unshift( $versions, array(
            'filename'   => strtr( $urlTemplate, $urlData ),
            'qualitytag' => 'original',
            'type'       => 'content',
          )
        );
      }

      // 111_video.mp4
      // 111_audio.mp3
      $urlData['<ext>'] = $this->row['mastervideoextension'];

      if ( $this->row['mastermediatype'] == 'audio' )
        $urlData['<type>'] = 'audio';
      else
        $urlData['<type>'] = 'video';

      array_unshift( $versions, array(
          'filename'   => strtr( $urlTemplate, $urlData ),
          'qualitytag' => 'original',
          'type'       => 'recording',
        )
      );

    }

    if ( $canDownload or $this->row['isaudiodownloadable'] ) {

      foreach( $versions as $version ) {
        if ( $version['qualitytag'] != 'audio' )
          continue;

        $ret['audio'] = array(
          'url'        => $basedir .
            \Springboard\Filesystem::getWithoutExtension( $version['filename'] ) .
            ',' . \Springboard\Filesystem::filenameize( $this->row['title'] ) . '.' .
            \Springboard\Filesystem::getExtension( $version['filename'] )
          ,
          'qualitytag' => $version['qualitytag'],
        );
        break;
      }

    }

    if ( $canDownload or $this->row['isdownloadable'] ) {

      foreach( $versions as $version ) {

        $filename =
          \Springboard\Filesystem::getWithoutExtension( $version['filename'] ) .
          ',' . \Springboard\Filesystem::filenameize( $this->row['title'] ) . '.' .
          \Springboard\Filesystem::getExtension( $version['filename'] )
        ;

        $data = array(
          'url'        => $basedir . $filename,
          'qualitytag' => $version['qualitytag'],
        );

        if ( $version['type'] == 'recording' and !isset( $ret['master'] ) ) {
          $ret['master'] = $data;
        } elseif ( $version['type'] == 'content' and !isset( $ret['contentmaster'] ) ) {
          $ret['contentmaster'] = $data;
        } elseif ( $version['type'] == 'pip' and !isset( $ret['pip'] ) ) {
          $ret['pip'] = $data;
        }

      }

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

  public function isRecordingWatched( $organization, $position, $row = null, &$info = null ) {
    if ( !$row ) {
      $this->ensureObjectLoaded();
      $row = $this->row;
    }

    $length = max(
      (int)$this->row['masterlength'],
      (int)$this->row['contentmasterlength']
    );
    $watchedpercent = round( ($position / $length) * 100 );
    $needpercent    = $organization['elearningcoursecriteria'];
    if ( $info ) {
      $info['watchedpercent'] = $watchedpercent;
      $info['needpercent']    = $needpercent;
    }

    return $watchedpercent >= $needpercent;
  }

  public function checkViewProgressTimeout( $organization, $userid ) {
    $this->ensureObjectLoaded();
    $timeout = $organization['viewsessiontimeoutminutes'];
    $row = $this->db->getRow("
      SELECT
        rvp.id,
        rvp.position,
        IF(
          rvp.timestamp < DATE_SUB(NOW(), INTERVAL $timeout MINUTE),
          1,
          0
        ) AS expired
      FROM recording_view_progress AS rvp
      WHERE
        rvp.userid      = '$userid' AND
        rvp.recordingid = '" . $this->id . "'
      ORDER BY id DESC
      LIMIT 1
    ");

    if ( !$row )
      return false;

    // ha megnezte akkor nem resetelunk semmit, nincs problema
    if ( $this->isRecordingWatched( $organization, $row['position'] ) )
      return false;

    if ( $row['expired'] ) {

      // reset
      $this->db->execute("
        UPDATE recording_view_progress
        SET
          position  = 0,
          timestamp = NOW()
        WHERE id = '" . $row['id'] . "'
        LIMIT 1
      ");

    }

    return (bool) $row['expired'];
  }

  public function searchAddSlidesToArray( $searchterm, &$arr ) {
    // a $searchterm-et mar escapelve varjuk!!!
    $recordingids = array();
    $recidToIndex = array();
    foreach( $arr as $key => $row ) {
      if ( isset( $row['type'] ) and $row['type'] != 'recording' )
        continue;

      $recordingids[] = $row['id'];
      $recidToIndex[ $row['id'] ] = $key;
      $arr[ $key ]['slides'] = array();
    }

    if ( empty( $recordingids ) )
      return $arr;

    // egyet kivonunk a positionsec-bol hogy biztosan lassuk a slidot
    $slides = $this->db->query("
      SELECT
        *,
        IF(positionsec = 0, 0, positionsec -1) AS positionsec
      FROM ocr_frames
      WHERE
        status = 'onstorage' AND
        recordingid IN('" . implode("', '", $recordingids ) . "') AND
        ocrtext IS NOT NULL AND
        LENGTH(ocrtext) > 0 AND
        ocrtext LIKE $searchterm
      ORDER BY recordingid, positionsec
    ");
    foreach( $slides as $row ) {
      $key = $recidToIndex[ $row['recordingid'] ];
      $arr[ $key ]['slides'][] = $row;
    }

    return $arr;
  }

  public function getArray( $start = false, $limit = false, $where = false, $orderby = false ) {

    if ( $where )
      $this->addTextFilter( $where );

    return $this->db->getArray("
      SELECT " . self::getRecordingSelect() . "
      FROM " . $this->table .
      $this->getFilter() .
      $this->sqlOrder( $orderby ) .
      $this->sqlLimit( $start, $limit )
    );
  }

  public function getStatistics( $info ) {
    $organizationid = $info['organizationid'];
    $startts = $this->db->qstr( $info['datefrom'] );
    $endts   = $this->db->qstr( $info['dateuntil'] );
    $tables  = '';
    $where   = array(
      "vso.timestamp >= $startts",
      "vso.timestamp <= $endts",
      "r.organizationid = '$organizationid'",
    );

    $extraselect = '';
    if ( $info['extrainfo'] )
      $extraselect = "
        vso.ipaddress AS sessionipaddress,
        vso.useragent AS sessionuseragent,
      ";

    if ( !empty( $info['recordingids'] ) )
      $where[] = "vso.recordingid IN('" . implode("', '", $info['recordingids'] ) . "')";

    if ( !empty( $info['groupids'] ) ) {
      $tables .= ", groups_members AS gm";
      $where[] = "gm.groupid IN('" . implode("', '", $info['groupids'] ) . "')";
      $where[] = "gm.userid = u.id";
    }

    if ( !empty( $info['userids'] ) )
      $where[] = "u.id IN('" . implode("', '", $info['userids'] ) . "')";

    $where = implode(" AND\n  ", $where );
    return $this->db->query("
      SELECT
        u.id AS userid,
        u.email,
        u.externalid,
        r.id AS recordingid,
        r.recordedtimestamp AS timestamp,
        r.timestamp AS uploadedtimestamp,
        r.title,
        ROUND( GREATEST(r.masterlength, IFNULL(r.contentmasterlength, 0)) ) AS recordinglength,
        IF(
          vso.positionuntil - vso.positionfrom < 0,
          0,
          vso.positionuntil - vso.positionfrom
        ) AS sessionwatchedduration,
        ROUND(
          (
            (vso.positionuntil - vso.positionfrom) /
            GREATEST(r.masterlength, IFNULL(r.contentmasterlength, 0))
          ) * 100
        ) AS sessionwatchedpercent,
        $extraselect
        vso.startaction,
        vso.stopaction,
        vso.viewsessionid,
        vso.positionfrom AS sessionwatchedfrom,
        vso.positionuntil AS sessionwatcheduntil,
        vso.timestamp AS sessionwatchedtimestamp
      FROM
        view_statistics_ondemand AS vso
        LEFT JOIN users AS u ON(
          u.id = vso.userid
        ),
        recordings AS r
        $tables
      WHERE
        r.id = vso.recordingid AND
        $where
      ORDER BY vso.id
    ");
  }

  private function getSQLFromChannelSubscriptions( $user, $organizationid ) {
    $sql = $this->getRecordingsSQL( $organizationid );
    $sql['select'] .= ",
      c.id AS channelid,
      c.title AS channeltitle
    ";
    $sql['from'] .= ",
      channels_recordings AS cr,
      subscriptions AS sub,
      channels AS c
    ";
    $sql['where'] .= " AND
      cr.recordingid = r.id AND
      cr.channelid = c.id AND
      c.id = sub.channelid AND
      sub.userid = '" . $user['id'] . "'
    ";

    return $sql;
  }

  public function getCountFromChannelSubscriptions( $user, $organizationid ) {
    if ( !$user['id'] )
      return 0;

    $sql = $this->getSQLFromChannelSubscriptions( $user, $organizationid );
    $sql['select'] = 'COUNT(DISTINCT r.id) AS count';
    return $this->db->getOne("
      SELECT " . $sql['select'] . "
      FROM " . $sql['from'] . "
      WHERE ". $sql['where'] . "
      LIMIT 1
    ");
  }

  public function getArrayFromChannelSubscriptions( $start, $limit, $order, $user, $organizationid ) {
    if ( !$user['id'] )
      return array();

    if ( !$order )
      $order = 'cr.id DESC';

    $sql = $this->getSQLFromChannelSubscriptions( $user, $organizationid );
    return $this->db->getArray("
      SELECT " . $sql['select'] . "
      FROM " . $sql['from'] . "
      WHERE " . $sql['where'] . "
      GROUP BY r.id
      ORDER BY $order " .
      ( is_numeric( $start ) ? 'LIMIT ' . $start . ', ' . $limit : "" )
    );

  }

  private function getCombinedRatingSQL() {
    return "
      combinedratingpermonth = IFNULL((
        ratingthismonth  *
        ( 100 * numberofratingsthismonth / numberofviewsthismonth ) *
        numberofviewsthismonth
      ), 0),
      combinedratingperweek = IFNULL((
        ratingthisweek *
        ( 100 * numberofratingsthisweek / numberofviewsthisweek ) *
        numberofviewsthisweek
      ), 0)
    ";
  }

  public function getUsersHistory( $user, $organizationid, $start, $limit, $order ) {
    if ( !$user or !$user['id'] )
      throw new \Exception("Non-valid user passed");

    $select = self::getRecordingSelect('r.') . ",
      ch.timestamp AS contenthistorytimestamp
    ";
    $from = "
      usercontenthistory AS ch,
      recordings AS r
    ";
    $where = "
      r.organizationid = '$organizationid' AND
      r.id = ch.recordingid AND
      ch.userid = '" . $user['id'] . "' AND
      ch.recordingid IS NOT NULL
    ";

    return $this->db->getArray(
      self::getUnionSelect( $user, $select, $from, $where, '0', 'GROUP BY r.id' ) . "
      ORDER BY $order
      LIMIT $start, $limit
    ");
  }

  public function getConversionInformation( $ids, $user, $organizationid ) {
    if ( empty( $ids ) )
      return array();

    $extrawhere = '';
    // normal user, ellenorizzuk hogy ove e minden
    if ( !$user['iseditor'] and !$user['isclientadmin'] and !$user['isadmin'] ) {
      $extrawhere = "AND
        r.userid = '" . $user['id'] . "'
      ";
    }

    $limit = count( $ids );
    $rows = $this->db->getArray("
      SELECT
        rv.recordingid,
        rv.status,
        r.status AS recordingstatus
      FROM
        recordings_versions AS rv,
        recordings AS r
      WHERE
        rv.recordingid IN('" . implode("', '", $ids ) . "') AND
        r.id = rv.recordingid AND
        r.organizationid = '$organizationid'
        $extrawhere
    ");

    $byRecording = array();
    foreach( $rows as $row ) {
      if ( !isset( $byRecording[ $row['recordingid'] ] ) )
        $byRecording[ $row['recordingid'] ] = array();

      $byRecording[ $row['recordingid'] ][] = $row;
    }

    $ret = array();
    $l = $this->bootstrap->getLocalization();
    foreach( $byRecording as $recid => $rows ) {
      $n = count( $rows );
      $foundOnstorage = 0;
      $foundFailed = 0;

      foreach( $rows as $row ) {
        if ( $row['status'] == 'onstorage' ) {
          $foundOnstorage++;
          continue;
        }

        if ( substr( $row['status'], 0, strlen('failed') ) !== 'failed' )
          continue;

        $foundFailed++;
      }

      $percent = floor( ( $foundOnstorage / $n ) * 100 );
      $status = $row['recordingstatus']; // a $row az utolso row, foreach itthagyta

      $ret[] = array(
        'recordingid' => $recid,
        'percent'     => $percent,
        'status'      => $status,
        'statusLabel' => $l->getLov( 'recordingstatus', null, $status ),
      );
    }

    return $ret;
  }
}
