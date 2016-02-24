<?php
namespace Videosquare\Model;

class Live extends \Springboard\Model {

    private $livefeedid = null;
    private $livefeedstreamid = null;
    
    // ## Select object for further operations
    
    // Select live feed
    public function selectLiveFeed($livefeedid) {
    
        if ( empty($livefeedid) ) return false;
        
        $this->livefeedid = $livefeedid;
        
        return true;
    }

    // Select live feed stream
    public function selectLiveFeedStream($livefeedstreamid) {
    
        if ( empty($livefeedstreamid) ) return false;
        
        $this->livefeedstreamid = $livefeedstreamid;
        
        return true;
    }
    
    // ## Status update functions
    
    // Update livefeed SMIL status
    public function updateLiveFeedSMILStatus($status, $type = "video") {

        if ( ( $type != "video" ) and ( $type != "content" ) ) return false;

        if ( empty($status) ) return false;

        $idx = "";
        if ( $type == "content" ) $idx = "content";

        $values = array(
            $idx . 'smilstatus' => $status
        );

        $recordingVersionObj = $this->bootstrap->getModel('livefeeds');
        $recordingVersionObj->select($this->livefeedid);
        $recordingVersionObj->updateRow($values);

        // Log status change
        $this->debugLog("[INFO] Livefeed id = " . $this->livefeedid . " " . $type . " status has been changed to '" . $status . "'.", false);

        return true;
    }

    // Update live stream status
    public function updateLiveStreamStatus($status) {

        if ( empty($status) ) return false;

        $values = array(
            'status' => $status
        );

        $converterNodeObj = $this->bootstrap->getModel('livefeed_streams');
        $converterNodeObj->select($this->livefeedstreamid);
        $converterNodeObj->updateRow($values);
        
        // Log status change
        $this->debugLog("[INFO] Live stream id#" . $this->livefeedstreamid . " status changed to '" . $status . "'.", false);
        
        return true;
    }

    ## Live thumbnail related
    
    // Update livefeed index photo
    public function updateLiveFeedStreamIndexPhoto($indexphotofilename) {

        if ( empty($indexphotofilename) ) return false;

        $values = array(
            'indexphotofilename'	=> $indexphotofilename
        );

        $recordingVersionObj = $this->bootstrap->getModel('livefeed_streams');
        $recordingVersionObj->select($this->livefeedstreamid);
        $recordingVersionObj->updateRow($values);
        $recordingVersionObj->updateFeedThumbnail();

        return true;
    }
    
    // ## Other

    // Get a list of live feeds with number of current viewers active in the last minute
    public function getLiveViewerCountersForAllFeeds() {

        $now = date("Y-m-d H:i:s");

        $query = "
            SELECT
                vsl.livefeedid,
                COUNT(vsl.id) AS currentviewers
            FROM
                view_statistics_live AS vsl
            WHERE
                vsl.timestampuntil >= DATE_SUB(NOW(), INTERVAL 1 MINUTE)
            GROUP BY
                vsl.livefeedid";

        try {
            $model = $this->bootstrap->getModel('view_statistics_live');
            $rs = $model->safeExecute($query);
        } catch (exception $err) {
            $this->debugLog("[ERROR] SQL query failed.\n" . trim($query), false);
            return false;
        }

        // Check if any record returned
        if ( $rs->RecordCount() < 1 ) return false;

        // Convert AdoDB resource to array
        $rs_array = adoDBResourceSetToArray($rs);

        return $rs_array;
    }

    // Update currentviewers counter for specified livefeed
    public function updateLiveFeedViewCounter($currentviewers) {
        
        // Update livefeed currentviewers counter
        $query = "
            UPDATE
                livefeeds
            SET
                currentviewers = " . $currentviewers. "
            WHERE
                id = " . $this->livefeedid;

        try {
            $model = $this->bootstrap->getModel('livefeeds');
            $rs = $model->safeExecute($query);
        } catch (exception $err) {
            $this->debugLog("[ERROR] Cannot update currentviewers for livefeedid#" . $this->livefeedid . "\n\n" . $err, false);
            return false;
        }
        
        return $model->db->Affected_Rows();
    }
    
    // Sets livefeeds.currentviewers to 0 for selected feed IDs
    public function setLiveFeedViewCountersToZero($livefeedids) {

        $in = "";
        
        if ( !empty($livefeedids) ) $in = " AND lf.id NOT IN (" . implode(", ", $livefeedids) . ")";

        $query = "
            UPDATE
                livefeeds AS lf
            SET
                lf.currentviewers = 0
            WHERE
                lf.currentviewers > 0 " . $in;
        
        try {
            $model = $this->bootstrap->getModel('livefeeds');
            $rs = $model->safeExecute($query);
        } catch (exception $err) {
            $this->debugLog("[ERROR] SQL query failed.\n" . trim($query), false);
            return false;
        }

        return $model->db->Affected_Rows();
    }
    
}
