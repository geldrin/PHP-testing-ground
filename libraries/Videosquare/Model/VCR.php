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
    public function getPendingLiveRecordings($livefeedstatus, $recordinglinkstatus) {

        $query = "
            SELECT
                lfs.id,
                lfs.livefeedid,
                lfs.qualitytag,
                lfs.status,
                lfs.recordinglinkid,
                rl.id AS reclink_id,
                rl.name AS reclink_name,
                rl.organizationid,
                rl.calltype,
                rl.number,
                rl.password,
                rl.bitrate,
                rl.alias,
                rl.aliassecure,
                rl.status AS reclink_status,
                rl.conferenceid AS conf_id,
                lf.id AS feed_id,
                lf.userid,
                lf.channelid,
                lf.name AS feed_name,
                lf.issecurestreamingforced,
                lf.needrecording
            FROM
                livefeed_streams AS lfs,
                recording_links AS rl,
                livefeeds AS lf
            WHERE
                lfs.status = '" . $livefeedstatus . "' AND
                rl.status = '" . $recordinglinkstatus . "' AND
                lfs.recordinglinkid = rl.id AND
                rl.disabled = 0 AND
                lfs.livefeedid = lf.id
            ORDER BY
                id
            LIMIT 1";
// LIMIT 1???? Mi lesz ha tobb stream tartozik egy felvetelhez? TODO

        $model = $this->bootstrap->getVSQModel('livefeed_streams');
        $rs = $model->safeExecute($query);
        
        // Check if pending job exsits
        if ( count($rs->getArray()) < 1 ) return false;
    
        return $rs->fields;
    }

}
