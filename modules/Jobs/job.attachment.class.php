﻿<?php

include_once('job.class.php');

class Attachment extends Job {

    public $doc = null;
    public $cache_size = null;
    
    private $cache = null;

    // ATTACHMENT status functions get/set

    // Update attached document status
    public function updateAttachedDocumentStatus($status, $type = null) {

        if ( !isset($this->doc['id']) ) return false;
    
        if ( !empty($type) and ( $type != "indexingstatus" ) ) return false;

        $idx = "";
        if ( $type == "indexingstatus" ) $idx = "indexing";

        if ( empty($status) ) $status = null;

        $values = array(
            $idx . 'status' => $status
        );

        try {
            $AttachmentObj = $this->app->bootstrap->getModel('attached_documents');
            $AttachmentObj->select($this->doc['id']);
            // !!! TODO: safe!
            $AttachmentObj->updateRow($values);
        } catch (exception $err) {
            $this->debug->log("[ERROR] SQL query failed." . trim(substr($err, 1, 255)), true);
            return false;
        }

        // Log status change
        $this->debugLog("[INFO] Attached document id = " . $this->doc['id'] . " " . $type . " has been changed to '" . $status . "'.", false);

        return true;
    }

    // ATTACHMENT cache manipulation
    
    // Update attached document cache
    public function updateAttachedDocumentCache() {

        $AttachmentObj = $this->app->bootstrap->getModel('attached_documents');
        
        if ( !empty($this->cache) ) {
            $documentcache_escaped = $AttachmentObj->db->qstr($this->cache);
        } else {
            $documentcache_escaped = null;
        }

        $values = array(
            'documentcache' => $documentcache_escaped
        );

        try {
            $AttachmentObj->select($this->doc['id']);
            // !!! TODO: safe!
            $AttachmentObj->updateRow($values);
        } catch (exception $err) {
            $this->debugLog("[ERROR] SQL query failed." . trim(substr($err, 1, 255)), true);
            return false;
        }

        $this->debugLog("[INFO] Attached document id = " . $this->doc['id'] . " cache has been updated.", false);

        return true;
    }

    public function addTextToDocumentCache($text) {
        
        $tmp = trim($text);
        if ( empty($tmp) ) return true;

        // Convert to UTF-8
        $tmp = mb_convert_encoding($tmp, "UTF-8");

        // Remove excess white spaces
        $text_stripped = preg_replace('/\s\s+/', ' ', $tmp);
        $this->cache .= $text_stripped . " ";
                
        // Update document cache size
        $this->cache_size = strlen($this->cache);
    
        // Debug
        if ( $this->debug_mode ) $this->debugLog("[DEBUG] Added to document cache: " . $text_stripped, false);
        if ( $this->debug_mode ) $this->debugLog("[DEBUG] Document cache size: " . $this->cache_size, false);
    
        return true;
    }
    
    // Clean up text index
    public function addTextFileToDocumentCache($file) {
        
		if ( !file_exists($file) or ( filesize($file) == 0 ) ) {
            $this->debugLog("[ERROR] Indexing output file does not exist or zero size. File: " . $file, true);
			return false;
		}

        $line_num = 0;
        $fh = fopen($file, 'r');
        while( !feof($fh) ) {
            $line = fgets($fh);
            $this->addTextToDocumentCache($line);
            $line_num++;
        }        
        fclose($fh);
        
        // Debug
        if ( $this->debug_mode ) $this->debugLog("[DEBUG] Text file " . $file . " content added to document cache (lines: " . $line_num . ")", false);
        
        return true;
    }

    // ATTACHMENT get next job
    
    // Get next attached document conversion job
    public function getAttachedDocumentJobs() {
    
        $query = "
            SELECT
                a.id,
                a.title,
                a.masterfilename,
                a.masterextension,
                a.isdownloadable,
                a.status,
                a.sourceip,
                a.recordingid AS rec_id,
                a.userid,
                b.nickname,
                b.email,
                b.language
            FROM
                attached_documents AS a,
                users AS b
            WHERE
                a.status = '" . $this->config_jobs['dbstatus_copystorage_ok'] . "' AND
                ( a.masterextension IN ('txt', 'csv', 'xml', 'htm', 'html', 'doc', 'docx', 'odt', 'ott', 'sxw', 'pdf', 'ppt', 'pptx', 'pps', 'odp') ) AND
                ( a.indexingstatus IS NULL OR a.indexingstatus = '' ) AND
                a.userid = b.id";

        try {
            $model = $this->app->bootstrap->getModel('attached_documents');
            $rs = $model->safeExecute($query);
        } catch (exception $err) {
            $this->debugLog("[ERROR] SQL query failed. Query:\n" . trim($query) . "\nError: ". $err, true);
            return false;
        }

        if ( $rs->RecordCount() < 1 ) return false;
        
        return $rs;
    }

    // ATTACHMENT conversion related
    
    // Identify file UNIX type
    public function identifyFile($filename) {

        unset($output);
        $command = "file -z -b " . $filename;
        exec($command, $output, $result);
        $output_string = implode("\n", $output);
        if ( $result != 0 ) {
            $this->debugLog("[WARNING] File command output error. Command:\n" . $command . "\nError message:\n" . $output_string, true);
            return false;
        }

        return $output_string;
    }

    // Identify attached document
    public function identifyAttachedDocument() {
        
        if ( empty($this->doc['source_file']) ) return false;
        
    	$file_type = $this->identifyFile($this->doc['source_file']);
        
        // Debug
        if ( $this->debug_mode ) $this->debugLog("[DEBUG] File UNIX type detection output: " . $file_type, false);
        
        if ( !$file_type ) return false;

        $this->doc['file_unix_type'] = $file_type;
        
        // DB: update document type
        $update = array(
            'type'	=> $this->doc['file_unix_type']
        );
        $attDoc = $this->app->bootstrap->getModel('attached_documents');
        $attDoc->select($this->doc['id']);
        // !!! TODO: safe!
        $attDoc->updateRow($update);

        // Text file, XML document, CSV, HTML, DOCX, PPTX, ODT, ODP or other text
        if ( stripos($file_type, "text") !== false ) {
            $this->doc['file_type'] = "text";
        }

        // PDF file
        if ( stripos($file_type, "PDF") !== false ) {
            $this->doc['file_type'] = "pdf";
        }

        // Data file
        if ( stripos($file_type, "data") !== false ) {
            $this->doc['file_type'] = "data";
        }

        // Executable
        if ( stripos($file_type, "executable") !== false ) {
            $this->doc['file_type'] = "executable";
            $this->debugLog("[WARN] Document to be indexed is an executable. Unix type: " . $file_type, false);
            return false;
        }

        // Debug
        if ( $this->debug_mode ) $this->debugLog("[DEBUG] File type detection output: " . $this->doc['file_type'], false);
        
        return true;
    }
    
    public function convertUnoConv($type = null) {
    
        if ( $type != "pdf" and $type != "txt" ) return false;

        $err['code'] = false;
        $err['command_output'] = "-";
        $err['result'] = 0;
        
        // Document -> PDF
        if ( $type == "pdf" ) {
            $err['command'] = "unoconv -n -v -f pdf " . $this->doc['source_file'] . " 2>&1";
            $err['output_file'] = $this->doc['temp_directory'] . $this->doc['id'] . ".pdf";
        }
        // Document -> TXT
        if ( $type == "txt" ) {
            $err['command'] = "unoconv -n -v -f txt " . $this->doc['source_file'] . " 2>&1";
            $err['output_file'] = $this->doc['temp_directory'] . $this->doc['id'] . ".txt";
        }
        
        exec($err['command'], $output, $err['result']);
        $err['command_output'] = implode("\n", $output);
        
        // Debug
        if ( $this->debug_mode ) $this->debugLog("[DEBUG] Unoconv conversion:\n" . print_r($err, true), false);
        
        if ( $err['result'] != 0 ) return $err;
                
        $err['code'] = true;
        return $err;
    }
    
    public function convertPdf2Text($file) {
        
        $err['code'] = false;
        $err['command_output'] = "-";
        $err['result'] = 0;
        $err['output_file'] = $this->doc['temp_directory'] . $this->doc['id'] . ".txt";

        $err['command'] = "pdftotext -q -nopgbrk -layout -enc UTF-8 " . $file;
        exec($err['command'], $output, $err['result']);
        $err['command_output'] = implode("\n", $output);
        
        // Debug
        if ( $this->debug_mode ) $this->debugLog("[DEBUG] pdf2text conversion:\n" . print_r($err, true), false);

		if ( $err['result'] != 0 ) return $err;
        
        $err['code'] = true;
        return $err;        
    }

    
    // ATTACHMENT download
    
    public function copyAttachedDocumentToConverter() {

        // Update watchdog timer
        $this->watchdog();

        // Update media status to copying
        $this->updateAttachedDocumentStatus($this->config_jobs['dbstatus_copyfromfe'], 'indexingstatus');

        // Prepare temporary conversion directories, remove any existing content
        $temp_directory = $this->config_jobs['doc_dir'] . $this->doc['rec_id'] . "/";
        $err = create_remove_directory($temp_directory);
        if ( !$err['code'] ) {
            $this->debugLog("MSG: " . $err['message'] . "\nCOMMAND: " . $err['command'] . "\nRESULT: " . $err['result'], true);
            return false;
        }
        if ( $this->debug_mode ) $this->debugLog("[DEBUG]:\n" . $err['message'] . "\nCOMMAND: " . $err['command'] . "\nRESULT: " . $err['result'], false);

        // Path and filename
        $base_filename = $this->doc['id'] . "." . $this->doc['masterextension'];

        $this->doc['remote_filename'] = $this->config['recordingpath'] . ( $this->doc['rec_id'] % 1000 ) . "/" . $this->doc['rec_id'] . "/attachments/" . $base_filename;
        $this->doc['source_file'] = $temp_directory . $base_filename;
        $this->doc['temp_directory'] = $temp_directory;
        
        // Debug
        if ( $this->debug_mode ) $this->debugLog("[DEBUG] Attached document information before copy:\n" . print_r($this->doc, true), false);

        // ## Copy attached document from remote location
        try {
            $ssh = new SSH($this->doc['sourceip'], 22, $this->config['ssh_user'], null, $this->config['ssh_pubkey'], $this->config['ssh_key'], $this->config['ssh_fingerprint']);
        } catch (Exception $err) {
            $this->debugLog("Caught exception: ",  $err->getMessage(), true);
            return false;
        }
        // Authenticate to SSH server
        // !!! TODO: block SSH when no connection, wait until not coming back!
        try {
            $ssh->connect();
        } catch (Exception $err) {
            $this->debugLog("Caught exception: ",  $err->getMessage(), true);
            return false;
        }
        if ( $this->debug_mode) $this->debugLog("[DEBUG] Connected to SSH server. Information: " . print_r($ssh, true), false);
        // Copy file
        try {
            $ssh->copyFromServer($this->doc['remote_filename'], $this->doc['source_file']);
        } catch (Exception $err) {
            $this->debugLog("Caught exception: ",  $err->getMessage(), true);
            $this->updateAttachedDocumentStatus($this->config_jobs['dbstatus_copyfromfe_err'], 'indexingstatus');
            return false;
        }
        if ( $this->debug_mode) $this->debugLog("[DEBUG] SCP copy has finished successfuly.", false);
        // SSH disconnect
        try {
            $ssh->disconnect();
        } catch (Exception $err) {
            $this->debugLog("Caught exception: ",  $err->getMessage(), true);
        }
        
        // Input file does not exist in temp directory
        if ( !file_exists($this->doc['source_file']) ) {
            $this->updateAttachedDocumentStatus($this->config_jobs['dbstatus_copyfromfe_err'], 'indexingstatus');
            $this->debugLog("[ERROR] Document file does NOT EXIST: " . $this->doc['source_file'], true);
            return false;
        }

        // Update filesize
        $this->doc['filesize'] = filesize($this->doc['source_file']);
        
        // Debug
        if ( $this->debug_mode ) $this->debugLog("[DEBUG] Attached document information after copy:\n" . print_r($this->doc, true), false);
        
        // Update watchdog timer
        $this->watchdog();

        return true;
    }
    
    // ATTACHMENT other
    
    // Reinit document
    public function killDocument() {
        
        $this->doc = null;
        $this->cache = null;
        $this->cache_size = 0;
        
        return true;
    }
    
}

?>