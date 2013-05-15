<?php
namespace Model;

class Streamingservers extends \Springboard\Model {
  
  public function getServerByClientIP($ip, $types) {

	$default_streaming_servers_cachekey = 'defaultstreamingservers';
	$default_streaming_servers_cachetimeout = 5 * 60;

    // TODO organizationid?
    if ( empty( $types ) )
      throw new \Exception("No types specified for the streaming servers!");
      
/*
	// Get host name (for future geoIP provisioning)
    $hostname = $this->bootstrap->getSession('hostname');
    if ( $hostname['value'] === null ) {
// !!! Reverse DNS call timeout      
//      $hostname['value'] = gethostbyaddr($ip);
      if ( !$hostname['value'] or $hostname['value'] == $ip )
        $hostname['tld'] = $hostname['value'] = false;
      else {
        $pos = strrpos( $hostname['value'], '.' );
        $hostname['tld'] = substr( $hostname['value'], $pos );
      }
    }
*/

	$servicetype = "live";
	if ( $types != 1 ) $servicetype = "ondemand";

	// Handle default servers
	$cache = $this->bootstrap->getCache($default_streaming_servers_cachekey . $servicetype, $default_streaming_servers_cachetimeout, true);
	if ( $cache->expired() ) {

		// TODO: config.php!!!
		$default_server = "stream.videosquare.eu";

		// Get default servers
		$query = "
			SELECT 
				ss.id,
				ss.server,
				ss.serverip,
				ss.servicetype,
				ss.default
			FROM
				cdn_streaming_servers as ss
			WHERE
				ss.default = 1 AND
				ss.servicetype LIKE '%" . $servicetype . "%' AND
				ss.disabled = 0
		";

		try {
			$default_servers = $this->db->getArray($query);
		} catch (exception $err) {
			return $default_server;
		}

//var_dump($default_servers);

		// No default servers found
		if ( count($default_servers) < 1 ) {
			throw new \Exception("No default servers found");
			return $default_server;
		}

		$cache->put($default_servers);
	}

	$query = "
		SELECT 
			ss.id,
			ss.server,
			ss.serverip,
			ss.servicetype
		FROM
			cdn_streaming_servers as ss,
			cdn_client_networks as cn,
			cdn_servers_networks as sn
		WHERE
			INET_ATON(cn.ipaddressstart) <= INET_ATON('" . $ip . "') AND
			INET_ATON(cn.ipaddressend) >= INET_ATON('" . $ip . "') AND
			cn.id = sn.clientnetworkid AND
			sn.streamingserverid = ss.id AND
			ss.servicetype LIKE '%" . $servicetype . "%' AND
			cn.disabled = 0 AND
			ss.disabled = 0
		GROUP BY
			ss.server
		ORDER BY
			RAND() LIMIT 1
	";

	try {
		$server_selected = $this->db->getArray($query);
	} catch (exception $err) {
		return $default_server;
	}

	// No specific streaming server was found for source IP. Return default server
	if ( count($server_selected) != 1 ) {
		// Return default servers from cache. Choose random default server.
		$default_servers = $cache->get();
		$server_idx = array_rand($default_servers);
		return $default_servers[$server_idx]['server'];
	}

	// Server found for this IP
//var_dump($server_selected);
	return $server_selected[0]['server'];	
/*
    $where = array(
      "cdns.servicetype IN('" . implode("', '", $types ) . "')",
      "cdns.disabled = '0'",
    );
*/
  
/*
Kell ez ide?
    if ( $hostname['tld'] )
      $where[] = "cdns.country = " . $this->db->qstr( $hostname['tld'] );
    else
      $where[] = "cdns.default = '1'";
*/    
    
  }
  
}