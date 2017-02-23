<?php
namespace Videosquare\Model;

class StreamingServers extends \Springboard\Model {

//    private $livefeedid = null;

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
    
    // Query all streaming servers
    public function getStreamingServers() {
		
		$query = "
			SELECT
				css.id,
				css.server,
				css.serverip,
				css.shortname,
				css.type,
				css.location,
				css.default,
				css.servicetype,
				css.isrtmpcompatible,
				css.isrtspcompatible,
				css.ishdscompatible,
				css.ishlscompatible,
				css.priority,
				css.serverstatus,
				css.currentload,
				css.disabled,
				css.reportsequencenum,
				css.lastreporttimestamp
			FROM
				cdn_streaming_servers AS css";

        $model = $this->bootstrap->getVSQModel('cdn_streaming_servers');
        $rs = $model->safeExecute($query);
        
        $rs_array = array();
        while ( !$rs->EOF ) {
            array_push($rs_array, $rs->fields);
            $rs->moveNext();
        }
                
        if ( count($rs_array) < 1 ) return false;
    
        return $rs_array;
    }

    
}

