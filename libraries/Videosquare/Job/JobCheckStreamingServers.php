<?php

namespace Videosquare\Job;

define('BASE_PATH',	realpath( __DIR__ . '/../../..' ) . '/' );
define('PRODUCTION', false );
define('DEBUG', false );

include_once('Job.php');
include_once('../Modules/SSH.php');

class CheckStreamingServersJob extends Job {
	
	// Job level config
	protected $needsLoop                = true;
	protected $signalReceived           = false;
	protected $needsSleep               = true;
	protected $closeDbOnSleep           = true;
	protected $sleepSeconds             = 30;
	protected $maxSleepSeconds          = 60;

	// Videosquare job specific config options
	protected $removeLockOnStart        = true;

	protected $debug_mode               = true;

    // Process job task
    protected function process() {
    
        $model = $this->bootstrap->getVSQModel("StreamingServers");

        // Get all streaming servers
        $StreamingServers = $model->getStreamingServers();
    
		$server_num = 0;
        if ( $StreamingServers !== false ) {
            
			// Loop through servers
            foreach ( $StreamingServers as $StreamingServer ) {

				$this->debugLog("[INFO] Server id#" . $StreamingServer['id'] . " (" . $StreamingServer['server'] . ") test started.", false);			
				if ( $this->debug_mode ) $this->debugLog("[DEBUG] Server information:\n" . print_r($StreamingServer, true), false);
	
				// Skip disabled server
				if ( $StreamingServer['disabled'] == 1 ) {
					$this->debugLog("[INFO] Server is administratively disabled. Skipping.", false);
					$server_num++;
					continue;
				}
	
				// Get IPs host name(s)
				$server_ips = gethostbynamel($StreamingServer['server']);
				if ( $server_ips === false ) {
					$this->debugLog("[ERROR] DNS test. Server name cannot be resolved.\n", false);
				} else {
		
					if ( count($server_ips) > 1 ) {
						$this->debugLog("[WARN] DNS test. Server hostname resolved to more than one IPs.\n" . print_r($server_ips, true), false);
					} else {
						$this->debugLog("[OK] DNS test. Hostname resolves to " . $server_ips[0], false);
					}
					
					if ( $StreamingServer['serverip'] <> $server_ips[0] ) {
						$this->debugLog("[ERROR] Server IP and database IP does not match. DNS says " . $server_ips[0] . " / DB says " . $StreamingServer['serverip'] . "\n" . print_r($server_ips, true), false);
					}

				}

				// Server ping test
				$ping = $this->pingAddress($StreamingServer['serverip']);
				if ( $ping['status'] === false ) {
					$this->debugLog("[ERROR] Ping test. Server does not answer. Skipping the rest.", false);
					$server_num++;
					continue;
				} else {
					
					$this->debugLog("[OK] Ping test. RTT avg is " . $ping['rtt_avg'] . "ms. Packet loss is " . $ping['packet_loss'] . "/" . $ping['packets_sent'] . ".", false);
				
					if ( $StreamingServer['type'] == "nginx" ) {
				
						// ## SSH: get cache free space
						try {
							
							$ssh = new SSH($StreamingServer['server'], 22, "support", null, "/home/conv/.ssh/id_dsa.pub-support", "/home/conv/.ssh/id_dsa-support", null);
							
							// Authenticate to SSH server
							$ssh->connect();
							
							// Debug
							//if ( $this->debug_mode ) $this->debugLog("[DEBUG] Connected to SSH server. Information: " . print_r($ssh, true), false);
							
							// Get cache free size
							$cache_path = "/srv/nginx_cache";
							$return = $ssh->exec("df " . $cache_path);
							$tmp = preg_split("/\r\n|\n|\r/", trim($return));
							$tmp2 = preg_split('/[\s]+/', trim($tmp[1]));
							$cache_info['path'] = $tmp2[0];
							$cache_info['used'] = $tmp2[2] * 1024;
							$cache_info['available'] = $tmp2[3] * 1024;
							$cache_info['used_percent'] = $tmp2[4];
							$cache_info['mount_point'] = $tmp2[5];
							
							$this->debugLog("[INFO] Cache information:\n" . print_r($cache_info, true)); 
							
							if ( $this->debug_mode ) $this->debugLog("[INFO] Cache sizes (used/available): " . round($cache_info['used'] / 1024 / 1024 / 1024, 3) . " / " . round($cache_info['available'] / 1024 / 1024 / 1024, 3) . " GB.");
							
							if ( $cache_info['available'] <= 10000000 ) $this->debugLog("[WARN] Cache size seems small: " . $cache_info['available'] / 1024 / 1024 / 1024 . "GB.");
							
							if ( $cache_info['used_percent'] > 95 ) $this->debugLog("[WARN] Cache usage is " . $cache_info['used_percent'] . "%.");
							
							// Get cache mount information
							$command = "cat /proc/mounts | grep nginx";
							$return = $ssh->exec($command);
							$tmp = preg_split('/[\s]+/', trim($return));
							if ( stripos($tmp[3], "rw") === false ) {
								$this->debugLog("[ERROR] Cache is NOT writeable. Mount parameters: " . $tmp[3]);
							}

							// SSH disconnect
							$ssh->disconnect();
															
						} catch ( \Videosquare\Model\Exception $err) {
							
							$this->debugLog( '[EXCEPTION] SSH: ' . $err->getMessage(), false );
							
						} // End of SSH try

					} // End of type == "nginx" block
						
				} // End of ping block

				
			}
			
			$server_num++;
		}
		
	}
	
	private function pingAddress($ip) {
    
		$ping_num = 5;
		
		$command = "/bin/ping -A -q -c " . $ping_num . " " . $ip . " 2>&1 | grep 'transmitted\|rtt'";
		exec($command, $output, $result);
		
		if ( $result != 0 ) {
			$ping_result = array( 'status' => false );
			return $ping_result;
		}

		$ping = array(
			'status'        => true,
			'rtt_avg'		=> 0
		);
		
		// Match numbers in line: "5 packets transmitted, 5 received, 0% packet loss, time 401ms"
		preg_match_all('/([\d]+)/', $output[0], $tmp);
		
		$ping['packets_sent'] = trim($tmp[0][0]);
		$ping['packets_received'] = trim($tmp[0][1]);
		$ping['packet_loss'] = trim($tmp[0][2]);
		
		if ( count($output) >= 2 ) {
		
			// Match numbers in line: "rtt min/avg/max/mdev = 0.151/0.191/0.242/0.037 ms, ipg/ewma 200.689/0.184 ms"
			preg_match_all('/([\d]+.[\d]+)/', $output[1], $tmp);
			$ping['rtt_avg'] = trim($tmp[0][1]);
		}
		
		// If packet loss is detected, status is false
		if ( $ping['packets_received'] != $ping_num ) $ping['status'] = false;
	   
		return $ping;
	}

}

// Job main

set_time_limit(0);
clearstatcache();

date_default_timezone_set('Europe/Budapest');

$job = new CheckStreamingServersJob(BASE_PATH, PRODUCTION);

try {
    $job->run();
} catch( Exception $err ) {
    $job->debugLog( '[EXCEPTION] run(): ' . $err->getMessage(), false );
    throw $err;
}

exit;

?>
