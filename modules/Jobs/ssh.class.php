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
    
    public function exec($cmd) {
        if ( !$this->connected ) throw new Exception('[ERROR] Cannot exec command. Not connected to server');

        if ( !( $stream = ssh2_exec($this->connection, $cmd) ) ) {
            throw new Exception('[ERROR] SSH command failed'); 
        }

        stream_set_blocking($stream, true); 

        $data = ""; 
        while ($buf = fread($stream, 4096)) { 
            $data .= $buf; 
        } 

        fclose($stream); 

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
    
    public function __destruct() { 
        $this->disconnect(); 
    } 
} 
?>