<?php
namespace Videosquare\Model;

class Recordings extends \Springboard\Model {

    private $recordingid;
    private $recordingversionid;
    
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

    
}
