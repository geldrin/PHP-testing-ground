<?php
namespace Videosquare\Job;

// Requirements: php-curl

class Pexip { 

    // Pexip related
    private $pexip_server              = '';
    private $pexip_port                = 443;
    private $pexip_user                = '';
    private $pexip_passwd              = '';
    private $pexip_location            = '';
    private $pexip_ishttpsenabled      = true;
    private $pexip_url                 = '';
    
    // Curl related
    private $curl_auth                 = CURLAUTH_BASIC;
    private $curl_lasthttpstatuscode   = null;
    private $curl_verbose              = 0;
    
    // Pexip session related
    private $streaming_participantid   = null;
    private $laststatus                = null;
    public  $lastapidatareturned       = null;
    
    // API commands
    private $api = array(
        'systeminfo'             => "/api/admin/status/v1/system_location/",
        'participant_status'     => "/api/admin/status/v1/participant/%s/",
        'participant_dial'       => "/api/admin/command/v1/participant/dial/",
        'participant_disconnect' => "/api/admin/command/v1/participant/disconnect/"
    );
    
    public function __construct($pexip_server, $pexip_port, $pexip_user, $pexip_passwd, $ishttpsenabled = false, $pexip_location) {
        
        if  ( !in_array('curl', get_loaded_extensions()) ) {
            throw new \Exception('[ERROR] PHP curl is not installed');
        }
        
        if ( empty($pexip_server) ) {
            throw new \Exception('[ERROR] No Pexip server host provided');
        } else {
            $this->pexip_server = $pexip_server;
        }

        if ( empty($pexip_user) or empty($pexip_passwd) ) {
            throw new \Exception('[ERROR] No user/password provided');
        } else {
            $this->pexip_user = $pexip_user;
            $this->pexip_passwd = $pexip_passwd;
        }

        if ( !empty($pexip_port) ) $this->pexip_port = $pexip_port;
        
        if ( !empty($ishttpsenabled) ) {
            if ( $ishttpsenabled == 1 ) {
                $this->pexip_ishttpsenabled = true;
            } else {
                $this->pexip_ishttpsenabled = false;
            }
        }
        
        if ( empty($pexip_location) ) {
            throw new \Exception('[ERROR] No location provided');    
        } else {
            $this->pexip_location = $pexip_location;
        }
        
        $this->pexip_url = ( $this->pexip_ishttpsenabled?"https":"http" ) . "://" . $this->pexip_server;
        
    }
    
    private function httpCURLWrapper($url, $ispost = false, $data) {
     
        $curl = curl_init();
     
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_PORT, $this->pexip_port); 
        curl_setopt($curl, CURLOPT_HTTPAUTH, $this->curl_auth);
        curl_setopt($curl, CURLOPT_USERPWD, $this->pexip_user . ":" . $this->pexip_passwd);
        curl_setopt($curl, CURLOPT_VERBOSE, $this->curl_verbose); 
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        if ( $ispost ) {

            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
        
            if ( !empty($data) ) {

                $data_string = json_encode($data);
            
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);                                                                                                                               
                curl_setopt($curl, CURLOPT_HTTPHEADER, array(                                                                          
                    'Content-Type: application/json',                                                                                
                    'Content-Length: ' . strlen($data_string))                                                                       
                );
                
            }
        }
            
        if ( $this->pexip_ishttpsenabled ) {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);   // false: only for testing!
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
            //curl_setopt($ch, CURLOPT_CAINFO, "/CAcerts/BuiltinObjectToken-EquifaxSecureCA.crt"); // For self signed: point to root cert
        }

        $result = curl_exec($curl);
        if ( curl_errno($curl) ) {
            $err = curl_error($curl);
            curl_close($curl);
            throw new \Exception('[ERROR] Server ' . $this->pexip_server . ' is unreachable.\n' . $err);
        }

        // Get HTTP header
        $header = curl_getinfo($curl);
        $this->curl_lasthttpstatuscode = $header['http_code'];
        
        // Authentication failed? (401)
        if ( $header['http_code'] == 401 ) {
            curl_close($curl);
            throw new \Exception('[ERROR] HTTP 401. Cannot authenticate to server ' . $this->pexip_server);
        }

        // URL not found? (404)
        $header = curl_getinfo($curl);
        if ( $header['http_code'] == 404 ) return false;
        
        curl_close($curl);
        
        $json = json_decode($result, true);
        if ( empty($json) ) {
            $retval = $result;
        } else {
            $retval = $json;
        }
        
        return $retval;
    }

    public function addParticipant($conference_alias, $destination, $presentation_url = null, $protocol, $role = 'chair', $remote_display_name = null) {
        
        $this->laststatus = null;
        $this->lastapidatareturned = null;
        
        if ( empty($conference_alias) ) throw new \Exception('[ERROR] Conference name is not provided');
        
        if ( empty($destination) ) throw new \Exception('[ERROR] Destination is not provided');
        
        if ( empty($protocol) ) throw new \Exception('[ERROR] Protocol is not provided');
        
        $data = array(
            'conference_alias'  => $conference_alias,
            'destination'       => $destination,
            'protocol'          => $protocol,
            'presentation_url'  => $presentation_url,
            'streaming'         => 'yes',
            'system_location'   => $this->pexip_location,
            'role'              => $role
        );
        
        if ( !empty($remote_display_name) ) $data['remote_display_name'] = $remote_display_name;
/*

'system_location': 'StreamNet LAN',

*/
        
        $url = $this->pexip_url . $this->api['participant_dial'];
        $result = $this->httpCURLWrapper($url, true, $data);
        
        if ( !is_array($result) ) throw new \Exception('[ERROR] Participant cannot be dialed. Pexip output is: ' . print_r($result, true));

        if ( isset($result['status']) ) $this->lastapistatus = $result['status'];
        if ( isset($result['data']['participant_id']) ) $this->streaming_participantid = $result['data']['participant_id'];
        
        return $result;
    }
    
    public function disconnectStreamingParticipant($streaming_participantid = null) {
        
        $this->laststatus = null;
        $this->lastapidatareturned = null;
    
        if ( empty($streaming_participantid) ) throw new \Exception('[ERROR] Participant ID is empty');
        
        $this->streaming_participantid = $streaming_participantid;
    
        $url = $this->pexip_url . $this->api['participant_disconnect'];
        
        $data = array(
            'participant_id' => $this->streaming_participantid
        );
        
        $result = $this->httpCURLWrapper($url, true, $data);
        
        $this->lastapidatareturned = $result;
        
        if ( !$result and ( $this->curl_lasthttpstatuscode == 404 ) ) return false;
        
        if ( isset($result['status']) ) $this->lastapistatus = $result['status'];
        if ( isset($result['disconnect']['participant_id']) ) {
            if ( strpos($result['disconnect']['participant_id'][0], "Failed") !== false ) return false;
        }
        
        return $result;
    }
    
    public function getStreamingParticipantStatus($streaming_participantid = null) {
        
        $this->lastapistatus = null;
        $this->lastapidatareturned = null;
        
        if ( empty($streaming_participantid) ) throw new \Exception('[ERROR] Participant ID is empty');
        
        $this->streaming_participantid = $streaming_participantid;
        
        $url = $this->pexip_url . sprintf($this->api['participant_status'], $this->streaming_participantid);
        
        $result = $this->httpCURLWrapper($url, false, null);
        
        $this->lastapidatareturned = $result;
        
        if ( !$result and ( $this->curl_lasthttpstatuscode == 404 ) ) return false;
        
        $this->lastapistatus = "success";
        
        return $result;
    }

}