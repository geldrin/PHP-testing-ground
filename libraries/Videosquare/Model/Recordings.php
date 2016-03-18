<?php
namespace Videosquare\Model;

class Recordings extends \Springboard\Model {

    private $recordingid;
    private $recordingversionid;
    
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
    
    // Select recording
    public function selectRecording($recordingid) {

        if ( empty($recordingid) ) throw new \Videosquare\Model\Exception('Recording ID is empty.');
        
        $this->recordingid = $recordingid;
        
    }

    // Select recording version
    public function selectRecordingVersion($recordingversionid) {
    
        if ( empty($recordingversionid) ) throw new \Videosquare\Model\Exception('Recording version ID is empty.');
        
        $this->recordingversionid = $recordingversionid;
        
        return true;
    }

    // Get recording status
    public function getRecordingStatus($type = "recording") {

        if ( ( $type != "recording" ) and ( $type != "content" ) ) throw new \Videosquare\Model\Exception('Status type is not valid. Type: ' . $type);

        $idx = "";
        if ( $type == "content" ) $idx = "content";

        $recordingObj = $this->bootstrap->getVSQModel('recordings');
        $recordingObj->select($this->recordingid);
        $recording = $recordingObj->getRow();

        return $recording[$idx . 'status'];
    }

    // Update recording status
    public function updateRecordingStatus($status, $type = "recording") {
        
        $allowed_types = array('recording', 'content', 'mobile', 'ocr', 'smil', 'contentsmil');

        if ( !in_array($type, $allowed_types, $strict = true) ) throw new \Videosquare\Model\Exception('Cannot update recording status. Status type is not valid. Type: ' . $type);
        
        if ( empty($status) ) throw new \Videosquare\Model\Exception('Cannot update recording status. Status is empty.');

        $idx = null;
        if ( $type === 'recording' ) $idx = '';
        else $idx = $type;
        
        if ( !empty($status) ) {
            $values = array(
                $idx . 'status' => $status
            );
        } else {
            $values = array(
                $idx . 'status' => null
            );
        }

        $recordingVersionObj = $this->bootstrap->getVSQModel('recordings');
        $recordingVersionObj->select($this->recordingid);
        $recordingVersionObj->updateRow($values);

        // Update index photos
        if ( ( $status == $this->bootstrap->config['config_jobs']['dbstatus_copystorage_ok'] ) and ( $type == "recording" ) ) {
            $recordingObj = $this->bootstrap->getVSQModel('recordings');
            $recordingObj->select($this->recordingid);
            $recordingObj->updateChannelIndexPhotos();
        }

        // Log status change
        $this->debugLog("[INFO] Recording id = " . $this->recordingid . " " . $type . " status has been changed to '" . $status . "'.", false);

        return true;
    }

    // Update master recording status
    public function updateMasterRecordingStatus($status, $type = "recording") {

        if ( ( $type != "recording" ) and ( $type != "content" ) )  throw new \Videosquare\Model\Exception('Cannot update recording master status. Status type is not valid. Type: ' . $type);

        if ( empty($status) ) throw new \Videosquare\Model\Exception('Cannot update recording master status. Status is empty.');

        $idx = "";
        if ( $type == "content" ) $idx = "content";

        $values = array(
            $idx . 'masterstatus' => $status
        );

        $recordingVersionObj = $this->bootstrap->getVSQModel('recordings');
        $recordingVersionObj->select($this->recordingid);
        $recordingVersionObj->updateRow($values);

        // Log status change
        $this->debugLog("[INFO] Recording id = " . $this->recordingid . " " . $type . " master status has been changed to '" . $status . "'.", false);

        return true;
    }

    // RECORDING VERSION status functions get/set
    
    // Get recording version status
    function getRecordingVersionStatus() {

        $recordingVersionObj = $this->bootstrap->getVSQModel('recordings_versions');
        $recordingVersionObj->select($this->recordingversionid);
        $recversion = $recordingVersionObj->getRow();

        return $recversion['status'];
    }
    
    // Update recording version status
    public function updateRecordingVersionStatus($status) {

        if ( empty($status) ) throw new \Videosquare\Model\Exception('Cannot update recording version status. Status is empty.');

        $values = array(
            'status' => $status
        );

        $recordingVersionObj = $this->bootstrap->getVSQModel('recordings_versions');
        $recordingVersionObj->select($this->recordingversionid);
        $recordingVersionObj->updateRow($values);

        // Log status change
        $this->debugLog("[INFO] Recording version id = " . $this->recordingversionid . " status has been changed to '" . $status . "'.", false);

        return true;
    }

    // Update status of all recording versions of a recording
    public function updateRecordingVersionStatusAll($status, $type = "recording") {

        if ( ( $type != "recording" ) and ( $type != "content" ) and ( $type != "all" ) ) throw new \Videosquare\Model\Exception('Cannot update recording versions status. Type is not valid: ' . $type);

        if ( empty($status) ) throw new \Videosquare\Model\Exception('Cannot update recording versions status. Status is empty.');

        if ( $type == "recording" ) $iscontent_filter = " AND rv.iscontent = 0";
        if ( $type == "content" ) $iscontent_filter = " AND rv.iscontent = 1";
        if ( $type == "all" ) $iscontent_filter = "";

        $query = "
            UPDATE
                recordings_versions AS rv
            SET
                rv.status = '" . $status . "'
            WHERE
                rv.recordingid = " . $this->recordingid . $iscontent_filter;

        try {
            $model = $this->bootstrap->getVSQModel('recordings_versions');
            $rs = $model->safeExecute($query);
        } catch (exception $err) {
            $this->debugLog("[ERROR] SQL query failed.\n" . trim(substr($query, 1, 255)) . "\n\nERR:\n" . trim(substr($err, 1, 255)), true);
            return false;
        }

        // Log status change
        $this->debugLog("[INFO] All recording versions for recording id = " . $this->recordingid . " have been changed to '" . $status . "' status.", false);

        return true;
    }

    // Get recording versions of a recording with specific status filter
    public function getRecordingVersionsApplyStatusFilter($type = "recording", $statusfilter) {

        if ( ( $type != "recording" ) and ( $type != "content" ) and ( $type != "all" ) )  throw new \Videosquare\Model\Exception('Cannot get recording versions. Type is not valid: ' . $type);

        if ( $type == "recording" ) $iscontent_filter = " AND rv.iscontent = 0";
        if ( $type == "content" ) $iscontent_filter = " AND rv.iscontent = 1";
        if ( $type == "all" ) $iscontent_filter = "";

        $sql_statusfilter = "";
        if ( !empty($statusfilter) ) {
            $statuses2filter = explode("|", $statusfilter);
            for ( $i = 0; $i < count($statuses2filter); $i++ ) {
                $sql_statusfilter .= "'" . $statuses2filter[$i] . "'";
                if ( $i < count($statuses2filter) - 1 ) $sql_statusfilter .= ",";
            }
            $sql_statusfilter = " AND rv.status IN (" . $sql_statusfilter . ")";
        }

        $query = "
            SELECT
                rv.id,
                rv.recordingid,
                rv.encodingprofileid,
                rv.encodingorder,
                rv.qualitytag,
                rv.filename,
                rv.iscontent,
                rv.status,
                rv.resolution,
                rv.bandwidth,
                rv.isdesktopcompatible,
                rv.ismobilecompatible
            FROM
                recordings_versions AS rv
            WHERE
                rv.recordingid = " . $this->recordingid . $iscontent_filter . $sql_statusfilter;

        try {
            $model = $this->bootstrap->getVSQModel('recordings_versions');
            $rs = $model->safeExecute($query);
        } catch ( Exception $err) {
            $this->debugLog("[ERROR] SQL query failed.\n" . trim(substr($query, 1, 255)) . "\n\nERR:\n" . trim(substr($err, 1, 255)), true);
            return false;
        }

        // Check if any record returned
        if ( $rs->RecordCount() < 1 ) return false;

        return true;
    }

    // Update status of recording version(s) of a recording with specific filter
    // # Important: When especially deleting recordings and recording versions, we cannot set
    // # all recording version statuses to "markedfordeletion" blindly. This would affect recording versions
    // # with undesired statuses such as "deleted" (removed earlier), going back to "markedfordeletion" again.
    public function updateRecordingVersionStatusApplyFilter($status, $typefilter, $statusfilter) {

        // Check parameters
        if ( empty($status) ) throw new \Videosquare\Model\Exception('Cannot update recording versions status. Status is empty.');

        if ( empty($typefilter) or ( $typefilter == "all" ) ) $typefilter = "recording|content|pip";

        // Build type filter
        $sql_typefilter = "";
        $tmp = explode("|", $typefilter);
        for ( $i = 0; $i < count($tmp); $i++ ) {
            // Check if type is valid
            if ( ( $tmp[$i] != "recording" ) and ( $tmp[$i] != "content" ) and ( $tmp[$i] != "pip" ) ) throw new \Videosquare\Model\Exception('Cannot update recording versions status. Invalid type filter: ' . $typefilter);
            $sql_typefilter .= "'" . $tmp[$i] . "'";
            if ( $i < count($tmp) - 1 ) $sql_typefilter .= ",";
        }
        $sql_typefilter = " AND ep.type IN (" . $sql_typefilter . ")";

        // Build status filter
        $sql_statusfilter = "";
        if ( !empty($statusfilter) ) {
            $tmp = explode("|", $statusfilter);
            for ( $i = 0; $i < count($tmp); $i++ ) {
                $sql_statusfilter .= "'" . $tmp[$i] . "'";
                if ( $i < count($tmp) - 1 ) $sql_statusfilter .= ",";
            }
            $sql_statusfilter = " AND rv.status IN (" . $sql_statusfilter . ")";
        }

        $query = "
            UPDATE
                recordings_versions AS rv,
                encoding_profiles AS ep
            SET
                rv.status = '" . $status . "'
            WHERE
                rv.recordingid = " . $this->recordingid . " AND
                rv.encodingprofileid = ep.id" . $sql_typefilter . $sql_statusfilter;

        try {
            $model = $this->bootstrap->getVSQModel('recordings_versions');
            $rs = $model->safeExecute($query);
        } catch (exception $err) {
            $this->debugLog("[ERROR] SQL query failed.\n" . trim(substr($query, 1, 255)) . "\n\nERR:\n" . trim(substr($err, 1, 255)), true);
            return false;
        }

        // Log status change
        $this->debugLog("[INFO] All recording versions with status filter = '" . $statusfilter . "' and type filter = '" . $typefilter . "' for recording id = " . $this->recordingid . " have been changed to '" . $status . "' status.", false);

        return true;
    }

    // ENCODING PROFILES

    // Update recording encoding profile group for a recording
    public function updateRecordingEncodingProfile($encodinggroupid) {

        if ( empty($encodinggroupid) ) throw new \Videosquare\Model\Exception('Cannot update recording encoding profile, encodinggroupid is empty.');

        $values = array(
            'encodinggroupid' => $encodinggroupid
        );

        $recordingVersionObj = $this->bootstrap->getVSQModel('recordings');
        $recordingVersionObj->select($this->recordingid);
        $recordingVersionObj->updateRow($values);

        // Log change
        $this->debugLog("[INFO] Recording id = " . $this->recordingid . " encoding group changed to '" . $encodinggroupid . "'.", false);

        return true;
    }

    // MEDIA INFO
    
    public function updateMediaInfo($profile) {

        $values = array(
            'qualitytag'			=> $profile['shortname'],
            'filename'				=> $this->recording['output_basename'],
            'isdesktopcompatible'	=> $profile['isdesktopcompatible'],
            'ismobilecompatible'	=> max($profile['isioscompatible'], $profile['isandroidcompatible'])
        );

        if ( !empty($this->recording['encodingparams']['resx']) and !empty($this->recording['encodingparams']['resy']) ) {
            $values['resolution'] = $this->recording['encodingparams']['resx'] . "x" . $this->recording['encodingparams']['resy'];
        }

        $values['bandwidth'] = 0;
        if ( !empty($this->recording['encodingparams']['audiobitrate']) ) $values['bandwidth'] += $this->recording['encodingparams']['audiobitrate'];
        if ( !empty($this->recording['encodingparams']['videobitrate']) ) $values['bandwidth'] += $this->recording['encodingparams']['videobitrate'];

        $recordingVersionObj = $this->bootstrap->getVSQModel('recordings_versions');
        $recordingVersionObj->select($this->recordingversionid);
        $recordingVersionObj->updateRow($values);

        // Log status change
        $this->debugLog("[INFO] Recording version id = " . $this->recordingversionid . " media information has been updated.\n" . print_r($values, true), false);

        // Video thumbnails: update if generated
        if ( !empty($this->recording['thumbnail_numberofindexphotos']) and !empty($this->recording['thumbnail_indexphotofilename']) ) {

            $values = array(
                'indexphotofilename'	=> $this->recording['thumbnail_indexphotofilename'],
                'numberofindexphotos'	=> $this->recording['thumbnail_numberofindexphotos']
            );

            $recordingObj = $this->bootstrap->getVSQModel('recordings');
            $recordingObj->select($this->recordingid);
            $recordingObj->updateRow($values);

            // Log status change
            $this->debugLog("[INFO] Recording id = " . $this->recordingid . " thumbnail information has been updated.\n" . print_r($values, true), false);
        }

        return true;
    }

    public function getRecordingMastersToFinalize() {

        $node = $app->config['node_sourceip'];

        $query = "
            SELECT
                r.id,
                r.status,
                r.contentstatus,
                r.masterstatus,
                r.contentmasterstatus,
                r.mastersourceip,
                r.contentmastersourceip,
                r.mastervideofilename,
                r.contentmastervideofilename,
                r.mastervideoextension,
                r.contentmastervideoextension,
                r.mastermediatype,
                r.contentmastermediatype
            FROM
                recordings AS r
            WHERE
                ( r.mastersourceip = '" . $node . "' AND r.masterstatus = '" . $this->bootstrap->config['config_jobs']['dbstatus_uploaded'] . "' AND r.status = '" . $this->bootstrap->config['config_jobs']['dbstatus_copystorage_ok'] . "' ) OR
                ( r.contentmastersourceip = '" . $node . "' AND r.contentmasterstatus = '" . $this->bootstrap->config['config_jobs']['dbstatus_uploaded'] . "' AND r.contentstatus = '" . $this->bootstrap->config['config_jobs']['dbstatus_copystorage_ok'] . "' )
            ORDER BY
                r.id";

        try {
            $model = $this->bootstrap->getVSQModel('recordings');
            $rs = $model->safeExecute($query);
        } catch (exception $err) {
            $this->debugLog("[ERROR] SQL query failed.\n" . trim($query), $sendmail = true);
            return false;
        }

        // Check if any record returned
        if ( $rs->RecordCount() < 1 ) return false;

        return $rs;
    }
    
}
