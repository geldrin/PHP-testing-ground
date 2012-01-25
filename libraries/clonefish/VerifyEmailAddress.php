<?php 
/*  
   $Id: VerifyEmailAddress.php 10 2008-11-16 18:49:20Z visser $ 
    
   Email address verification with SMTP probes 
   Dick Visser <dick@tienhuis.nl> 

   INTRODUCTION 

   This function tries to verify an email address using several tehniques, 
   depending on the configuration.  

   Arguments that are needed: 

   $email (string) 
   The address you are trying to verify 

   $domainCheck (boolean) 
   Check if any DNS MX records exist for domain part 

   $verify (boolean) 
   Use SMTP verify probes to see if the address is deliverable. 

   $probe_address (string) 
   This is the email address that is used as FROM address in outgoing 
   probes. Make sure this address exists so that in the event that the 
   other side does probing too this will work. 
    
   $helo_address (string) 
   This should be the hostname of the machine that runs this site. 

   $return_errors (boolean) 
   By default, no errors are returned. This means that the function will evaluate 
   to TRUE if no errors are found, and false in case of errors. It is not possible 
   to return those errors, because returning something would be a TRUE. 
   When $return_errors is set, the function will return FALSE if the address 
   passes the tests. If it does not validate, an array with errors is returned. 


   A global variable $debug can be set to display all the steps. 


   EXAMPLES 

   Use more options to get better checking. 
   Check only by syntax:  validateEmail('dick@tienhuis.nl') 
   Check syntax + DNS MX records: validateEmail('dick@tienhuis.nl', true);    
   Check syntax + DNS records + SMTP probe: 
   validateEmail('dick@tienhuis.nl', true, true, 'postmaster@tienhuis.nl', 'outkast.tienhuis.nl'); 


   WARNING 
  
   This function works for now, but it may well break in the future. 

*/ 
function validateEmail($email, $domainCheck = false, $verify = false, $probe_address='', $helo_address='', $return_errors=false, $dns = Array() ) { 
    global $debug; 
    $server_timeout = 180; # timeout in seconds. Some servers deliberately wait a while (tarpitting) 
    if($debug) {echo "<pre>";} 
    # Check email syntax with regex 
    if (preg_match('/^([a-zA-Z0-9\._\+-]+)\@((\[?)[a-zA-Z0-9\-\.]+\.([a-zA-Z]{2,7}|[0-9]{1,3})(\]?))$/', $email, $matches)) { 
        $user = $matches[1]; 
        $domain = $matches[2]; 
        # Check availability of  MX/A records 
        if ($domainCheck) { 
            if(function_exists('checkdnsrr')) { 
                # Construct array of available mailservers 
                if(getmxrr($domain, $mxhosts, $mxweight)) { 
                    for($i=0;$i<count($mxhosts);$i++){ 
                        $mxs[$mxhosts[$i]] = $mxweight[$i]; 
                    } 
                    asort($mxs); 
                    $mailers = array_keys($mxs); 
                } elseif(checkdnsrr($domain, 'A')) { 
                    $mailers[0] = gethostbyname($domain); 
                } else { 
                    $mailers=array(); 
                } 
            } else { 
            # DNS functions do not exist - we are probably on Win32. 
            # This means we have to resort to other means, like the Net_DNS PEAR class. 
            # For more info see http://pear.php.net 
            # For this you also need to enable the mhash module (lib_mhash.dll). 
            # Another way of doing this is by using a wrapper for Win32 dns functions like 
            # the one descrieb at http://px.sklar.com/code.html/id=1304 
                require_once 'Net/DNS.php'; 
                $resolver = new Net_DNS_Resolver(); 
                # Workaround for bug in Net_DNS, you have to explicitly tell the name servers 
                # 
                # ***********  CHANGE THIS TO YOUR OWN NAME SERVERS ************** 
                $resolver->nameservers = $dns;

                $mx_response = $resolver->query($domain, 'MX'); 
                $a_response  = $resolver->query($domain, 'A'); 
                if ($mx_response) { 
                    foreach ($mx_response->answer as $rr) { 
                            $mxs[$rr->exchange] = $rr->preference; 
                    } 
                    asort($mxs); 
                    $mailers = array_keys($mxs); 
                } elseif($a_response) { 
                    $mailers[0] = gethostbyname($domain); 
                } else { 
                    $mailers = array(); 
                } 
                 
            } 
             
            $total = count($mailers); 
            # Query each mailserver 
            if($total > 0 && $verify) { 
                # Check if mailers accept mail 
                for($n=0; $n < $total; $n++) { 
                    # Check if socket can be opened 
                    if($debug) { echo "Checking server $mailers[$n]...\n";} 
                    $connect_timeout = $server_timeout; 
                    $errno = 0; 
                    $errstr = 0; 
                    # Try to open up socket 
                    if($sock = @fsockopen($mailers[$n], 25, $errno , $errstr, $connect_timeout)) { 
                        $response = fgets($sock); 
                        if($debug) {echo "Opening up socket to $mailers[$n]... Succes!\n";} 
                        stream_set_timeout($sock, 30); 
                        $meta = stream_get_meta_data($sock); 
                        if($debug) { echo "$mailers[$n] replied: $response\n";} 
                        $cmds = array( 
                            "HELO $helo_address", 
                            "MAIL FROM: <$probe_address>", 
                            "RCPT TO: <$email>", 
                            "QUIT", 
                        ); 
                        # Hard error on connect -> break out 
                        # Error means 'any reply that does not start with 2xx ' 
                        if(!$meta['timed_out'] && !preg_match('/^2\d\d[ -]/', $response)) { 
                            $error = "Error: $mailers[$n] said: $response\n"; 
                            break; 
                        } 
                        foreach($cmds as $cmd) { 
                            $before = microtime(true); 
                            fputs($sock, "$cmd\r\n"); 
                            $response = fgets($sock, 4096); 
                            $t = 1000*(microtime(true)-$before); 
                            if($debug) {echo htmlentities("$cmd\n$response") . "(" . sprintf('%.2f', $t) . " ms)\n";} 
                            if(!$meta['timed_out'] && preg_match('/^5\d\d[ -]/', $response)) { 
                                $error = "Unverified address: $mailers[$n] said: $response"; 
                                break 2; 
                            } 
                        } 
                        fclose($sock); 
                        if($debug) { echo "Succesful communication with $mailers[$n], no hard errors, assuming OK";} 
                        break; 
                    } elseif($n == $total-1) { 
                        $error = "None of the mailservers listed for $domain could be contacted"; 
                    } 
                } 
            } elseif($total <= 0) { 
                $error = "No usable DNS records found for domain '$domain'"; 
            } 
        } 
    } else { 
        $error = 'Address syntax not correct'; 
    } 
    if($debug) { echo "</pre>";} 
     
    if($return_errors) { 
        # Give back details about the error(s). 
        # Return FALSE if there are no errors. 
        if(isset($error)) return $error; else return false; 
    } else { 
        # 'Old' behaviour, simple to understand 
        if(isset($error)) return false; else return true; 
    } 
} 

?>