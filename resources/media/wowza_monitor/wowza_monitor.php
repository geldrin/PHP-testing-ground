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

if ((count($argv) > 1) && ($argv[1] == 'config')) {
	print("graph_title Streaming server load
graph_category network
graph_vlabel Clients
total.label Total
stream-bp-1.label BP-1
stream-bp-2.label BP-2
stream-deb-3.label Debrecen-3
stream-deb-4.label Debrecen-4
");
    exit();
}

$wowza_list =  array(
	0	=> array(
					'server'				=> "stream-bp-1videosquare.hu.t-internal.com",
					'shortname'				=> "stream-bp-1",
					'user'					=> "admin",
					'password'				=> "MisiMokus",
					'currentconnections'	=> 0
				),
	1	=> array(
					'server'				=> "stream-bp-2videosquare.hu.t-internal.com",
					'shortname'				=> "stream-bp-2",
					'user'					=> "admin",
					'password'				=> "MisiMokus",
					'currentconnections'	=> 0
				),
	2	=> array(
					'server'				=> "stream-deb-3videosquare.hu.t-internal.com",
					'shortname'				=> "stream-deb-3",
					'user'					=> "admin",
					'password'				=> "MisiMokus",
					'currentconnections'	=> 0
				),
	3	=> array(
					'server'				=> "stream-deb-4videosquare.hu.t-internal.com",
					'shortname'				=> "stream-deb-4",
					'user'					=> "admin",
					'password'				=> "MisiMokus",
					'currentconnections'	=> 0
				)
);

$total_currentconnections = 0;

for ( $i = 0; $i < count($wowza_list); $i++ ) {

	$curl = curl_init();

	$wowza_url = "http://" . $wowza_list[$i]['server'] . ":8086/connectioncounts";

//echo $wowza_url . "\n";

	//curl_setopt($curl, CURLOPT_URL, "http://stream.videosquare.eu:8086/streammanager/index.html"); 
	curl_setopt($curl, CURLOPT_URL, $wowza_url); 
	curl_setopt($curl, CURLOPT_PORT, 8086); 
	curl_setopt($curl, CURLOPT_VERBOSE, 0); 
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); 
	curl_setopt($curl, CURLOPT_USERPWD, $wowza_list[$i]['user'] . ":" . $wowza_list[$i]['password']);
	curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
	//curl_setopt($curl, CURLOPT_POST, 1); 
	//curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

	$data = curl_exec($curl); 
	if( curl_errno($curl) ){ 
//		echo "CURL ERROR: " . curl_error($curl) . " " . $wowza_list[$i]['server'] . "\n";;
		$wowza_list[$i]['currentconnections'] = -1;
		curl_close($curl);
		continue;
	}

	// Check if authentication failed
	$header = curl_getinfo($curl);
	if ( $header['http_code'] == 401 ) {
//		echo "ERROR: HTTP 401. Cannot authenticate at " . $wowza_list[$i]['server'] . "\n";
		$wowza_list[$i]['currentconnections'] = -1;
		curl_close($curl); 
		continue;
	}

	// Process XML output
//var_dump($data);

	$wowza_xml = simplexml_load_string($data);
//	print_r($wowza_xml);
//	echo $wowza_xml->ConnectionsCurrent . "\n";
	$currentconnections = 0 + (string)$wowza_xml->ConnectionsCurrent;

	if ( is_numeric($currentconnections) ) {
		$wowza_list[$i]['currentconnections'] = $currentconnections;
		$total_currentconnections += $currentconnections;
	}

	curl_close($curl); 

}

//var_dump($wowza_list);

echo "total.value " . $total_currentconnections . "\n";

for ( $i = 0; $i < count($wowza_list); $i++ ) echo $wowza_list[$i]['shortname'] . ".value " . $wowza_list[$i]['currentconnections'] . "\n";

exit;

?>
