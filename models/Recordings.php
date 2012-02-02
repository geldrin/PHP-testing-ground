<?php
namespace Model;

class InvalidFileTypeException extends \Exception {}
class InvalidLengthException extends \Exception {}
class InvalidVideoResolutionException extends \Exception {}

class Recordings extends \Springboard\Model {
  
  public function insertUploadingRecording( $userid, $organizationid, $languageid, $title ) {
    
    $recording = array(
      'userid'          => $userid,
      'organizationid'  => $organizationid,
      'languageid'      => $languageid,
      'title'           => $title,
      'mediatype'       => $this->metadata['mastermediatype'],
      'status'          => 'uploading',
      'masterstatus'    => 'uploading',
      'accesstype'      => 'public',
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
  
}
