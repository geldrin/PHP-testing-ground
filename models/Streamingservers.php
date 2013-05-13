<?php
namespace Model;

class Streamingservers extends \Springboard\Model {
  
  public function getServerByClientIP( $ip, $types ) {

	$default_streaming_servers_cachekey = 'defaultstreamingservers';

    // TODO organizationid?
    if ( empty( $types ) )
      throw new \Exception("No types specified for the streaming servers!");
      
//    $hostname = $this->bootstrap->getSession('hostname');
$hostname = "mail.streamnet.hu";

    if ( $hostname['value'] === null ) {
      
      $hostname['value'] = gethostbyaddr( $ip );
      if ( !$hostname['value'] or $hostname['value'] == $ip )
        $hostname['tld'] = $hostname['value'] = false;
      else {
        
        $pos = strrpos( $hostname['value'], '.' );
        $hostname['tld'] = substr( $hostname['value'], $pos );
        
      }
      
    }

	$servicetype = "live";
	if ( $types != 1 ) $servicetype = "ondemand";

	// Handle default servers
	$cache = $this->bootstrap->getCache($default_streaming_servers_cachekey . $servicetype, 15*60, true);
	if ( $cache->expired() ) {
		// Get default servers
echo "getdefs\n";

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
			$rs2 = $this->db->Execute($query);
		} catch (exception $err) {
			echo "[ERROR] SQL query failed.\n" . trim($query) . "\n" . $err . "\n";
			return FALSE;
		}

		// No default servers found
		if ( $rs2->RecordCount() == 0 ) 
			throw new \Exception("No default servers found");

		$default_servers = array();
		while ( !$rs2->EOF ) {
			$server = $rs2->fields;
			array_push($default_servers, $server['server']);
			$rs2->MoveNext();
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
		$rs = $this->db->Execute($query);
	} catch (exception $err) {
		echo "[ERROR] SQL query failed.\n", trim($query), $err . "\n";
		return FALSE;
	}

	// No specific streaming server was found for source IP. Return default server
	if ( $rs->RecordCount() != 1 ) {
		// TODO: Return default servers from cache. Update cache one every X mins.
		$default_servers = $cache->get();
		return array_rand($default_servers);
	}

	// Server found for this IP
	$server = $rs->fields;
	return $server['server'];	
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