<?php
///////////////////////////////////////////////////////////////////////////////////////////////////
//
//	CORRIGATE MEDIAINFO'S INCORRECT VIDEO DURATION VALUES IN DATABASE
//
///////////////////////////////////////////////////////////////////////////////////////////////////

define('STORAGE_PATH', realpath('/srv/vsq_storage/dev.videosquare.eu/') .'/');  #stream server/dev
// define('STORAGE_PATH', realpath('/srv/vsq_storage/videosquare.eu/') .'/');  #stream server/vsqlive
define('BASE_PATH',     realpath( '/var/www/dev.videosquare.eu/' ) . '/' );	#stream server/dev
// define('BASE_PATH',     realpath( '/var/www/videosquare.eu/' ) . '/' );	#stream server/vsqlive
//define('BASE_PATH',     realpath( '/home/conv/dev.videosquare.eu' ) . '/' );	#conv server

echo BASE_PATH ."\n";
define('PRODUCTION', false );
define('DEBUG', false );
include_once( BASE_PATH . 'libraries/Springboard/Application/Cli.php');

// Utils
include_once(BASE_PATH. 'modules/Jobs/job_utils_base.php');
include_once(BASE_PATH. 'modules/Jobs/job_utils_log.php');
include_once(BASE_PATH. 'modules/Jobs/job_utils_status.php');
include_once(BASE_PATH. 'modules/Jobs/job_utils_media.php');

set_time_limit(0);

// Init
$app = new Springboard\Application\Cli(BASE_PATH, FALSE);

// Load jobs configuration file
$app->loadConfig('modules/Jobs/config_jobs.php');
$jconf = $app->config['config_jobs'];

// Establish database connection
$db = null;
$db = db_maintain();

//////////////////////////////////////////// TEST BLOCK ///////////////////////////////////////////
$config = array(
        'mediainfo_identify' => 'mediainfo --full --output=XML %s 2>&1',
        'recordings_seconds_minlength' => 5
);

//$qryrecordings = "SELECT id, mastervideoextension, masterlength, status FROM recordings";
$qryrecordings = "SELECT id, mastervideoextension, contentmastervideoextension, contentmasterlength, masterstatus, contentstatus FROM recordings";
$qryrecordings = "SELECT id, mastervideoextension, contentmastervideoextension, contentmasterlength, masterstatus, status, contentstatus FROM recordings WHERE contentvideoreshq IS NOT NULL ";
$recordingarray = null;

// $recordings = new Recordings();
$recordings = $app->bootstrap->getModel('recordings');

try {
	$recordingarray = $db->execute($qryrecordings);
	$recordingarray->recordCount() == 0 ? print_r("[ERROR] No data returned from db.\n") : null ;
} catch (exception $ex) {
	print_r("[ERROR] Database query failed.\n". $ex->getMessage() ."\n");
}
do {
  print_r("\n");
	$rec = $recordingarray->fields;
	// $path = STORAGE_PATH ."recordings/". ($rec['id'] - (1000 * round($rec['id'] / 1000, 0))) ."/". $rec['id'] ."/master/";
	$path = STORAGE_PATH ."recordings/". ($rec['id'] - (1000 * round($rec['id'] / 1000, 0))) ."/". $rec['id'] ."/";
	$videopath = $path . $rec['id'] ."_video.". $rec['mastervideoextension'];
	$contentpath = $path . $rec['id'] ."_content.". (empty($rec['contentmastervideoextension']) ? $rec['mastervideoextension'] : $rec['contentmastervideoextension']);
	$videolqpath = $path . $rec['id'] ."_video_lq.mp4";
	$videohqpath = $path . $rec['id'] ."_video_hq.mp4";
	$contentlqpath = $path . $rec['id'] ."_content_lq.mp4";
	$contenthqpath = $path . $rec['id'] ."_content_hq.mp4";
	
  $filepath = $contenthqpath; // Itt adjuk meg melyik fajlnevet hasznaljuk az elore generaltak kozul
  $lblstatus = 'status';  // statusmezo neve, amelyre szurest vegzunk
  $dbkey = 'videoreshq';  // Ezzel a kulccsal hasonlitjuk ossze az analyze-zal kinyert adatot
  $param = 'mastervideores'; // Itt adjuk meg annak a parameternek a nevet, amit a metaadatokbol akarunk kinyerni (analyze)
  
	// filter deleted recordings:
	if ($rec[$lblstatus] == 'deleted' || $rec[$lblstatus] === null) {
		print_r("Recording #". $rec['id'] ." is deleted. Skipping entry.\n");
		continue;	
	} elseif ( file_exists( $filepath) === false) {
		print_r("[ERROR] Path doesn't exists! (id - ". $rec['id'] .", $filepath). Skipping entry.\nSTATUS-". var_export($rec[$lblstatus]) ."\n");
		// print_r("[ERROR] Path doesn't exists! (id - ". $rec['id'] .", $filepath). Skipping entry.\nCONTENTSTATUS-". var_export($rec['contentstatus']) ."\n");
		continue;
	} else {
		try {
			$recordings->analyze($filepath);
		} catch (Exception $ex) {
			print_r("[ERROR] Analyze failed! (id - ". $rec['id'] ."\nError message:\n\n". $ex->getMessage()."\n");
		}
		echo "analyze $filepath - ". $dbkey ." = ". $recordings->metadata[$param] ."\n";
		updateData(array('id' => $rec['id']), $dbkey, $recordings->metadata[$param], false);
	}
} while ($recordingarray->MoveNext() === true);

print_r("> Updating finished!\n");
exit(0);

//////////////////////////////////////////// TEST BLOCK ///////////////////////////////////////////


///////////////////////////////////////////////////////////////////////////////////////////////////
//
//	FUNCTION updateData()
//
///////////////////////////////////////////////////////////////////////////////////////////////////
//	Function to override previous length values in recordings table
///////////////////////////////////////////////////////////////////////////////////////////////////
// class Recordings {
	function updateData($recording, $key, $value, $debug = true) {
		global $db;
		$checkqry = "SELECT	". $key ." FROM recordings WHERE id = ". $recording['id'];
		$updateqry = empty($value) ? null : ("UPDATE recordings SET ". $key ." = '". $value ."' WHERE id = ". $recording['id']);
		$dbresult = null;
		try {
			$dbresult = $db->execute($checkqry);
		} catch( exception $ex) {
			print_r($ex->getMessage() ."\n");
			exit;
		}
		
		if ($dbresult->recordCount() == 0) {
			print_r("[ERROR] No data has been returned.\n");
			exit;
		}
		
		$dbresult = $dbresult->fields;
		if ($dbresult[$key] != $value) {
			print_r("[WARN] Different values. (original: ". $dbresult[$key] ."/result: ". $value .") Updating");
			if ($debug === false) {
				try {
					print_r(":\n". $updateqry);
					$db->execute($updateqry);
				} catch( exception $ex) {
					print_r("\n[ERROR] - db update failed.\n". $ex->getMessage() ."\n");
					exit;
				}
				print_r(" - done.\n");
			} else {
				print_r(" needed on recording #". $recording['id'] ."\n");
				print_r("update:\n$updateqry\n");	
			}
		}
		// no output message when values are matching
	}
	/*
	function SXEtoArray(SimpleXMLElement $xml) {
		$array = (array) $xml;
		foreach ( array_slice($array, 0) as $key => $value ) {
			if ( $value instanceof SimpleXMLElement ) {
				$array[$key] = empty($value) ? NULL : json_decode(json_encode($value), true);
			}
		}
		return $array;
	}
	
	public function getMediainfoNumericValue( $elem, $isfloat = false, $scale = 1 ) {
		$elem = strval( $elem );
		if ( !$elem )
			return null;

		$elem = str_replace( ' ', '', $elem );
		if ( $isfloat )
			return (float) $elem * $scale;
		else
			return (integer) $elem * $scale;
	}
	
	public function analyze( $filename, $originalfilename = null ) {
		global $config;
		//$config = $this->bootstrap->config;
		
		if ( !$originalfilename )
			$originalfilename = $filename;

		$cmd = sprintf( $config['mediainfo_identify'], escapeshellarg( $filename ) );
		exec( $cmd, $output, $return );
		$output = implode("\n", $output );

		if ( $return )
			throw new \Exception('Mediainfo returned non-zero exit code, output was: ' . $output , $return );

		//if ( $this->bootstrap->debug )
		//	var_dump( $output );

		$xml     = new \SimpleXMLElement( $output );
		$general = current( $xml->xpath('File/track[@type="General"][1]') );
		$video   = current( $xml->xpath('File/track[@type="Video"][1]') );
		$audio   = current( $xml->xpath('File/track[@type="Audio"][1]') );
		
		if ( !($general instanceof SimpleXMLElement) or ( !$video and !$audio ) )
			throw new InvalidFileTypeException('Unrecognized file, output was: ' . $output );
		$general = $this->SXEtoArray($general);
			
		if ( $video and $audio )
			$mediatype = 'video';
		elseif ( !$video and $audio )
			$mediatype = 'audio';
		elseif ( $video and !$audio )
			$mediatype = 'videoonly';
		else
			throw new \Exception("Cannot happen wtf, output was: " . $output );

		$extension         = \Springboard\Filesystem::getExtension( $originalfilename )?: $general['File_extension'];;
		$videocontainer    = $general['Format'][0]?: $extension;
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

		if ( $general['Duration'][0] ) {
			$videolength = round($general['Duration'][0] / 1000, 2);
		}

		if ( $videolength <= $config['recordings_seconds_minlength'] )
			throw new InvalidLengthException('Recording length was less than ' . $config['recordings_seconds_minlength'] );
			
		if ( $video instanceof SimpleXMLElement ) {
			$video = $this->SXEtoArray($video);
			
			if ( $video['Duration'][0] ) {
				$videolength = round($video['Duration'][0] / 1000, 2);	// Duration[0] is in milisecs
			} else {
				throw new InvalidLengthException('Length not found for the media, output was ' . $output );
			}

			$videostreamid  = array_key_exists('ID', $video) === true ? $this->getMediainfoNumericValue( $video['ID'][0]): null;
			$videofps       = $this->getMediainfoNumericValue( $video['Frame_rate'][0], true );
			$videocodec     = $video['Format'];
			
			if ( is_array( $videocodec)) {
				echo "\nvideo->Format !!!\n";
				if ( array_key_exists('Format_Info', $video) )
					$videocodec .= ' (' . $video['Format_Info'] . ')';
				if ( array_key_exists('Format_profile', $video) )	// There's no such entry in the current video values
					$videocodec .= ' / ' . $video['Format_profile'];
			}
			
			if ( array_key_exists( 'Bit_rate_mode', $video)) { #Ain't nothing use this attribute, WTF man?!
				if ( !is_array( $video['Bit_rate_mode'])) {
				// sometimes it's placed inside of a subarray, sometimes not, needs to be checked every time.
					if ( $video['Bit_rate_mode'] == 'Constant' )
						$videobitratemode = 'cbr';
					else
						$videobitratemode = 'vbr';
				} else {
					$videobitratemode = $video['Bit_rate_mode'][1];
				}
			}
			
			if ( $video['Width'][0] and $video['Height'][0] ) {
				$videores = sprintf(
					'%sx%s',
					$this->getMediainfoNumericValue( $video['Width'][0] ),
					$this->getMediainfoNumericValue( $video['Height'][0] )
				);

				if ( $video['Display_aspect_ratio'][0] )
					$videodar = $this->getMediainfoNumericValue( $video['Display_aspect_ratio'][0], true);

				$videobitrate = $video['Bit_rate'][0] + 0 ?: $general['Overall_bit_rate'][0] + 0;
				$videobitrate = $this->getMediainfoNumericValue( $videobitrate, false, 1);
			}
			
			if (isset($video['Scan_type']) && !empty($video['Scan_type'])) {
				$videoisinterlaced = $video['Scan_type'][0] == "Progressive" ? 0 : 1;
			} elseif (isset($general['Interlacement']) && !empty($video['Interlacement'])) {
				$videoisinterlaced = $general['Interlacement'][1] == "Progressive" ? 0 : 1;
			} else {
				throw new Exception('Length not found for the media, output was ' . $output );
			}
		}

		if ( $audio instanceof SimpleXMLElement) {
			$audio = $this->SXEtoArray($audio);
			
			$audiocodec    = $audio['Format'];
			if ( array_key_exists('Format_Info', $audio))
				$audiocodec .= ' ( ' . $audio['Format_Info'] . ' ) ';
			if ( array_key_exists('Format_profile', $audio))
				$audiocodec .= ' / ' . $audio['Format_profile'];

			$audiostreamid = array_key_exists('ID', $audio) === true ? $this->getMediainfoNumericValue( $audio['ID'][0]) : null;
			$audiofreq     = $this->getMediainfoNumericValue( $audio['Sampling_rate'][0] , false, 1 );
			$audiobitrate  = $this->getMediainfoNumericValue( $audio['Bit_rate'][0], false, 1 );
			$audiochannels = $this->getMediainfoNumericValue( $audio['Channel_s_'][1] );

			$audiomode = array_key_exists('Bit_rate_mode', $audio) ? $audio['Bit_rate_mode'][0] : null;
			
			if ( array_key_exists('Compression_mode', $audio) === true)
				$audioquality = $audio['Compression_mode'][0] == 'Lossy' ? 'lossy' : 'lossless' ;
			
			if ( !$videolength ) {
				$videolength = array_key_exists('Duration', $audio) ? round($audio['Duration'][0] / 1000, 2) : null;
			}
			
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
			'mastervideobitratemode'     => $videobitratemode,	#Ain't nothing use this attribute, WTF man?!
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
}*/
?>