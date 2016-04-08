<?php
namespace Videosquare\Model;

class VCR extends \Videosquare\Model\Live {

    private $recordinglinkid = null;

    // Logging: EZ ELÉG RANDA!!!
    
    public function debugLog($msg, $sendmail = false) {

        if ( empty($this->d) ) $this->initLog();
    
        $this->d->log($this->logDir, $this->logFile, $msg, $sendmail);
        
    }
    
    public function initLog($debug_mode) {

        // Debug object
        $this->d = \Springboard\Debug::getInstance();
        
        // Log directory and file
        $this->logDir = BASE_PATH . 'data/logs/jobs';
   
        $filename = basename($_SERVER["PHP_SELF"]);
        $this->logFile = substr( $filename, 0, strrpos( $filename, '.' ) ) . ".txt";
        
        if ( !empty($debug_mode) ) $this->debug_mode = $debug_mode;
            
    }
    
    // Select recording link
    public function selectRecordingLink($recordinglinkid) {

        if ( empty($recordinglinkid) ) throw new \Exception('[ERROR] Recording Link ID is empty.');
        
        $this->recordinglinkid = $recordinglinkid;
        
    }
    
    // Update recording link status
    public function updateRecordingLinkStatus($status) {

        if ( empty($status) ) throw new \Exception('[ERROR] Recording Link status.');

        $values = array(
            'status' => $status
        );

        $model = $app->bootstrap->getVSQModel('recording_links');
        $model->select($this->recordinglinkid);
        $model->updateRow($values);
        
        // Log status change
        $this->debugLog("[INFO] Recording link id#" . $this->recordinglinkid . " status changed to '" . $status . "'.", $sendmail = false);

    }

/*
    // update_db_stream_params
    function updateVCRLiveStreamParams($id, $streamid = null, $conferenceid = null) {
    global $app, $debug, $jconf, $myjobid;

        $values = array();

        if ( !empty($streamid) ) $values['keycode'] = $streamid;
        if ( !empty($conferenceid) ) $values['vcrconferenceid'] = $conferenceid;

        if ( empty($values) ) return false;

        $converterNodeObj = $app->bootstrap->getModel('livefeed_streams');
        $converterNodeObj->select($id);
        $converterNodeObj->updateRow($values);
        
        // Log status change
        $debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] VCR live stream id#" . $id . " params updated:\n" . print_r($values, true), $sendmail = false);

        return true;
    }

// update_db_vcr_reclink_params
function updateVCRReclinkParams($id, $conf_id) {
global $app, $debug, $jconf, $myjobid;

	if ( empty($conf_id) ) return false;

    $values = array(
        'conferenceid'  => $conf_id
	);

    $converterNodeObj = $app->bootstrap->getModel('recording_links');
    $converterNodeObj->select($id);
    $converterNodeObj->updateRow($values);
    
	// Log status change
	$debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] VCR recording link id#" . $id . " params updated with conferenceid = " . $conf_id . ".", $sendmail = false);

	return true;
}

*/

    // Get live recordings requires to be handled
    public function getPendingLiveRecordings($type, $livefeedstatus, $recordinglinkstatus) {

        $query = "
            SELECT
                lfs.id,
                lfs.livefeedid,
                lfs.qualitytag,
                lfs.status,
                lfs.recordinglinkid,
                lfs.qualitytag,
                lfs.keycode,
                lfs.contentkeycode,
                rl.id AS recordinglinkid,
                rl.name AS recordinglinkname,
                rl.organizationid,
                rl.calltype,
                rl.number,
                rl.password,
                rl.bitrate,
                rl.alias,
                rl.aliassecure,
                rl.status AS recordinglinkstatus,
                rl.conferenceid,
                rl.apiserver,
                rl.apiport,
                rl.apiuser,
                rl.apipassword,
                rl.apiishttpsenabled,
                rl.pexiplocation,
                rl.livestreamgroupid,
                lf.id AS feed_id,
                lf.userid,
                lf.channelid,
                lf.name AS livefeedname,
                lf.issecurestreamingforced,
                lf.needrecording,
                lsg.id AS livestreamgroupid,
                lsg.name AS livestreamgroupname,
                lsg.istranscoderencoded,
                lsg.transcoderid,
                lst.id AS livestreamtranscoderid,
                lst.name AS livestreamtranscodername,
                lst.type AS livestreamtranscodertype,
                lst.server AS livestreamtranscoderserver,
                lst.ingressurl AS livestreamtranscoderingressurl
            FROM
                livefeed_streams AS lfs,
                livefeeds AS lf,
                recording_links AS rl
            LEFT JOIN livestream_groups AS lsg
                ON lsg.id = rl.livestreamgroupid
            LEFT JOIN livestream_transcoders AS lst
                ON lst.id = lsg.transcoderid
            WHERE
                rl.type = '" . $type . "' AND
                lfs.status = '" . $livefeedstatus . "' AND
                rl.status = '" . $recordinglinkstatus . "' AND
                lfs.recordinglinkid = rl.id AND
                lfs.livefeedid = lf.id AND
                rl.disabled = 0
            ORDER BY
                lfs.id";

        $model = $this->bootstrap->getVSQModel('livefeed_streams');
        $rs = $model->safeExecute($query);
        
        // $rs->getArray() does not work!
        $rs_array = array();
        while ( !$rs->EOF ) {
            array_push($rs_array, $rs->fields);
            $rs->moveNext();
        }
                
        if ( count($rs_array) < 1 ) return false;
    
        return $rs_array;
    }

}

