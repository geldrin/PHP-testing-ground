<?php
namespace Videosquare\Model;

class Live extends \Springboard\Model {

    private $livefeedid = null;
    private $livefeedstreamid = null;
    
    // ## Select object for further operations
    
    // Select livefeed id
    public function selectLiveFeed($livefeedid) {
    
        if ( empty($livefeedid) ) throw new \Videosquare\Model\Exception('Empty livefeed ID.');
        
        $this->livefeedid = $livefeedid;
        
    }

    // Select livefeedstream id
    public function selectLiveFeedStream($livefeedstreamid) {
    
        if ( empty($livefeedstreamid) ) throw new \Videosquare\Model\Exception('Empty livefeedstream ID.');
        
        $this->livefeedstreamid = $livefeedstreamid;

    }

    // Get livefeed id
    public function getLiveFeed() {

        return $this->livefeedid;
        
    }

    // Get livefeedstream id
    public function getLiveFeedStream() {
        
        return $this->livefeedstreamid;

    }

    
    // ## Status update functions
    
    // Update livefeed SMIL status
    public function updateLiveFeedSMILStatus($status, $type = "video") {

        if ( ( $type != "video" ) and ( $type != "content" ) ) throw new \Videosquare\Model\Exception('Invalid livefeed type to set SMIL staus.');

        if ( empty($status) ) throw new \Videosquare\Model\Exception('Empty livefeed SMIL status.');

        $idx = "";
        if ( $type == "content" ) $idx = "content";

        $values = array(
            $idx . 'smilstatus' => $status
        );

        $recordingVersionObj = $this->bootstrap->getVSQModel('livefeeds');
        $recordingVersionObj->select($this->livefeedid);
        $recordingVersionObj->updateRow($values);

        // Log status change
        //$this->debugLog("[INFO] Livefeed id = " . $this->livefeedid . " " . $type . " status has been changed to '" . $status . "'.", false);

    }

    // Update live stream status
    public function updateLiveStreamStatus($status) {

        if ( empty($status) ) throw new \Videosquare\Model\Exception('Empty status to set livefeedstream status.');

        $values = array(
            'status' => $status
        );

        $converterNodeObj = $this->bootstrap->getVSQModel('livefeed_streams');
        $converterNodeObj->select($this->livefeedstreamid);
        $converterNodeObj->updateRow($values);
        
        // Log status change
        //$this->debugLog("[INFO] Live stream id#" . $this->livefeedstreamid . " status changed to '" . $status . "'.", false);
        
    }

    ## Live thumbnail related
    
    // Update livefeed index photo
    public function updateLiveFeedStreamIndexPhoto($indexphotofilename) {

        if ( empty($indexphotofilename) ) throw new \Videosquare\Model\Exception('Empty index photo filename.');

        $values = array(
            'indexphotofilename'	=> $indexphotofilename
        );

        $recordingVersionObj = $this->bootstrap->getVSQModel('livefeed_streams');
        $recordingVersionObj->select($this->livefeedstreamid);
        $recordingVersionObj->updateRow($values);
        $recordingVersionObj->updateFeedThumbnail();

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

        $model = $this->bootstrap->getVSQModel('view_statistics_live');
        $rs = $model->safeExecute($query);

        // Check if any record returned
        if ( $rs->RecordCount() < 1 ) return false;

        // Convert AdoDB resource to array
        $rs_array = adoDBResourceSetToArray($rs);

        return $rs_array;
    }

    // Update currentviewers counter for specified livefeed
    public function updateLiveFeedViewCounter($currentviewers) {
        
        if ( empty($currentviewers) ) throw new \Videosquare\Model\Exception('Number of current livefeed viewers is empty.');
        
        // Update livefeed currentviewers counter
        $query = "
            UPDATE
                livefeeds
            SET
                currentviewers = " . $currentviewers. "
            WHERE
                id = " . $this->livefeedid;

        $model = $this->bootstrap->getVSQModel('livefeeds');
        $rs = $model->safeExecute($query);

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
        
        $model = $this->bootstrap->getVSQModel('livefeeds');
        $rs = $model->safeExecute($query);

        return $model->db->Affected_Rows();
    }
    
}
