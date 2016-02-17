<?php

// Requirements: php5-ssh2

class SSH { 
    // SSH Host 
    private $ssh_host = null; 
    // SSH Port 
    private $ssh_port = 22; 
    // SSH Server Fingerprint 
    private $ssh_server_fp = null; // hex fingerprint 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'
    // SSH Username 
    private $ssh_auth_user = null; 
    // SSH Private Key Passphrase (null == no passphrase) 
    private $ssh_auth_pass = null; 
    // SSH Public Key File 
    private $ssh_auth_pub = null; // '/home/username/.ssh/id_rsa.pub'; 
    // SSH Private Key File 
    private $ssh_auth_priv = null; // '/home/username/.ssh/id_rsa';
    // SSH Connection 
    private $connection;
    // SSH connection status
    private $connected = false;
    // Command return code
    private $command_return_code = null;
    // Error message
    private $command_error_message = null;
   
    public function __construct($ssh_host, $ssh_port, $ssh_user, $ssh_pass, $ssh_auth_pub = null, $ssh_auth_priv = null, $ssh_fp = null) {
        
        if ( !isset($ssh_host) ) {
            throw new Exception('[ERROR] No host name provided');
        } else {
            $this->ssh_host = $ssh_host;
        }
        
        if ( isset($ssh_port) ) $this->ssh_port = $ssh_port;
        
        // Username
        if ( !isset($ssh_user) ) {
            throw new Exception('[ERROR] No username provided');
        } else {
            $this->ssh_auth_user = $ssh_user;
        }

        // Password (used for private key file if key authentication is requested)
        if ( !empty($ssh_pass) ) $this->ssh_auth_pass = $ssh_pass;

        // Public + private key files
        if ( !empty($ssh_auth_pub) and !empty($ssh_auth_priv) ) {
            $this->ssh_auth_pub = $ssh_auth_pub;
            $this->ssh_auth_priv = $ssh_auth_priv;
        } else {
            if ( empty($this->ssh_auth_pass) ) {
                throw new Exception('[ERROR] No password, neither public/private key file provided');
            }
        }
        
        // Fingerprint
        if ( !empty($ssh_fp) ) $this->ssh_server_fp = strtoupper($ssh_fp);
        
        $this->connected = false;
    }
    
    public function connect() { 
    
        // Connect to SSH server
        if ( !($this->connection = ssh2_connect($this->ssh_host, $this->ssh_port)) ) { 
            throw new Exception('[ERROR] Cannot connect to server'); 
        }

        // Check server fingerprint
        if ( !empty($this->ssh_server_fp) ) {
            $fingerprint = ssh2_fingerprint($this->connection, SSH2_FINGERPRINT_MD5 | SSH2_FINGERPRINT_HEX);
            if ( strcmp($this->ssh_server_fp, $fingerprint) !== 0 ) { 
            echo $fingerprint . "\n";
                throw new Exception('[ERROR] Unable to verify server identity!'); 
            } else { echo "FP is OK!"; }
        }
        
        // Connect
        if ( empty($this->ssh_auth_pub) and empty($this->ssh_auth_priv) ) {
            // Username / password authentication
            if( !ssh2_auth_password($this->connection, $this->ssh_auth_user, $this->ssh_auth_pass) ) {
                throw new Exception('[ERROR] Cannot log in using this username/password');
            }
        } else {
            // Authenticate using key files
            if ( !ssh2_auth_pubkey_file($this->connection, $this->ssh_auth_user, $this->ssh_auth_pub, $this->ssh_auth_priv, $this->ssh_auth_pass) ) { 
                throw new Exception('[ERROR] Autentication rejected by server'); 
            }            
        }

        $this->connected = true;

        return true;
    }
    
    public function exec($command) {
        
        $this->command_return_code = null;
        $this->command_error_message = null;
        
        if ( !$this->connected ) throw new Exception('[ERROR] Cannot exec command. Not connected to server');

        if ( empty($command) ) return false;
        
        if ( !( $stream = ssh2_exec($this->connection, $command . ';echo "[return_code:$?]"') ) ) {
            throw new Exception('[ERROR] SSH command failed'); 
        }
        
        stream_set_blocking($stream, true); 

        $data = ""; 
        while ($buf = fread($stream, 4096)) { 
            $data .= $buf; 
        } 

        fclose($stream); 

        // Match return code
        preg_match('/\[return_code:(.*?)\]/', $data, $match);
        if ( isset($match[1]) ) {
            $this->command_return_code = $match[1];
        } else {
            $this->command_return_code = null;
        }

        // Remove return code from output
        $tmp = preg_split('/\[return_code:(.*?)\]/', $data);
        $data = $tmp[0];
        
        if ( $this->command_return_code != 0 ) {

            // Handle shell file/directory does not exist error here
            if ( strpos($data, "No such file or directory") > 0 ) {
                $this->command_error_message = "[ERROR] Input file/directory does not exist.\nCOMMAND: " . $command . "\nOUTPUT: " . $data;
            } else {
                // Other error: return all command output
                $this->command_error_message = "[ERROR] SSH command failed.\nCOMMAND: " . $command . "\nOUTPUT: " . $data;
            }
            
            return false;
        }

        return $data; 
    }
    
    public function SCPCopyFromServer($remote_file, $local_file) {
        
        if ( !ssh2_scp_recv($this->connection, $remote_file, $local_file) ) {
            throw new Exception('[ERROR] Cannot copy remote file');
        }
        
        return true;
    }
    
    public function disconnect() { 
        if ( $this->connected ) {
            $this->exec('exit'); 
            $this->connection = null; 
            $this->connected = false;
        }
    } 

    public function getCommandReturnCode() {
        return $this->command_return_code;
    }

    public function getCommandErrorMessage() {
        return $this->command_error_message;
    }

    public function getFileSize($file) {

        $this->command_return_code = 0;
        $this->command_error_message = null;
    
        if ( empty($file) ) return false;
    
        $command = "du -sb " . $file . " 2>&1";
        $command_output = $this->exec($command);

        if ( $this->getCommandReturnCode() != 0 ) return false;
        
        // Parse output
        $tmp = preg_split('/\s+/', $command_output);
        $filesize = $tmp[0];
        if ( !is_numeric($filesize) ) {
            $this->command_return_code = 1;
            $this->command_error_message = "[ERROR] File length invalid: " . $this->ssh_auth_user . "@" . $this->ssh_host . ":" . $file . "\nCOMMAND OUTPUT: " . $filesize;
            return false;
        }

        $this->command_return_code = 0;
        $this->command_error_message = null;
        
        return intval($filesize);
    }
    
    public function getFileModificationTime($file) {

        $this->command_return_code = 0;
        $this->command_error_message = null;
    
        if ( empty($file) ) return false;
        
        $command = "stat -c %Y " . $file . " 2>&1";
        $command_output = $this->exec($command);

        if ( $this->getCommandReturnCode() != 0 ) return false;
        
        // Parse output
        $tmp = preg_split('/\s+/', $command_output);
        $filemtime = $tmp[0];
        if ( !is_numeric($filemtime) ) {
            $this->command_return_code = 1;
            $this->command_error_message = "[ERROR] Input file/directory mtime invalid: " . $this->ssh_auth_user . "@" . $this->ssh_host . ":" . $file . "\nCOMMAND OUTPUT: " . $filemtime;
            return false;
        }

        $this->command_return_code = 0;
        $this->command_error_message = null;
        
        return intval($filemtime);
    }

    public function compareRemoteAndLocaleFileMTime($remote_file, $local_file) {

        $this->command_return_code = 0;
        $this->command_error_message = null;
    
        // File already exists in temp area
        if ( !file_exists($local_file) ) {
            $this->command_error_message = "[INFO] Local file " . $local_file . " does not exist.";
            return false;
        }

        // ## Filesize and file mtime check
        // Get local filesize
        $local_filesize = filesize($local_file);
        // Get local file mtime
        $local_filemtime = filemtime($local_file);
        
        // Get remote filesize
        $remote_filesize = $this->getFileSize($remote_file);
        if ( $this->getCommandReturnCode() != 0 ) return false;
        // Get remote file mtime
        $remote_filemtime = $this->getFileModificationTime($remote_file);
        if ( $this->getCommandReturnCode() != 0 ) return false;
        
        $msg  = "Local file: " . $local_file . " (size = " . $local_filesize . ", mtime = " . date("Y-m-d H:i:s", $local_filemtime) . ")\n";
        $msg .= "Remote file: " . $remote_file . " (size = " . $remote_filesize . ", mtime = " . date("Y-m-d H:i:s", $remote_filemtime) . ")";
            
        // File size match and file mtime check: do they different?
        //if ( ( $local_filesize == $remote_filesize ) and ( $local_filemtime >= $remote_filemtime ) ) {
        if ( ( $local_filesize != $remote_filesize ) or ( $local_filemtime < $remote_filemtime ) ) {
            $this->command_error_message  = "[INFO] Local file is NOT up to date.\n" . $msg;
            return false;
        }            
            
        $this->command_error_message = "[OK] Local file is up to date.\n" . $msg;

        return true;
    }
    
    // Disconnect
    public function __destruct() { 
        $this->disconnect(); 
    } 
} 
?>