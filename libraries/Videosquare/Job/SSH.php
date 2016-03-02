<?php
namespace Videosquare\Job;

// Requirements: php5-ssh2

class SSH { 
    // SSH Host 
    private $ssh_host = null; 
    // SSH Port 
    private $ssh_port = 22; 
    // SSH Server Fingerprint 
    private $ssh_server_fp = null;  // hex fingerprint 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'
    // SSH Username 
    private $ssh_auth_user = null; 
    // SSH Private Key Passphrase (null == no passphrase) 
    private $ssh_auth_pass = null; 
    // SSH Public Key File 
    private $ssh_auth_pub = null;   // '/home/username/.ssh/id_rsa.pub'; 
    // SSH Private Key File 
    private $ssh_auth_priv = null;  // '/home/username/.ssh/id_rsa';
    // SSH Connection 
    private $connection;
    // SSH connection status
    private $connected = false;
    // Command
    private $command = null;
    // Command return code
    private $command_return_code = null;
    // Command error message
    private $message = null;
    // Command execution start timer
    private $command_timer_start = null;
    // Command execution duration
    private $command_duration = null;
   
    public function __construct($ssh_host, $ssh_port, $ssh_user, $ssh_pass, $ssh_auth_pub = null, $ssh_auth_priv = null, $ssh_fp = null) {
        
        // Is extension loaded
        if ( !extension_loaded('ssh2') ) {
            throw new \Videosquare\Model\Exception('[ERROR] SSH2 extension is not loaded!');
        }
            
        if ( !isset($ssh_host) ) {
            throw new \Videosquare\Model\Exception('[ERROR] No host name provided');
        } else {
            $this->ssh_host = $ssh_host;
        }
        
        if ( isset($ssh_port) ) $this->ssh_port = $ssh_port;
        
        // Username
        if ( !isset($ssh_user) ) {
            throw new \Videosquare\Model\Exception('[ERROR] No username provided');
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
                throw new \Videosquare\Model\Exception('[ERROR] No password, neither public/private key file provided');
            }
        }
        
        // Fingerprint
        if ( !empty($ssh_fp) ) $this->ssh_server_fp = strtoupper($ssh_fp);
        
        $this->connected = false;
    }
    
    public function connect() { 
    
        // Connect to SSH server
        if ( !($this->connection = ssh2_connect($this->ssh_host, $this->ssh_port)) ) { 
            throw new \Videosquare\Model\Exception('[ERROR] Cannot connect to server', 100); 
        }

        // Check server fingerprint
        if ( !empty($this->ssh_server_fp) ) {
            $fingerprint = ssh2_fingerprint($this->connection, SSH2_FINGERPRINT_MD5 | SSH2_FINGERPRINT_HEX);
            if ( strcmp($this->ssh_server_fp, $fingerprint) !== 0 ) { 
            echo $fingerprint . "\n";
                throw new \Videosquare\Model\Exception('[ERROR] Unable to verify server identity!', 100); 
            } else { echo "FP is OK!"; }
        }
        
        // Connect
        if ( empty($this->ssh_auth_pub) and empty($this->ssh_auth_priv) ) {
            // Username / password authentication
            if( !ssh2_auth_password($this->connection, $this->ssh_auth_user, $this->ssh_auth_pass) ) {
                throw new \Videosquare\Model\Exception('[ERROR] Cannot log in using this username/password', 100);
            }
        } else {
            // Authenticate using key files
            if ( !ssh2_auth_pubkey_file($this->connection, $this->ssh_auth_user, $this->ssh_auth_pub, $this->ssh_auth_priv, $this->ssh_auth_pass) ) { 
                throw new \Videosquare\Model\Exception('[ERROR] Autentication rejected by server', 100); 
            }            
        }

        $this->connected = true;

        return true;
    }
    
    public function exec($command) {
        
        $this->initCommand();
        
        if ( !$this->connected ) throw new \Videosquare\Model\Exception('[ERROR] Cannot exec command. Not connected to server');

        if ( empty($command) ) throw new \Videosquare\Model\Exception("[ERROR] Empty SSH command.");
        
        $this->command = $command;
        
        // Start command timer
        $this->command_timer_start = time();
        
        if ( !( $stream = ssh2_exec($this->connection, $command . ';echo "[return_code:$?]"') ) ) {
            throw new \Videosquare\Model\Exception('[ERROR] SSH command failed'); 
        }
        
        stream_set_blocking($stream, true); 

        $data = ""; 
        while ($buf = fread($stream, 4096)) { 
            $data .= $buf; 
        } 

        fclose($stream); 

        // Calculate command execution duration
        $this->command_duration = time() - $this->command_timer_start;
        
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
                throw new \Videosquare\Model\Exception("[ERROR] Input file/directory does not exist.\nCOMMAND: " . $command . "\nOUTPUT: " . $data);                 
            }

            // Other error: return all command output
            throw new \Videosquare\Model\Exception("[ERROR] SSH command failed.\nCOMMAND: " . $command . "\nOUTPUT: " . $data);
            
        }

        return $data; 
    }
    
    public function copyFromServer($remote_file, $local_file) {
                
        if ( empty($remote_file) or empty($local_file) ) throw new \Videosquare\Model\Exception("[ERROR] Empty remote/local file name detected.");
        
        $pathinfo_local = pathinfo($local_file);

        // Check file size before start copying
        $filesize = $this->getFilesize($remote_file);
        if ( !$filesize ) throw new \Videosquare\Model\Exception("[ERROR] Cannot get remote filesize.");

        $this->initCommand();
        
        // Start command timer
        $this->command_timer_start = time();
        
        // Check available disk space (remote file size * 2 required)
        $filesize_ratio = 1.5;      // TODO: take from configuration?
		$available_disk = floor(disk_free_space($pathinfo_local['dirname']));
		if ( $available_disk < $filesize * $filesize_ratio ) {
            $this->command_return_code = 1;
            throw new \Videosquare\Model\Exception("[ERROR] Not enough free space to start copying (available: " . ceil($available_disk / 1024 / 1024) . "Mb, filesize: " . ceil($filesize / 1024 / 1024) . "). Minimum " . $filesize_ratio . "x needed as the size of remote file.");
		}

        // Executing shell SCP command beacause ssh2_scp_recv() is:
        // o slow and not capable of copying very large files
        // o not capable of copying entire directories
        /* if ( !ssh2_scp_recv($this->connection, $remote_file, $local_file) ) {
            throw new \Videosquare\Model\Exception('[ERROR] Cannot copy remote file');
        } */
        $this->command = "scp -B -r -i " . $this->ssh_auth_priv . " " . $this->ssh_auth_user . "@" . $this->ssh_host . ":" . $remote_file . " " . $local_file . " 2>&1";
        exec($this->command, $output, $this->command_return_code);

        // Calculate command execution duration
        $this->command_duration = time() - $this->command_timer_start;
        
        if ( $this->getLastCommandReturnCode() != 0 ) throw new \Videosquare\Model\Exception("[ERROR] SCP download failed.\nCOMMAND: " . $this->command . "\nOUTPUT: " . implode("\n", $output));

        $this->message = "[OK] SCP download finished (in " . round( $this->command_duration / 60, 2) . " mins). Remote " . $this->ssh_auth_user . "@" . $this->ssh_host . ":" . $remote_file . " was download to " . $local_file;

        return true;
    }

    public function copyToServer($local_file, $remote_file) {
    
        if ( empty($local_file) or empty($remote_file) ) return false;

        $this->initCommand();
        
        if ( !file_exists($local_file) ) {
            $this->command_return_code = 1;
            throw new \Videosquare\Model\Exception("[ERROR] Local file " . $local_file . " does not exist.");
        }

        $this->initCommand();
        
        // Start command timer
        $this->command_timer_start = time();

        // Executing shell SCP command
        $this->command = "scp -B -r -i " . $this->ssh_auth_priv . " " . $local_file . " " . $this->ssh_auth_user . "@" . $this->ssh_host . ":" . $remote_file . " 2>&1";
        exec($this->command, $output, $this->command_return_code);
        
        // Calculate command execution duration
        $this->command_duration = time() - $this->command_timer_start;
        
        if ( $this->getLastCommandReturnCode() != 0 ) throw new \Videosquare\Model\Exception("[ERROR] SCP upload failed.\nCOMMAND: " . $this->command . "\nOUTPUT: " . implode("\n", $output));

        $this->message = "[OK] SCP upload finished (in " . 	round( $this->command_duration / 60, 2) . " mins). Local " . $local_file . " was uploaded to " . $this->ssh_auth_user . "@" . $this->ssh_host . ":" . $remote_file . ".";
        
        return true;
    }
    
    public function disconnect() { 
        if ( $this->connected ) {
            $this->exec('exit'); 
            $this->connection = null; 
            $this->connected = false;
        }
    } 

    public function getLastCommandReturnCode() {
        return $this->command_return_code;
    }

    public function getLastCommandMessage() {
        return $this->message;
    }

    public function getLastCommand() {
        return $this->command;
    }

    public function getLastCommandDuration() {
        return $this->command_duration;
    }

    // Init command related variables
    private function initCommand() {
        $this->command = "";
        $this->command_return_code = 0;
        $this->message = "";
        $this->command_timer_start = null;
        $this->command_duration = 0;
        return true;
    }
    
    public function getFileSize($file) {

        $this->initCommand();
    
        if ( empty($file) ) return false;
    
        $command = "du -sb " . $file . " 2>&1";
        $command_output = $this->exec($command);

        if ( $this->getLastCommandReturnCode() != 0 ) throw new \Videosquare\Model\Exception("[ERROR] Cannot get filesize.");
        
        // Parse output
        $tmp = preg_split('/\s+/', $command_output);
        $filesize = $tmp[0];
        if ( !is_numeric($filesize) ) {
            $this->command_return_code = 1;
            throw new \Videosquare\Model\Exception("[ERROR] File length invalid: " . $this->ssh_auth_user . "@" . $this->ssh_host . ":" . $file . "\nCOMMAND OUTPUT: " . $filesize);
        }

        $this->message = "[OK] Remote " . $this->ssh_auth_user . "@" . $this->ssh_host . ":" . $file . " size is: " . $filesize;
        
        return intval($filesize);
    }
    
    public function getFileModificationTime($file) {

        $this->initCommand();

        if ( empty($file) ) return false;
        
        $command = "stat -c %Y " . $file . " 2>&1";
        $command_output = $this->exec($command);

        if ( $this->getLastCommandReturnCode() != 0 ) throw new \Videosquare\Model\Exception("[ERROR] Cannot get mtime of remote file. Does not exist?");
        
        // Parse output
        $tmp = preg_split('/\s+/', $command_output);
        $filemtime = $tmp[0];
        if ( !is_numeric($filemtime) ) {
            $this->command_return_code = 1;
            throw new \Videosquare\Model\Exception("[ERROR] Input file/directory mtime invalid: " . $this->ssh_auth_user . "@" . $this->ssh_host . ":" . $file . "\nCOMMAND OUTPUT: " . $filemtime);
        }
        
        $this->message = "[OK] Remote file " . $this->ssh_auth_user . "@" . $this->ssh_host . ":" . $file . " mtime is: " . $filemtime;
        
        return intval($filemtime);
    }

    public function compareRemoteAndLocaleFileMTime($remote_file, $local_file) {

        $this->initCommand();
    
        // File already exists in temp area
        if ( !file_exists($local_file) ) throw new \Videosquare\Model\Exception("[ERROR] Local file " . $local_file . " does not exist.");

        // ## Filesize and file mtime check
        // Get local filesize
        $local_filesize = filesize($local_file);
        // Get local file mtime
        $local_filemtime = filemtime($local_file);
        
        // Get remote filesize
        $remote_filesize = $this->getFileSize($remote_file);
        if ( $this->getLastCommandReturnCode() != 0 ) throw new \Videosquare\Model\Exception("[ERROR] Cannot get remote file size.");
        // Get remote file mtime
        $remote_filemtime = $this->getFileModificationTime($remote_file);
        if ( $this->getLastCommandReturnCode() != 0 ) throw new \Videosquare\Model\Exception("[ERROR] Cannot get remote file mtime.");
        
        $msg  = "Local file: " . $local_file . " (size = " . $local_filesize . ", mtime = " . date("Y-m-d H:i:s", $local_filemtime) . ")\n";
        $msg .= "Remote file: " . $remote_file . " (size = " . $remote_filesize . ", mtime = " . date("Y-m-d H:i:s", $remote_filemtime) . ")";
            
        // File size match and file mtime check: do they different?
        if ( ( $local_filesize != $remote_filesize ) or ( $local_filemtime < $remote_filemtime ) ) {
            $this->message  = "[INFO] Local file is NOT up to date.\n" . $msg;
            return false;
        }            
            
        $this->message = "[OK] Local file is up to date.\n" . $msg;

        return true;
    }

    // Chmod and chown remote file(s)
    public function doChmodChown($file, $permissions = null, $owners = null) {

        if ( empty($permissions) and empty($owners) ) throw new \Videosquare\Model\Exception("[ERROR] Parameters are empty. Nothing to do.");
    
        $this->initCommand();

        $command = "";
        if ( !empty($permissions) ) $command .= "chmod -f -R " . $permissions . " " . $file . " 2>&1";
        
        if ( !empty($owners) ) {
            if ( !empty($command) ) $command .= " ; ";
            $command .= "chown -f -R " . $owners . " " . $file . " 2>&1";
        }
        
        $command_output = $this->exec($command);

        if ( $this->getLastCommandReturnCode() != 0 ) throw new \Videosquare\Model\Exception("[WARN] SCP cannot stat " . $this->ssh_auth_user . "@" . $this->ssh_host . ":" . $file . " file.");
        
        $this->message = "[OK] SCP stat " . $this->ssh_auth_user . "@" . $this->ssh_host . ":" . $file . " file.";

        return true;
    }
    
    // Disconnect
    public function __destruct() { 
        $this->disconnect(); 
    } 
} 
?>