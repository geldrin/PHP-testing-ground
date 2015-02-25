<?php

//phpinfo();

	// ## LDAP specifics
	// Server
	$AD_server = "ldap://172.18.1.7:389";
    $AD_Auth_User = "vsqad@streamnet.hu";
    $AD_Auth_PWD = "LELgcv2dzH*GFm5h";
	$base_tree = "DC=streamnet,DC=hu";
	// User DN
	$users_tree = "OU=Users,OU=StreamNet HU,DC=streamnet,DC=hu";
//	$users_tree = "OU=Users,OU=TeleConnect,DC=streamnet,DC=hu";
	$groups_tree = "OU=Access Groups,OU=StreamNet HU,DC=streamnet,DC=hu";

/*	$AD_server = "ldap://WIN-OD479QR8J7J.vsqtest.hu:389";
    $AD_Auth_User = "vsqtest@vsqtest.hu";
    $AD_Auth_PWD = "Kakukk%2015";
	$base_tree = "DC=vsqtest,DC=hu";
	// User DN
	$users_tree = "OU=Users,DC=vsqtest,DC=hu";
	$groups_tree = "OU=Access Groups,DC=vsqtest,DC=hu"; */

	echo "<html><head><meta charset=\"UTF-8\"><meta http-equiv=\"cache-control\" content=\"max-age=0\" /><meta http-equiv=\"cache-control\" content=\"no-cache\" /></head><body>\n";
    
	echo "<h1>Single Sign-On Test</h1>\n\n";

    // Is Kerberos (krb5) extension loaded?
	if( !extension_loaded('krb5') ) {
		die('KRB5 Extension not installed');
	}

    // Server variables
    $x = htmlentities(print_r($_SERVER, true));
    echo "<pre>" . $x . "</pre><br>";

	if ( isset($_SERVER['REMOTE_USER']) or isset($_SERVER['REDIRECT_REMOTE_USER']) ) {

        // REMOTE_USER or REDIRECT_REMOTE_USER
        $user = $_SERVER['REMOTE_USER'];
        if ( empty($user) ) {
            $user = $_SERVER['REDIRECT_REMOTE_USER'];
        }
        
        // Check username format
        if ( !preg_match("/^([a-zA-Z0-9])+([a-zA-Z0-9\._-])*@([a-zA-Z0-9_-])+([a-zA-Z0-9\._-]+)+$/", $user) ) {
            echo "<p>Username format is NOT valid!<p>";
            echo "</body></html>";
            exit;
        }
        $tmp = explode("@", $user, 2);
		$username = $tmp[0];
		$domain = $tmp[1];
        
		echo "<p>Welcome! You are logged in as " . $username . " (from " . $domain . " domain).</p>\n\n";

		echo "Kerberos login information:<br>\n";
		echo "Server name: " . $_SERVER['SERVER_NAME'] . ":" . $_SERVER['SERVER_PORT'] . " (" . $_SERVER['SERVER_ADDR'] . ":" . $_SERVER['SERVER_PORT'] . ")<br>\n";
		echo "Client address: " . $_SERVER['REMOTE_ADDR'] . "<br>\n";
		echo "Auth Type: " . $_SERVER['AUTH_TYPE'] . "<br>\n";
		echo "Realm domain: " . $domain . "<br>\n";
		echo "Remote User: " . $user . "<br>\n";
//		echo "PHP Auth USER: " . $_SERVER['PHP_AUTH_USER'] . "<br>\n";
//		echo "KRB5CCNAME: " . $_SERVER['KRB5CCNAME'] . "<br>";

	} else {
		die("<br>Kerberos login failed, no SSO auth is available. User is not authenticated.<br>\n");
		exit;
	}

	echo "<h1>LDAP test</h1>\n";
    	
    
/*
User authhoz:

LDAP Server Settings
- Server URL: ldap://62.112.213.214
- Use TLS connection: y/n
- Bind user: cn=LDAP USER,ou=Service Accounts,ou=StreamNet HU,dc=streamnet,dc=hu (Bind DN or username of the user with read access to the LDAP tree.)
- Password: xyz
- Base context: dc=streamnet,dc=hu (User and group searches begin from the base context.)
User query:

User Query Settings
- User search filter: (objectClass=user) (Optional filter parameters are combined with user search parameters using AND.)
- Username attribute: sAMAccountName
- First name attribute: givenName
- Surname attribute: sn
- Display name attribute: -
- Extension attribute: -
- Group membership attribute: member Of
- Email attribute: mail
Group query:

Group Query Settings
- Group search filter: (objectClass=group) (Optional filter parameters are combined with the group search parameters using AND.)
- Group name attribute: name
- Group member attribute: member
- Query nested groups: y/n. This option uses the LDAP_MATCHING_RULE_IN_CHAIN operator available in Microsoft Server 2003 SP2 and later.
*/

	// Connect to AD
	$ldap_conn = ldap_connect($AD_server) or
		die("ERROR: cannot connect to LDAP server $AD_server\n");

	ldap_set_option($ldap_conn, LDAP_OPT_REFERRALS, 0);
	ldap_set_option($ldap_conn, LDAP_OPT_PROTOCOL_VERSION, 3);

	// Bind LDAP admin user to connection
	$ldap_bind = ldap_bind($ldap_conn, $AD_Auth_User, $AD_Auth_PWD) or
		die ("ERROR: cannot bind to LDAP admin " . $AD_Auth_User . "<br>\n");

	echo "LDAP connection successful<br><br>\n";

	// User query
	echo "<h1>User query example</h1>\n\n";
    $filter = "(&(objectClass=user)(objectCategory=person)(userPrincipalName=" . $user . "))";    
	$attr_filter = array("objectguid", "cn", "sn", "givenName", "ou", "distinguishedName", "memberOf", "sAMAccountName", "mail", "thumbnailPhoto", "userPrincipalName");
	$result = ldap_search($ldap_conn, $base_tree, $filter, $attr_filter) or
		die("ERROR: ldap_search() failed<br>\n");

    $ldap_user_entry = ldap_first_entry($ldap_conn, $result);

    if ( !empty($ldap_user_entry) ) {
        
        // Get all atributes as array
        $user_attribs = ldap_get_attributes($ldap_conn, $ldap_user_entry);
        
        // Get ObjectGUID
        $objectguid = $user_attribs['objectGUID'][0];
        echo "ObjectGUID: " . bin2hex($objectguid) . "<br>";
        
        // Show thumbnail photo
        if ( $user_attribs['thumbnailPhoto']['count'] > 0 ) {
            $imageString = $user_attribs['thumbnailPhoto'][0];
            $tempFile = tempnam(sys_get_temp_dir(), 'image');
            file_put_contents($tempFile, $imageString);
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime  = explode(';', $finfo->file($tempFile));
            echo '<img src="data:' . $mime[0] . ';base64,' . base64_encode($imageString) . '"/><br>';
            
            // null thumb, do not print
            $user_attribs['thumbnailPhoto'][0] = "";
        }

        // memberOf
        if ( $user_attribs['memberOf']['count'] > 0 ) {
        
            for ( $i = 0; $i < $user_attribs['memberOf']['count']; $i++) {
                $group_cn = $user_attribs['memberOf'][$i];
                echo "dn: " . $group_cn . "<br>";
            }
        
        }

        // Dump entry
        echo "User entry:<br><pre>" . print_r($user_attribs, true) . "</pre><br>";
    } else {
        echo "ERROR: user entry not found?\n";
        exit;
    }
        
	// Group query (all groups)
	echo "<h1>Group query example (list all groups)</h1>\n\n";
	$filter = "(objectClass=group)";
	$attr_filter = array("cn", "objectguid", "member", "distinguishedname", "whencreated", "whenchanged", "memberof");
    $result = ldap_search($ldap_conn, $groups_tree, $filter, $attr_filter) or
		die("ERROR: ldap_search() failed<br>\n");
        
	$ldap_groups = ldap_first_entry($ldap_conn, $result);

    $i = 0;
    do {
        
        // ObjectGUID
        $objectguid_array = ldap_get_values_len($ldap_conn, $ldap_groups, 'objectguid');     
        $objectguid = bin2hex($objectguid_array[0]);
        echo "ObjectGUID: " . $objectguid . "<br>";

        // Dump entry
        $group = ldap_get_attributes($ldap_conn, $ldap_groups);
        echo "entries:<br><pre>" . print_r($group, true) . "</pre><br>";

        // When created
        $whenCreated = preg_replace("/(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2}).+/","$1-$2-$3 $4:$5:$6", $group['whenCreated'][0]);
        $whenCreated_ts = strtotime($whenCreated);
        
        // When changed
        $whenChanged = preg_replace("/(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2}).+/","$1-$2-$3 $4:$5:$6", $group['whenChanged'][0]);
        $whenChanged_ts = strtotime($whenChanged);
           
        echo "whenCreated: " . $whenCreated . "<br>";
        echo "whenChanged: " . $whenChanged . "<br>";
        
        if ( $i > 2 ) break;
        $i++;
    } while ( $ldap_groups = ldap_next_entry($ldap_conn, $ldap_groups) );    

    // Search groups based on objectGUID
    echo "<h1>Group search example</h1>";
    $objectguid_escaped = FormatGUID($objectguid);
    echo "<p>ObjectGUID: " . $objectguid . "</p>";
    echo "<p>ObjectGUID escaped: " . $objectguid_escaped . "</p>";
    $filter = "(objectGUID=" . $objectguid_escaped . ")";
	$attr_filter = array("cn", "objectguid", "member", "distinguishedname", "whencreated", "whenchanged", "memberof");
    $result = ldap_search($ldap_conn, $groups_tree, $filter, $attr_filter) or
		die("ERROR: ldap_search() failed<br>\n");

    $ldap_group_entry = ldap_first_entry($ldap_conn, $result);

    if ( !empty($ldap_group_entry) ) {
    
        // Get all attributes as array
        $group_attribs = ldap_get_attributes($ldap_conn, $ldap_group_entry);

        // Dump entry
        echo "Group entry:<br><pre>" . print_r($group_attribs, true) . "</pre><br>";
        
    } else {
        echo "ERROR: group entry not found\n";
        exit;    
    }

    // Search group based on DN
// ???

    // List all OUs and groups belonging to them
/*    echo "<h1>OU list</h1>";
    $filter = "(OU=*)";
	$attr_filter = array();
    $result = ldap_search($ldap_conn, $base_tree, $filter, $attr_filter) or
		die("ERROR: ldap_search() failed<br>\n");

    $info = ldap_get_entries($ldap_conn, $result);

    echo "entries:<br><pre>" . print_r($info, true) . "</pre><br>";
*/

    // Find group based on CN
    echo "<h1>Group get based on CN</h1>";
    $group_cn = "CN=SNIT,OU=Access Groups,OU=StreamNet HU,DC=streamnet,DC=hu";
    echo "<p>Group CN to look for: " . $group_cn . "</p>";
    $filter = "(objectClass=group)";
	$attr_filter = array("description", "member", "distinguishedname", "whenchanged", "memberof", "objectguid", "dn");

    $result = ldap_search($ldap_conn, $group_cn, $filter, $attr_filter) or
		die("ERROR: ldap_search() failed<br>\n");

    $info = ldap_get_entries($ldap_conn, $result);

    echo "entries:<br><pre>" . print_r($info, true) . "</pre><br>";
  
    
    // End of HTML
    echo "</body></html>";
    
    exit;
    
    function FormatGUID($hexGUID) {

        $hexGUID = str_replace("-", "", $hexGUID);
        
        for ($i = 0; $i <= strlen($hexGUID)-2; $i = $i+2){
            $output .=  "\\".substr($hexGUID, $i, 2);
        }
 
        return $output;
    }
     

// NESTED stuff
/**
 * Recursively search $userDN groups to find if $userDN $groupToFind is in that  
 * group
 * This can be used for verifying if a user have an indirect (nested) member
 * ship in a group.
 * 
 * @param resource $ldapConnection
 * @param string $userDN - e.g: CN=User Name,OU=Users,DC=domain,DC=com
 * @param string $haystackDN - e.g: CN=Finance,OU=Groups,DC=domain,DC=com
 * @return boolean - true if  $userDN is member of $groupToFind 
 */
 /*
public function inGroup($ldapConnection, $userDN, $groupToFind) {
    $filter = "(memberof:1.2.840.113556.1.4.1941:=".$groupToFind.")";
    $search = ldap_search($ldapConnection, $userDN, $filter, array("dn"), 1);
    $items = ldap_get_entries($ldapConnection, $search);
    if(!isset($items["count"])) {
        return false;
    }
    return (bool)$items["count"];
}
*/
//The filter string to get all groups a user is in, including those reachable via nesting of those groups is:
//(&(objectClass=group)(member:1.2.840.113556.1.4. 19 41:={0})) where the {0} value is the DN of the particular user.

// ---------------------------- Ojjektum orientaltan

/*

namespace ldap;

abstract class AuthStatus
{
    const FAIL = "Authentication failed";
    const OK = "Authentication OK";
    const SERVER_FAIL = "Unable to connect to LDAP server";
    const ANONYMOUS = "Anonymous log on";
}

// The LDAP server
class LDAP
{
    private $server = "127.0.0.1";
    private $domain = "localhost";
    private $admin = "admin";
    private $password = "";

    public function __construct($server, $domain, $admin = "", $password = "")
    {
        $this->server = $server;
        $this->domain = $domain;
        $this->admin = $admin;
        $this->password = $password;
    }

    // Authenticate the against server the domain\username and password combination.
    public function authenticate($user)
    {
        $user->auth_status = AuthStatus::FAIL;

        $ldap = ldap_connect($this->server) or $user->auth_status = AuthStatus::SERVER_FAIL;
        ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);
        ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
        $ldapbind = ldap_bind($ldap, $user->username."@".$this->domain, $user->password);

        if($ldapbind)
        {
            if(empty($user->password))
            {
                $user->auth_status = AuthStatus::ANONYMOUS;
            }
            else
            {
                $result = $user->auth_status = AuthStatus::OK;

                $this->_get_user_info($ldap, $user);
            }
        }
        else
        {
            $result = $user->auth_status = AuthStatus::FAIL;
        }

        ldap_close($ldap);
    }

    // Get an array of users or return false on error
    public function get_users()
    {       
        if(!($ldap = ldap_connect($this->server))) return false;

        ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);
        ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
        $ldapbind = ldap_bind($ldap, $this->admin."@".$this->domain, $this->password);

        $dc = explode(".", $this->domain);
        $base_dn = "";
        foreach($dc as $_dc) $base_dn .= "dc=".$_dc.",";
        $base_dn = substr($base_dn, 0, -1);
        $sr=ldap_search($ldap, $base_dn, "(&(objectClass=user)(objectCategory=person)(|(mail=*)(telephonenumber=*))(!(userAccountControl:1.2.840.113556.1.4.803:=2)))", array("cn", "dn", "memberof", "mail", "telephonenumber", "othertelephone", "mobile", "ipphone", "department", "title"));
        $info = ldap_get_entries($ldap, $sr);

        for($i = 0; $i < $info["count"]; $i++)
        {
            $users[$i]["name"] = $info[$i]["cn"][0];
            $users[$i]["mail"] = $info[$i]["mail"][0];
            $users[$i]["mobile"] = $info[$i]["mobile"][0];
            $users[$i]["skype"] = $info[$i]["ipphone"][0];
            $users[$i]["telephone"] = $info[$i]["telephonenumber"][0];
            $users[$i]["department"] = $info[$i]["department"][0];
            $users[$i]["title"] = $info[$i]["title"][0];

            for($t = 0; $t < $info[$i]["othertelephone"]["count"]; $t++)
                $users[$i]["othertelephone"][$t] = $info[$i]["othertelephone"][$t];

            // set to empty array
            if(!is_array($users[$i]["othertelephone"])) $users[$i]["othertelephone"] = Array();
        }

        return $users;
    }

    private function _get_user_info($ldap, $user)
    {
        $dc = explode(".", $this->domain);

        $base_dn = "";
        foreach($dc as $_dc) $base_dn .= "dc=".$_dc.",";

        $base_dn = substr($base_dn, 0, -1);

        $sr=ldap_search($ldap, $base_dn, "(&(objectClass=user)(objectCategory=person)(samaccountname=".$user->username."))", array("cn", "dn", "memberof", "mail", "telephonenumber", "othertelephone", "mobile", "ipphone", "department", "title"));
        $info = ldap_get_entries($ldap, $sr);

        $user->groups = Array();
        for($i = 0; $i < $info[0]["memberof"]["count"]; $i++)
            array_push($user->groups, $info[0]["memberof"][$i]);

        $user->name = $info[0]["cn"][0];
        $user->dn = $info[0]["dn"];
        $user->mail = $info[0]["mail"][0];
        $user->telephone = $info[0]["telephonenumber"][0];
        $user->mobile = $info[0]["mobile"][0];
        $user->skype = $info[0]["ipphone"][0];
        $user->department = $info[0]["department"][0];
        $user->title = $info[0]["title"][0];

        for($t = 0; $t < $info[$i]["othertelephone"]["count"]; $t++)
                $user->other_telephone[$t] = $info[$i]["othertelephone"][$t];

        if(!is_array($user->other_telephone[$t])) $user->other_telephone[$t] = Array();
    }
}

class User
{
    var $auth_status = AuthStatus::FAIL;
    var $username = "Anonymous";
    var $password = "";

    var $groups = Array();
    var $dn = "";
    var $name = "";
    var $mail = "";
    var $telephone = "";
    var $other_telephone = Array();
    var $mobile = "";
    var $skype = "";
    var $department = "";
    var $title = "";

    public function __construct($username, $password)
    {       
        $this->auth_status = AuthStatus::FAIL;
        $this->username = $username;
        $this->password = $password;
    }

    public function get_auth_status()
    {
        return $this->auth_status;
    }
 }

$ldap = new ldap\LDAP("192.168.1.123", "company.com", "admin", "mypassword");
$users = $ldap->get_users();

*/

?>

