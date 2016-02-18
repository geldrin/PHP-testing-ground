<?php

include_once('job.class.php');

class Recording extends Job {

    private $recordingid;
    private $recordingversionid;
    
    public $recording = null;

    // RECORDING status functions get/set

    // Select recording
    public function selectRecording($recordingid) {
    
        if ( empty($recordingid) ) return false;
        
        $this->recordingid = $recordingid;
        
        return true;
    }

    // Select recording version
    public function selectRecordingVersion($recordingversionid) {
    
        if ( empty($recordingversionid) ) return false;
        
        $this->recordingversionid = $recordingversionid;
        
        return true;
    }
    
    // Get recording status
    public function getRecordingStatus($type = "recording") {

        if ( ( $type != "recording" ) and ( $type != "content" ) ) return false;

        $idx = "";
        if ( $type == "content" ) $idx = "content";

        $recordingObj = $this->app->bootstrap->getModel('recordings');
        $recordingObj->select($this->recordingid);
        $recording = $recordingObj->getRow();

        return $recording[$idx . 'status'];
    }

    // Update recording status
    public function updateRecordingStatus($status, $type = "recording") {
        
        $allowed_types = array('recording', 'content', 'mobile', 'ocr', 'smil', 'contentsmil');

        if ( !in_array($type, $allowed_types, $strict = true) ) return false;
        if ( empty($status) ) return false;

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

        $recordingVersionObj = $this->app->bootstrap->getModel('recordings');
        $recordingVersionObj->select($this->recordingid);
        $recordingVersionObj->updateRow($values);

        // Update index photos
        if ( ( $status == $this->config_jobs['dbstatus_copystorage_ok'] ) and ( $type == "recording" ) ) {
            $recordingObj = $this->app->bootstrap->getModel('recordings');
            $recordingObj->select($this->recordingid);
            $recordingObj->updateChannelIndexPhotos();
        }

        // Log status change
        $this->debugLog("[INFO] Recording id = " . $this->recordingid . " " . $type . " status has been changed to '" . $status . "'.", false);

        return true;
    }

    // Update master recording status
    public function updateMasterRecordingStatus($status, $type = "recording") {

        if ( ( $type != "recording" ) and ( $type != "content" ) ) return false;

        if ( empty($status) ) return false;

        $idx = "";
        if ( $type == "content" ) $idx = "content";

        $values = array(
            $idx . 'masterstatus' => $status
        );

        $recordingVersionObj = $this->app->bootstrap->getModel('recordings');
        $recordingVersionObj->select($this->recordingid);
        $recordingVersionObj->updateRow($values);

        // Log status change
        $this->debugLog("[INFO] Recording id = " . $this->recordingid . " " . $type . " master status has been changed to '" . $status . "'.", false);

        return true;
    }

    // RECORDING VERSION status functions get/set
    
    // Get recording version status
    function getRecordingVersionStatus() {

        $recordingVersionObj = $this->app->bootstrap->getModel('recordings_versions');
        $recordingVersionObj->select($this->recordingversionid);
        $recversion = $recordingVersionObj->getRow();

        return $recversion['status'];
    }
    
    // Update recording version status
    public function updateRecordingVersionStatus($status) {

        if ( empty($status) ) return false;

        $values = array(
            'status' => $status
        );

        $recordingVersionObj = $this->app->bootstrap->getModel('recordings_versions');
        $recordingVersionObj->select($this->recordingversionid);
        $recordingVersionObj->updateRow($values);

        // Log status change
        $this->debugLog("[INFO] Recording version id = " . $this->recordingversionid . " status has been changed to '" . $status . "'.", false);

        return true;
    }

    // Update status of all recording versions of a recording
    public function updateRecordingVersionStatusAll($status, $type = "recording") {

        if ( ( $type != "recording" ) and ( $type != "content" ) and ( $type != "all" ) ) return false;

        if ( empty($status) ) return false;

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
            $model = $this->app->bootstrap->getModel('recordings_versions');
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

        if ( ( $type != "recording" ) and ( $type != "content" ) and ( $type != "all" ) ) return false;

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
            $model = $this->app->bootstrap->getModel('recordings_versions');
            $rs = $model->safeExecute($query);
        } catch (exception $err) {
            $this-debugLog("[ERROR] SQL query failed.\n" . trim(substr($query, 1, 255)) . "\n\nERR:\n" . trim(substr($err, 1, 255)), true);
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
        if ( empty($status) ) {
            $this->debugLog("[ERROR] updateRecordingVersionStatusApplyFilter() called with invalid status: " . $status, false);
            return false;
        }
        if ( empty($typefilter) or ( $typefilter == "all" ) ) $typefilter = "recording|content|pip";

        // Build type filter
        $sql_typefilter = "";
        $tmp = explode("|", $typefilter);
        for ( $i = 0; $i < count($tmp); $i++ ) {
            // Check if type is valid
            if ( ( $tmp[$i] != "recording" ) and ( $tmp[$i] != "content" ) and ( $tmp[$i] != "pip" ) ) {
                $this->debugLog("[ERROR] updateRecordingVersionStatusApplyFilter() called with invalid type filter: " . $typefilter, false);
                return false;
            }
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
            $model = $this->app->bootstrap->getModel('recordings_versions');
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

        if ( empty($encodinggroupid) ) return false;

        $values = array(
            'encodinggroupid' => $encodinggroupid
        );

        $recordingVersionObj = $this->app->bootstrap->getModel('recordings');
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

        $recordingVersionObj = $this->app->bootstrap->getModel('recordings_versions');
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

            $recordingObj = $this->app->bootstrap->getModel('recordings');
            $recordingObj->select($this->recordingid);
            $recordingObj->updateRow($values);

            // Log status change
            $this->debugLog("[INFO] Recording id = " . $this->recordingid . " thumbnail information has been updated.\n" . print_r($values, true), false);
        }

        return true;
    }
    
}

?>