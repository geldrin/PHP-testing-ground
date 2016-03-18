<?php
namespace Videosquare\Model;

class Users extends \Springboard\Model {
    
    private $userid = null;
    
    // Debug
    private $d = null;
    private $logDir = null;
    private $logFile = null;
    public  $debug_mode = null;
    
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
    
    // ## Select object for further operations
    
    // Select user ID
    public function selectUser($userid) {
    
        if ( empty($userid) ) throw new \Videosquare\Model\Exception('Attached document ID is empty.');
        
        $this->userid = $userid;
        
    }
    
    // Get selected attached document ID
    public function getSelectedUser() {
    
        return $this->userid;
    }
    
    // Status functions get/set

    // Avatars

    // Get attached documents by status for this specific front-end
    function getAvatarsByStatus($status) {

        if ( empty($status) ) throw new \Videosquare\Model\Exception('[ERROR] Cannot get avatar list. Status is empty.');

        $query = "
            SELECT
                a.id AS userid,
                a.nickname,
                a.email,
                a.avatarfilename,
                a.avatarstatus,
                a.organizationid,
                a.avatarsourceip,
                b.domain
            FROM
                users AS a,
                organizations AS b
            WHERE
                a.avatarstatus = '" . $status . "' AND
                a.avatarsourceip = '" . $this->bootstrap->config['node_sourceip'] . "' AND
                a.organizationid = b.id";

        $model = $this->bootstrap->getVSQModel('users');
        $rs = $model->safeExecute($query);

        if ( $rs->RecordCount() < 1 ) return false;

        return $rs;
    }
    
    public function updateAvatarStatus($status) {

        if ( empty($this->userid) ) throw new \Videosquare\Model\Exception('[ERROR] Cannot set avatar status. User ID is empty.');
	
        if ( empty($status) ) throw new \Videosquare\Model\Exception('[ERROR] Cannot set avatar status. Status is empty.');

        $values = array(
            'avatarstatus' => $status
        );

        try {
            $AttachmentObj = $this->bootstrap->getModel('users');
            $AttachmentObj->select($userid);
            $AttachmentObj->updateRow($values);
        } catch ( \Videosquare\Model\Exception $err) {
            throw $err;
        }

        // Log status change
        $this->debugLog("[INFO] User avatar status = '" . $status . "'", false);

    }
    
}
