<?php

/*
<WowzaMediaServer>
	<ConnectionsCurrent>1</ConnectionsCurrent>
	<ConnectionsTotal>4</ConnectionsTotal>
	<ConnectionsTotalAccepted>4</ConnectionsTotalAccepted>
	<ConnectionsTotalRejected>0</ConnectionsTotalRejected>
	<MessagesInBytesRate>2.0</MessagesInBytesRate>
	<MessagesOutBytesRate>1266.0</MessagesOutBytesRate>
	<VHost>
		<Name>_defaultVHost_</Name>
		<TimeRunning>7540.514</TimeRunning>
		<ConnectionsLimit>0</ConnectionsLimit>
		<ConnectionsCurrent>1</ConnectionsCurrent>
		<ConnectionsTotal>4</ConnectionsTotal>
		<ConnectionsTotalAccepted>4</ConnectionsTotalAccepted>
		<ConnectionsTotalRejected>0</ConnectionsTotalRejected>
		<MessagesInBytesRate>2.0</MessagesInBytesRate>
		<MessagesOutBytesRate>1273.0</MessagesOutBytesRate>
		<Application>
			<Name>vod</Name>
			<Status>loaded</Status>
			<TimeRunning>7538.637</TimeRunning>
			<ConnectionsCurrent>1</ConnectionsCurrent>
			<ConnectionsTotal>4</ConnectionsTotal>
			<ConnectionsTotalAccepted>4</ConnectionsTotalAccepted>
			<ConnectionsTotalRejected>0</ConnectionsTotalRejected>
			<MessagesInBytesRate>2.0</MessagesInBytesRate>
			<MessagesOutBytesRate>1273.0</MessagesOutBytesRate>
			<ApplicationInstance>
				<Name>_definst_</Name>
				<TimeRunning>7538.606</TimeRunning>
				<ConnectionsCurrent>1</ConnectionsCurrent>
				<ConnectionsTotal>4</ConnectionsTotal>
				<ConnectionsTotalAccepted>4</ConnectionsTotalAccepted>
				<ConnectionsTotalRejected>0</ConnectionsTotalRejected>
				<MessagesInBytesRate>2.0</MessagesInBytesRate>
				<MessagesOutBytesRate>1273.0</MessagesOutBytesRate>
				<Stream>
					<Name>sample.mp4</Name>
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

define('BASE_PATH',	realpath( __DIR__ . '/../../..' ) . '/' );
define('DEBUG', false );

include_once( BASE_PATH . 'libraries/Springboard/Application/Cli.php');
//include_once( BASE_PATH . 'modules/Jobs/job_utils_base.php' );

set_time_limit(0);

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
		ss.disabled = 0
";

try {
	$monitor_servers = $db->getArray($query);
} catch ( \Exception $e ) {
	echo "[ERROR]: Cannot query streaming servers.\n";
	exit -1;
}

var_dump($monitor_servers);

$munin_labels = "";
foreach( $monitor_servers as $server ) {
	$munin_labels .= $server['shortname'] . ".label " . $server['shortname'] . "\n";
}

echo $munin_labels;

if ((count($argv) > 1) && ($argv[1] == 'config')) {
	print("graph_title Streaming server load
graph_category network
graph_vlabel Clients
total.label Total
" . $munin_labels);
    exit();
}

$wowza_app = "devvsq";

$total_currentconnections = 0;

for ($i = 0; $i < count($monitor_servers); $i++ ) {

	$curl = curl_init();

	$wowza_url = "http://" . $monitor_servers[$i]['server'] . ":8086/connectioncounts";

//echo $wowza_url . "\n";

	//curl_setopt($curl, CURLOPT_URL, "http://stream.videosquare.eu:8086/streammanager/index.html"); 
	curl_setopt($curl, CURLOPT_URL, $wowza_url); 
	curl_setopt($curl, CURLOPT_PORT, 8086); 
	curl_setopt($curl, CURLOPT_VERBOSE, 0); 
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); 
	curl_setopt($curl, CURLOPT_USERPWD, $monitor_servers[$i]['adminuser'] . ":" . $monitor_servers[$i]['password']);
	curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
	//curl_setopt($curl, CURLOPT_POST, 1); 
	//curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

	$data = curl_exec($curl); 
	if( curl_errno($curl) ){ 
//		echo "CURL ERROR: " . curl_error($curl) . " " . $monitor_servers[$i]['server'] . "\n";;
		curl_close($curl);
		$monitor_servers[$i]['currentconnections'] = 0;
		streamingServerUpdateDB($monitor_servers[$i]['id'], "unreachable", 0);
		continue;
	}

	// Check if authentication failed
	$header = curl_getinfo($curl);
	if ( $header['http_code'] == 401 ) {
//		echo "ERROR: HTTP 401. Cannot authenticate at " . $monitor_servers[$i]['server'] . "\n";
		curl_close($curl); 
		$monitor_servers[$i]['currentconnections'] = 0;
		streamingServerUpdateDB($monitor_servers[$i]['id'], "autherror", 0);
		continue;
	}

	// Process XML output
//var_dump($data);

	$wowza_xml = simplexml_load_string($data);
//	print_r($wowza_xml);
	echo $wowza_xml->ConnectionsCurrent . "\n";
	$currentconnections = 0 + (string)$wowza_xml->ConnectionsCurrent;

	if ( is_numeric($currentconnections) ) {
		$monitor_servers[$i]['currentconnections'] = $currentconnections;
		streamingServerUpdateDB($monitor_servers[$i]['id'], "ok", $currentconnections);
		$total_currentconnections += $currentconnections;
	}

	// Update current load into database



	curl_close($curl); 

}

//var_dump($monitor_servers);

echo "total.value " . $total_currentconnections . "\n";

foreach( $monitor_servers as $server ) echo $server['shortname'] . ".value " . $server['currentconnections'] . "\n";

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
