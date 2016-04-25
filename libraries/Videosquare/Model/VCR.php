<?php
namespace Videosquare\Model;

class VCR extends \Videosquare\Model\Live {

    private $livefeedid = null;
    private $recordinglinkid = null;
    private $vcrparticipantid = null;
    private $livefeedrecordingid = null;

    // Logging: EZ ELÉG RANDA!!!
    
    public function debugLog($msg, $sendmail = false) {

        if ( empty($this->d) ) $this->initLog();
    
        $this->d->log($this->logDir, $this->logFile, $msg, $sendmail);
        
    }
    
    public function initLog($debug_mode = false) {

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

        $model = $this->bootstrap->getVSQModel('recording_links');
        $model->select($this->recordinglinkid);
        $model->updateRow($values);
        
        // Log status change
        $this->debugLog("[INFO] Recording link id#" . $this->recordinglinkid . " status changed to '" . $status . "'.", $sendmail = false);

    }
    
    // Select livefeed
    public function selectLiveFeed($livefeedid) {

        if ( empty($livefeedid) ) throw new \Exception('[ERROR] Livefeed ID is empty.');
        
        $this->livefeedid = $livefeedid;
        
    }
    
    // Set Pexip participant ID
    public function setParticipantID($participantid) {

        if ( empty($participantid) ) throw new \Exception('[ERROR] Participant ID is empty.');
        
        $this->vcrparticipantid = $participantid;
        
    }
    
    public function updateLiveFeedStatus($status) {

        if ( empty($status) ) throw new \Exception('[ERROR] Recording Link status.');

        $values = array(
            'status' => $status
        );

        $model = $this->bootstrap->getVSQModel('livefeeds');
        $model->select($this->livefeedid);
        $model->updateRow($values);
        
        $this->debugLog("[INFO] Livefeed id#" . $this->livefeedid . " status changed to '" . $status . "'.", $sendmail = false);

    }

    function updateLiveFeedParams($vcrparticipantid) {

        if ( empty($vcrparticipantid) ) return false;
    
        $values = array();
        $values['vcrparticipantid'] = $vcrparticipantid;

        $converterNodeObj = $this->bootstrap->getModel('livefeeds');
        $converterNodeObj->select($this->livefeedid);
        $converterNodeObj->updateRow($values);

        $this->debugLog("[INFO] Livefeed id#" . $this->livefeedid . " Pexip participant ID changed to: " . $vcrparticipantid, $sendmail = false);

    }

    function updateRecordingLinkParams($vcrparticipantid) {

        if ( empty($vcrparticipantid) ) return false;
    
        $values = array();
        $values['conferenceid'] = $vcrparticipantid;

        $converterNodeObj = $this->bootstrap->getModel('recording_links');
        $converterNodeObj->select($this->recordinglinkid);
        $converterNodeObj->updateRow($values);

        $this->debugLog("[INFO] Recording link id#" . $this->recordinglinkid . " Pexip participant ID changed to: " . $vcrparticipantid, $sendmail = false);

    }

    // Get live recordings requires to be handled
    public function getPendingLiveFeeds($type, $livefeedstatus, $recordinglinkstatus) {

        $query = "
            SELECT
                lf.id,
                lf.userid,
                lf.channelid,
                lf.name,
                lf.issecurestreamingforced,
                lf.needrecording,
                lf.recordinglinkid,
                lf.status,
                lf.livefeedrecordingid,
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
                livefeeds AS lf,
                recording_links AS rl
            LEFT JOIN livestream_groups AS lsg
                ON lsg.id = rl.livestreamgroupid
            LEFT JOIN livestream_transcoders AS lst
                ON lst.id = lsg.transcoderid
            WHERE
                rl.type = '" . $type . "' AND
                lf.status = '" . $livefeedstatus . "' AND
                rl.status = '" . $recordinglinkstatus . "' AND
                lf.recordinglinkid = rl.id AND
                rl.disabled = 0
            ORDER BY
                lf.id";

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

    public function getStreamsForLivefeed() {

        $query = "
            SELECT
                lfs.id,
                lfs.livefeedid,
                lfs.qualitytag,
                lfs.keycode,
                lfs.contentkeycode,
                lfs.isdesktopcompatible,
                lfs.isioscompatible,
                lfs.isandroidcompatible
            FROM
                livefeed_streams AS lfs
            WHERE
                lfs.livefeedid = " . $this->livefeedid;

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

    public function getLiveFeed() {

        $query = "
            SELECT
                lf.id,
                lf.organizationid,
                lf.userid,
                lf.channelid,
                lf.name,
                lf.slideonright,
                lf.hascontent,
                lf.feedtype,
                lf.needrecording,
                lf.recordinglinkid,
                lf.vcrconferenceid,
                lf.vcrparticipantid,
                lf.livefeedrecordingid,
                lf.status
            FROM
                livefeeds AS lf
            WHERE
                lf.id = " . $this->livefeedid;

        $model = $this->bootstrap->getVSQModel('livefeeds');
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

    // Get finished livefeed recordings (ready to be uploaded)
    public function getFinishedRecordedFeeds($server) {

        $query = "
            SELECT
                lfr.id,
                lfr.livefeedid,
                lfr.userid,
                lfr.livestreamtranscoderid,
                lfr.starttimestamp,
                lfr.endtimestamp,
                lfr.vcrconferenceid,
                lfr.status,
                lst.server,
                u.email,
                o.id AS organizationid,
                o.domain
            FROM
                livefeed_recordings AS lfr,
                livestream_transcoders AS lst,
                users AS u,
                organizations AS o
            WHERE
                lst.server = '" . $server . "' AND
                lst.id = lfr.livestreamtranscoderid AND
                lfr.status = 'finished' AND
                lfr.userid = u.id AND
                u.organizationid = o.id";
        
        $model = $this->bootstrap->getVSQModel('livefeed_recordings');
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

    // Get livefeed recording by ID
    public function getLiveFeedRecording($id = null, $livefeedid = null, $status = null, $server = null) {

        $filter = array();
        
        if ( !empty($id) ) array_push($filter, "lfr.id = " . $id);
        
        if ( !empty($livefeedid) ) array_push($filter, "lfr.livefeedid = " . $livefeedid);
        
        if ( !empty($status) ) array_push($filter, "lfr.status = '" . $status . "'");
        
        if ( !empty($server) ) array_push($filter, "lst.server = '" . $server . "'");
        
        $sql_filter = "";
        if ( count($filter) > 0 ) $sql_filter = " AND " . implode(" AND ", $filter);
    
        $query = "
            SELECT
                lfr.id,
                lfr.livefeedid,
                lfr.userid,
                lfr.livestreamtranscoderid,
                lfr.starttimestamp,
                lfr.endtimestamp,
                lfr.vcrconferenceid,
                lfr.status,
                lst.server,
                u.email,
                o.id AS organizationid,
                o.domain
            FROM
                livefeed_recordings AS lfr,
                livestream_transcoders AS lst,
                users AS u,
                organizations AS o
            WHERE
                lfr.livestreamtranscoderid = lst.id AND
                lfr.userid = u.id AND
                u.organizationid = o.id" . $sql_filter . "
            ORDER BY
                lfr.starttimestamp DESC";

        echo $query . "\n";
                
        $model = $this->bootstrap->getVSQModel('livefeed_recordings');
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

    
    // Select livefeed recording
    public function selectLiveFeedRecording($livefeedrecordingid) {

        if ( empty($livefeedrecordingid) ) throw new \Exception('[ERROR] Livefeed recording ID is empty.');
        
        $this->livefeedrecordingid = $livefeedrecordingid;
        
    }

    // Update livefeed_recording status
    public function updateLiveFeedRecording($status = null, $starttimestamp = null, $endtimestamp = null) {

        if ( empty($status) and empty($starttimestamp) and empty($endtimestamp) ) throw new \Exception('[ERROR] Livefeed recording status and start and end time empty');

        if ( !empty($status) ) $values['status'] = $status;
        if ( !empty($starttimestamp) ) $values['starttimestamp'] = $starttimestamp;
        if ( !empty($endtimestamp) ) $values['endtimestamp'] = $endtimestamp;

        $model = $this->bootstrap->getVSQModel('livefeed_recordings');
        $model->select($this->livefeedrecordingid);
        $model->updateRow($values);
        
        // Log status change
        $this->debugLog("[INFO] Livefeed recording id#" . $this->livefeedrecordingid . " updated with:\n" . print_r($values, true), $sendmail = false);

    }
    
    
}

