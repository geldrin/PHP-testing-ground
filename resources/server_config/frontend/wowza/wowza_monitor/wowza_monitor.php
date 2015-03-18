<?php

/*

<WowzaMediaServer>
	<ConnectionsCurrent>6</ConnectionsCurrent>
	<ConnectionsTotal>293</ConnectionsTotal>
	<ConnectionsTotalAccepted>287</ConnectionsTotalAccepted>
	<ConnectionsTotalRejected>6</ConnectionsTotalRejected>
	<MessagesInBytesRate>403858.0</MessagesInBytesRate>
	<MessagesOutBytesRate>175126.0</MessagesOutBytesRate>
	<VHost>
		<Name>_defaultVHost_</Name>
		<TimeRunning>505435.573</TimeRunning>
		<ConnectionsLimit>0</ConnectionsLimit>
		<ConnectionsCurrent>6</ConnectionsCurrent>
		<ConnectionsTotal>293</ConnectionsTotal>
		<ConnectionsTotalAccepted>287</ConnectionsTotalAccepted>
		<ConnectionsTotalRejected>6</ConnectionsTotalRejected>
		<MessagesInBytesRate>403858.0</MessagesInBytesRate>
		<MessagesOutBytesRate>175126.0</MessagesOutBytesRate>
		<Application>
			<Name>devvsqlive</Name>
			<Status>loaded</Status>
			<TimeRunning>505434.616</TimeRunning>
			<ConnectionsCurrent>0</ConnectionsCurrent>
			<ConnectionsTotal>0</ConnectionsTotal>
			<ConnectionsTotalAccepted>0</ConnectionsTotalAccepted>
			<ConnectionsTotalRejected>0</ConnectionsTotalRejected>
			<MessagesInBytesRate>403855.0</MessagesInBytesRate>
			<MessagesOutBytesRate>0.0</MessagesOutBytesRate>
			<ApplicationInstance>
				<Name>_definst_</Name>
				<TimeRunning>505434.575</TimeRunning>
				<ConnectionsCurrent>0</ConnectionsCurrent>
				<ConnectionsTotal>0</ConnectionsTotal>
				<ConnectionsTotalAccepted>0</ConnectionsTotalAccepted>
				<ConnectionsTotalRejected>0</ConnectionsTotalRejected>
				<MessagesInBytesRate>403855.0</MessagesInBytesRate>
				<MessagesOutBytesRate>0.0</MessagesOutBytesRate>
				<Stream>
					<Name>461272.stream</Name>
					<SessionsFlash>0</SessionsFlash>
					<SessionsCupertino>0</SessionsCupertino>
					<SessionsSanJose>0</SessionsSanJose>
					<SessionsSmooth>0</SessionsSmooth>
					<SessionsRTSP>0</SessionsRTSP>
					<SessionsTotal>0</SessionsTotal>
				</Stream>
				<Stream>
					<Name>549088.stream</Name>
					<SessionsFlash>0</SessionsFlash>
					<SessionsCupertino>0</SessionsCupertino>
					<SessionsSanJose>0</SessionsSanJose>
					<SessionsSmooth>0</SessionsSmooth>
					<SessionsRTSP>0</SessionsRTSP>
					<SessionsTotal>0</SessionsTotal>
				</Stream>
			</ApplicationInstance>
		</Application>
		<Application>
			<Name>vsq</Name>
			<Status>loaded</Status>
			<TimeRunning>10918.991</TimeRunning>
			<ConnectionsCurrent>6</ConnectionsCurrent>
			<ConnectionsTotal>21</ConnectionsTotal>
			<ConnectionsTotalAccepted>21</ConnectionsTotalAccepted>
			<ConnectionsTotalRejected>0</ConnectionsTotalRejected>
			<MessagesInBytesRate>3.0</MessagesInBytesRate>
			<MessagesOutBytesRate>175126.0</MessagesOutBytesRate>
			<ApplicationInstance>
				<Name>_definst_</Name>
				<TimeRunning>10918.976</TimeRunning>
				<ConnectionsCurrent>6</ConnectionsCurrent>
				<ConnectionsTotal>21</ConnectionsTotal>
				<ConnectionsTotalAccepted>21</ConnectionsTotalAccepted>
				<ConnectionsTotalRejected>0</ConnectionsTotalRejected>
				<MessagesInBytesRate>3.0</MessagesInBytesRate>
				<MessagesOutBytesRate>175126.0</MessagesOutBytesRate>
				<Stream>
					<Name>294%2F294%2F294_content_lq.mp4</Name>
					<SessionsFlash>1</SessionsFlash>
					<SessionsCupertino>0</SessionsCupertino>
					<SessionsSanJose>0</SessionsSanJose>
					<SessionsSmooth>0</SessionsSmooth>
					<SessionsRTSP>0</SessionsRTSP>
					<SessionsTotal>1</SessionsTotal>
				</Stream>
				<Stream>
					<Name>302%2F302%2F302_content_lq.mp4</Name>
					<SessionsFlash>1</SessionsFlash>
					<SessionsCupertino>0</SessionsCupertino>
					<SessionsSanJose>0</SessionsSanJose>
					<SessionsSmooth>0</SessionsSmooth>
					<SessionsRTSP>0</SessionsRTSP>
					<SessionsTotal>1</SessionsTotal>
				</Stream>
				<Stream>
					<Name>294%2F294%2F294_video_lq.mp4</Name>
					<SessionsFlash>1</SessionsFlash>
					<SessionsCupertino>0</SessionsCupertino>
					<SessionsSanJose>0</SessionsSanJose>
					<SessionsSmooth>0</SessionsSmooth>
					<SessionsRTSP>0</SessionsRTSP>
					<SessionsTotal>1</SessionsTotal>
				</Stream>
				<Stream>
					<Name>302%2F302%2F302_video_lq.mp4</Name>
					<SessionsFlash>1</SessionsFlash>
					<SessionsCupertino>0</SessionsCupertino>
					<SessionsSanJose>0</SessionsSanJose>
					<SessionsSmooth>0</SessionsSmooth>
					<SessionsRTSP>0</SessionsRTSP>
					<SessionsTotal>1</SessionsTotal>
				</Stream>
				<Stream>
					<Name>295%2F295%2F295_video_lq.mp4</Name>
					<SessionsFlash>1</SessionsFlash>
					<SessionsCupertino>0</SessionsCupertino>
					<SessionsSanJose>0</SessionsSanJose>
					<SessionsSmooth>0</SessionsSmooth>
					<SessionsRTSP>0</SessionsRTSP>
					<SessionsTotal>1</SessionsTotal>
				</Stream>
				<Stream>
					<Name>295%2F295%2F295_content_lq.mp4</Name>
					<SessionsFlash>1</SessionsFlash>
					<SessionsCupertino>0</SessionsCupertino>
					<SessionsSanJose>0</SessionsSanJose>
					<SessionsSmooth>0</SessionsSmooth>
					<SessionsRTSP>0</SessionsRTSP>
					<SessionsTotal>1</SessionsTotal>
				</Stream>
			</ApplicationInstance>
		</Application>
	</VHost>
</WowzaMediaServer>

*/

define('BASE_PATH',	realpath( __DIR__ . '/../../../../..' ) . '/' );
define('DEBUG', false );

include_once( BASE_PATH . 'libraries/Springboard/Application/Cli.php');

set_time_limit(0);

$islive = TRUE;

// Init
$app = new Springboard\Application\Cli(BASE_PATH, FALSE);

// Establish database connection
try {
	$db = $app->bootstrap->getAdoDB();
} catch (exception $err) {
	echo "ERROR: cannot connect to DB.\n" . $err . "\n";
	exit -1;
}

// Query streaming servers and passwords
$query = "
	SELECT
		ss.id,
		ss.server,
		ss.serverip,
		ss.shortname,
		ss.servicetype,
		ss.adminuser,
		ss.monitoringpassword as password,
		ss.disabled
	FROM
		cdn_streaming_servers AS ss
	WHERE
		ss.disabled = 0 AND
        adminuser IS NOT NULL AND
        monitoringpassword IS NOT NULL
";

try {
	$monitor_servers = $db->getArray($query);
} catch ( \Exception $e ) {
	echo "[ERROR]: Cannot query streaming servers.\n";
	exit -1;
}

// Munin: generate labels for munin plugin "config"
$munin_labels = "";
foreach( $monitor_servers as $server ) {
	$munin_labels .= $server['shortname'] . ".label " . $server['shortname'] . "\n";
}

// Wowza app: vsq or devvsq for on demand, vsqlive or devvsqlive for live analysis
$wowza_app = "vsq";
if ( stripos($app->config['baseuri'], "dev.videosquare.eu") !== FALSE ) $wowza_app = "dev" . $wowza_app;

// Wowza app: live or on demand config
$islive = FALSE;
if ( ( ( count($argv) > 1 ) && ( $argv[1] == 'live' ) ) or ( (count($argv) > 2) && ($argv[2] == 'live') ) ) {
	$islive = TRUE;
	$wowza_app .= "live";
}

// Munin config. See: http://munin-monitoring.org/wiki/protocol-config
if ( (count($argv) > 1) && ($argv[1] == 'config') ) {
	$graph_title = "Videosquare streaming server load (" . $wowza_app . ")";
// total.label All server load
	print("graph_title " . $graph_title . "
graph_category videosquare
graph_vlabel Clients
apptotal.label Total " . $wowza_app . "
" . $munin_labels);
    exit();
}

$total_currentconnections = 0;
$total_currentconnections_perapp = 0;

for ($i = 0; $i < count($monitor_servers); $i++ ) {

	$curl = curl_init();

	$wowza_url = "http://" . $monitor_servers[$i]['server'] . ":8086/connectioncounts";

	curl_setopt($curl, CURLOPT_URL, $wowza_url); 
	curl_setopt($curl, CURLOPT_PORT, 8086); 
	curl_setopt($curl, CURLOPT_VERBOSE, 0); 
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); 
	curl_setopt($curl, CURLOPT_USERPWD, $monitor_servers[$i]['adminuser'] . ":" . $monitor_servers[$i]['password']);
	curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_ANY);

	$data = curl_exec($curl); 
	if( curl_errno($curl) ){ 
//		echo "CURL ERROR: " . curl_error($curl) . " " . $monitor_servers[$i]['server'] . "\n";;
		curl_close($curl);
		$monitor_servers[$i]['currentconnections'] = "0";		// Munin: undefined value
		streamingServerUpdateDB($monitor_servers[$i]['id'], "unreachable", 0);
		continue;
	}

	// Check if authentication failed
	$header = curl_getinfo($curl);
	if ( $header['http_code'] == 401 ) {
//		echo "ERROR: HTTP 401. Cannot authenticate at " . $monitor_servers[$i]['server'] . "\n";
		curl_close($curl); 
		$monitor_servers[$i]['currentconnections'] = "0";		// Munin: undefined value
		streamingServerUpdateDB($monitor_servers[$i]['id'], "autherror", 0);
		continue;
	}

	// Process XML output

	// Open XML data
	$wowza_xml = simplexml_load_string($data);

	// Total number of clients connected to server
	$currentconnections = 0 + (string)$wowza_xml->ConnectionsCurrent;

	if ( is_numeric($currentconnections) ) {
		$monitor_servers[$i]['total_currentconnections'] = $currentconnections;
		// Update current load into database
		streamingServerUpdateDB($monitor_servers[$i]['id'], "ok", $currentconnections);
		$total_currentconnections += $currentconnections;
	}

	// Search for Wowza on demand (dev, non-dev) applications and record number of current connections
	foreach ($wowza_xml->VHost as $w_vhost) {
		$wowza_app_currentconnections = -1;
		foreach ($w_vhost->Application as $w_app) {
			// Wowza load for specific app
			if ( strcmp($w_app->Name, $wowza_app ) == 0 ) $wowza_app_currentconnections = 0 + (string)$w_app->ConnectionsCurrent;
		}
		$monitor_servers[$i][$wowza_app . '_currentconnections'] = $wowza_app_currentconnections;
		$total_currentconnections_perapp += $wowza_app_currentconnections;
	}

	curl_close($curl); 
}

// Server total load
/*if ( $total_currentconnections >= 0 ) {
	echo "total.value " . $total_currentconnections . "\n";
} else {
	echo "total.value 0\n";
} */

// Per app total load
if ( $total_currentconnections_perapp >= 0 ) {
	echo "apptotal.value " . $total_currentconnections_perapp . "\n";
} else {
	echo "apptotal.value 0\n";
}

//echo $wowza_app . "\n";
//var_dump($monitor_servers);
foreach( $monitor_servers as $server ) {
//var_dump($server);
	if ( isset($server[$wowza_app . '_currentconnections']) ) {
		if ( $server[$wowza_app . '_currentconnections'] >= 0 ) {
			echo $server['shortname'] . ".value " . $server[$wowza_app . '_currentconnections'] . "\n";
		} else {
			echo $server['shortname'] . ".value 0\n";
		}
	}
}

exit;

function streamingServerUpdateDB($id, $reachable, $currentload) {
 global $db;

	// Query streaming servers and passwords
	$query = "
		UPDATE
			cdn_streaming_servers
		SET
			serverstatus = \"". $reachable . "\",
			currentload = " . $currentload . "
		WHERE
			id = " . $id;

	try {
		$rs = $db->Execute($query);
	} catch ( \Exception $e ) {
		echo "[ERROR]: Cannot update streaming server record.\n" .  $err . "\n";
		return FALSE;
	}

	return TRUE;
}

?>
