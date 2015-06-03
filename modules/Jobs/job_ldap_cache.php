<?php
// Job: LDAP/AD cache

define('BASE_PATH', realpath( __DIR__ . '/../..' ) . '/' );
define('PRODUCTION', false );
define('DEBUG', false );

include_once( BASE_PATH . 'libraries/Springboard/Application/Cli.php');

// Utils
include_once('job_utils_base.php');
include_once('job_utils_log.php');

set_time_limit(0);

// Init
$app = new Springboard\Application\Cli(BASE_PATH, PRODUCTION);

// Load jobs configuration file
$app->loadConfig('modules/Jobs/config_jobs.php');
$jconf = $app->config['config_jobs'];
$myjobid = $jconf['jobid_ldap_cache'];

// Log related init
$debug = Springboard\Debug::getInstance();
//$debug->log($jconf['log_dir'], $myjobid . ".log", "*************************** Job: LDAP/AD cache started ***************************", $sendmail = false);

// Check operating system - exit if Windows
if ( iswindows() ) {
    $debug->log($jconf['log_dir'], $myjobid . ".log", "ERROR: Non-Windows process started on Windows platform" , $sendmail = false);
    exit;
}

// DB
$db = db_maintain();

// Config
$isexecute = true;
$isdebug_ldap = false;
$isdebug_user = false;

// Should we delete files or just testing?
if ( !$isexecute ) {
    $debug->log($jconf['log_dir'], $myjobid . ".log", "[WARNING] THIS IS A TEST RUN. NO DB RECORDS WILL BE UPDATED/REMOVED!", $sendmail = false);
}

// Get LDAP/AD directories for all organizations
$ldap_dirs = getLDAPDirectories();
if ( $ldap_dirs === false ) {
    $debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] Cannot query LDAP/AD servers. Exiting...", $sendmail = false);
    exit;
}

// Debug
if ( $isdebug_ldap ) {
    echo "*** LDAP directories:\n";
    var_dump($ldap_dirs);
    echo "---------------------\n";
}

// Basic DB LDAP/AD user maintenance: search for unconnected users in groups_members
$err = updateUnconnectedGroupMembers();

// Get LDAP/AD groups to be synchronized
$ldap_groups = getLDAPGroups($app->config['directorygroupnestedcachetimeout']);
if ( $ldap_groups === false ) {
    $debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] Cannot query LDAP/AD groups. Exiting...", $sendmail = false);
    exit;
}
// Nothing to update, exit
if ( $ldap_groups->RecordCount() < 1 ) exit;

while ( !$ldap_groups->EOF ) {

    $ldap_group = $ldap_groups->fields;
    
    // Start timer
    $time_start = time();
    
    // Debug
    if ( $isdebug_ldap ) {
        echo "*** LDAP group processing started:\n";
        var_dump($ldap_group);
        echo "----------------------------------\n";
    }

    $ldap_dir = searchLDAPDirectoriesByID($ldap_dirs, $ldap_group['organizationdirectoryid']);

    // Already connected to this LDAP/AD?
    if ( !isset($ldap_dir['connected']) ) {

        // Connect to LDAP/AD
        try {
            $ldap_dir['ldap_handler'] = @ldap_connect($ldap_dir['server']);
        } catch (exception $err) {
            echo "exp\n";
            $debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] Skipping syncing " . $ldap_group['organizationdirectoryldapdn'] . ". Cannot connect to LDAP/AD server: " . $ldap_dir['server'] . "\nERROR:\n\n" . $err, $sendmail = true);
            $ldap_dir['connected'] = false;
            $ldap_groups->MoveNext();
            continue;
        }
        // Handling errors
        if ( $ldap_dir['ldap_handler'] === false ) {
            $debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] Cannot connect to LDAP/AD server " . $ldap_dir['user'] . " at " . $ldap_dir['server'] . " (orgdir#" . $ldap_group['organizationdirectoryid'] . "). Cannot sync group " . $ldap_group['organizationdirectoryldapdn'] . ".", $sendmail = true);
            $ldap_dir['connected'] = false;
            $ldap_groups->MoveNext();
            continue;            
        }
        
        ldap_set_option($ldap_dir['ldap_handler'], LDAP_OPT_REFERRALS, 0);
        ldap_set_option($ldap_dir['ldap_handler'], LDAP_OPT_PROTOCOL_VERSION, 3);

        // Bind LDAP admin user to connection
        try {
            $ldap_bind = @ldap_bind($ldap_dir['ldap_handler'], $ldap_dir['user'], $ldap_dir['password']);
        } catch (exception $err) {
            $ldap_errno = ldap_errno($ldap_dir['ldap_handler']);
            $ldap_errmsg = ldap_err2str($ldap_errno);
            $debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] Cannot bind to LDAP/AD server " . $ldap_dir['user'] . " at " . $ldap_dir['server'] . " (orgdir#" . $ldap_group['organizationdirectoryid'] . "). [err code #" . $ldap_errno . " - " . $ldap_errmsg . "]. Cannot sync group " . $ldap_group['organizationdirectoryldapdn'] . ".", $sendmail = true);
            $ldap_dir['connected'] = false;
            $ldap_groups->MoveNext();
            continue;
        }
        // Handling errors
        if ( $ldap_bind === false ) {
            $ldap_errno = ldap_errno($ldap_dir['ldap_handler']);
            $ldap_errmsg = ldap_err2str($ldap_errno);
            $debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] Cannot bind to LDAP/AD server " . $ldap_dir['user'] . " at " . $ldap_dir['server'] . " (orgdir#" . $ldap_group['organizationdirectoryid'] . "). [err code #" . $ldap_errno . " - " . $ldap_errmsg . "]. Cannot sync group " . $ldap_group['organizationdirectoryldapdn'] . ".", $sendmail = true);
            $ldap_dir['connected'] = false;
            $ldap_groups->MoveNext();
            continue;            
        }

        $ldap_dir['connected'] = true;
        $debug->log($jconf['log_dir'], $myjobid . ".log", "[OK] Connected to LDAP/AD server: " . $ldap_dir['server'] . " (orgdir#" . $ldap_group['organizationdirectoryid'] . ")", $sendmail = false);

    } else {
        
        if ( $ldap_dir['connected'] === false ) {
            $debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] Skipping syncing " . $ldap_group['organizationdirectoryldapdn'] . ". Cannot connect previously to LDAP/AD server: " . $ldap_dir['server'] . ". Please check!" . $err, $sendmail = false); 
        }
        
        $ldap_groups->MoveNext();
        continue;
    }

    // Log
    $debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] Synchronizing group: " . $ldap_group['organizationdirectoryldapdn'], $sendmail = false);

    // Request all members of nested group
    $filter = "(&(memberOf:1.2.840.113556.1.4.1941:=" . $ldap_group['organizationdirectoryldapdn'] . ")(objectClass=person)(objectClass=user))";
	$attr_filter = array("sAMAccountName", "userPrincipalName");
    // Debug
    if ( $isdebug_user ) echo "*** LDAP query filter: " . $filter . "\nAttributes: " . print_r($attr_filter, true) . "\n";
    try {
        $result = ldap_search($ldap_dir['ldap_handler'], $ldap_dir['ldapusertreedn'], $filter, $attr_filter);
    } catch (exception $err) {
        $ldap_errno = ldap_errno($ldap_dir['ldap_handler']);
        $ldap_errmsg = ldap_err2str($ldap_errno);
        $debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] Not syncing " . $ldap_group['organizationdirectoryldapdn'] . ". LDAP/AD query failed at server: " . $ldap_dir['user'] . " at " . $ldap_dir['server'] . " [err code #" . $ldap_errno . " - " . $ldap_errmsg . "]. \n\nFilter: " . $filter . "\nAttribute filter: " . $attr_filter . "\n\nERROR: " . $err, $sendmail = true);
        $ldap_groups->MoveNext();
        continue;
    }
    // Handling errors
    if ( $result === false ) {
        $ldap_errno = ldap_errno($ldap_dir['ldap_handler']);
        $ldap_errmsg = ldap_err2str($ldap_errno);
        $debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] Not syncing " . $ldap_group['organizationdirectoryldapdn'] . ". LDAP/AD query failed at server: " . $ldap_dir['user'] . " at " . $ldap_dir['server'] . " [err code #" . $ldap_errno . " - " . $ldap_errmsg . "]. \n\nFilter: " . $filter . "\nAttribute filter: " . $attr_filter, $sendmail = true);
        $ldap_groups->MoveNext();
        continue;            
    }
    
    // Collect users from LDAP result set
    $i = 0;
    $ldap_users = array();
	$ldap_group_users = ldap_first_entry($ldap_dir['ldap_handler'], $result);
    do {      

        // Get user record
        $ldap_user = ldap_get_attributes($ldap_dir['ldap_handler'], $ldap_group_users);
        if ( $ldap_user['sAMAccountName']['count'] > 1 ) $debug->log($jconf['log_dir'], $myjobid . ".log", "[WARN] Multiple sAMAccountName for user.\n\n" . print_r($ldap_user, true), $sendmail = false);
        
        // sAMAccountName
        $ldap_users[$i]['sAMAccountName'] = strtolower($ldap_user['sAMAccountName'][0]);
        // userPrincipalName
        $ldap_users[$i]['userPrincipalName'] = strtolower($ldap_user['userPrincipalName'][0]);
        
        // userPrincipalName - not necessary in user@domain.com form!
        // Normalize userPrincipalName (can be different with service accounts)
/*        $tmp = explode("@", strtolower($ldap_user['userPrincipalName'][0]), 2);
        if ( !empty($tmp[1]) ) {
    	    $ldap_users[$i]['userPrincipalName'] = $ldap_users[$i]['sAMAccountName'] . "@" . $tmp[1];
        }
*/
        $ldap_users[$i]['isnew'] = false;
        
        $i++;
    } while ( $ldap_group_users = ldap_next_entry($ldap_dir['ldap_handler'], $ldap_group_users) );

    // Get members of this Videosquare group
    $vsq_group_members = getVSQGroupMembers($ldap_group['id']);
    
    // Log
    $debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] Users in LDAP/AD vs. VSQ group: " . count($ldap_users) . " / " . count($vsq_group_members), $sendmail = false);
        
    // Debug
    if ( $isdebug_user ) {
        echo "*** VSQ group members:\n";
        var_dump($vsq_group_members);
        echo "----------------------\n";
    }
    if ( $isdebug_user ) {
        echo "*** LDAP/AD group members:\n";
        var_dump($ldap_users);
        echo "----------------------\n";
    }

    // Variables to track number of changes
    $num_users_new = 0;
    $num_users_remove = 0;

    // Who has been added to LDAP/AD group?
    if ( $isdebug_user ) echo "*** Search VSQ group member for LDAP users: NEW users\n";
    $users2add = array();
    $users2add_sql = array();
    foreach ($ldap_users as $key => $user) {
        $result = recursive_array_search($user['sAMAccountName'], $vsq_group_members);
        if ( $isdebug_user ) echo "Searched: " . $user['sAMAccountName'] . " - result VSQ index = " . $result . "\n";
        // Record new user from LDAP/AD to be added
        if ( $result === false ) {
            if ( $isdebug_user ) echo $user['sAMAccountName'] . " is new.\n";

            array_push($users2add, $user['sAMAccountName']);
            // gm.userid = NULL (we do not know userid) - it will be updated by updateUnconnectedGroupMembers()
            array_push($users2add_sql, "(" . $ldap_group['id'] . ",NULL,'" . $user['sAMAccountName'] . "')");

            $ldap_users[$key]['isnew'] = true;
            $num_users_new++;
        }
        
        if ( $isdebug_user ) var_dump($ldap_users[$key]);
    }
    
    // Who has been removed from LDAP/AD group?
    if ( $isdebug_user ) echo "*** Search LDAP group members for VSQ group members: REMOVE users\n";
    $users2remove = array();
    $users2remove_sql = array();
    if ( is_array($vsq_group_members) ) {
        foreach ($vsq_group_members as $key => $vsq_user) {
            $result = recursive_array_search($vsq_user['member_externalid'], $ldap_users);
            if ( $isdebug_user ) echo "Searched: " . $vsq_user['member_externalid'] . " - result LDAP user index = " . $result . "\n";
    // !!!
    /*if ( $vsq_user['member_externalid'] == "akovacs@streamnet.hu" ) {
        var_dump($vsq_user);
        $result = false;
    } */
            // Record user to be removed from LDAP/AD
            if ( $result === false ) {
                $vsq_group_members[$key]['isremoved'] = true;
                $num_users_remove++;
                if ( $isdebug_user ) echo $vsq_user['member_externalid'] . " removed.\n";
                array_push($users2remove, $vsq_user['member_externalid']);
                array_push($users2remove_sql, "'" . $vsq_user['member_externalid'] . "'");
            } else {
                if ( $isdebug_user ) echo $vsq_user['member_externalid'] . " found.\n";
                $vsq_group_members[$key]['isremoved'] = false;
            }
        }
    }
    
    // Debug
    if ( $isdebug_user ) {
        echo "*** Users2add SQL: ***\n";
        var_dump($users2add_sql);
        echo "*** Users2remove SQL: ***\n";
        var_dump($users2remove_sql);
    }

    // Remove users from group
    $msg = "";
    $users_removed = 0;
    $users_removed_list = "";
    if ( $isexecute and !empty($users2remove_sql) ) {
        $users2remove_sql_flat = "(" . implode(",", $users2remove_sql) . ")";
        $users_removed = DeleteVSQGroupMembers($ldap_group['id'], $users2remove_sql_flat);
        if ( $users_removed !== false ) {
            // Log
            if ( !empty($users2remove_sql) ) $users_removed_list = "\nRemoved: " . implode(",", $users2remove);
        } else {
            $users_removed = 0;
            $debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] Users NOT removed from group: " . $num_users_remove . ". Inconsistent group membership with LDAP/AD!", $sendmail = true);
        }
    }

    // Add new users to group
    $msg = "";
    $users_added = 0;
    $users_added_list = "";
    if ( $isexecute and !empty($users2add_sql) ) {
        $users2add_sql_flat = implode(",", $users2add_sql);
        $users_added = AddVSQGroupMembers($users2add_sql_flat);
        if ( $users_added !== false ) {
            // Log
            if ( !empty($users2add_sql) ) $users_added_list = "\nAdded: " . implode(",", $users2add);
            $err = updateUnconnectedGroupMembers();
        } else {
            $users_added = 0;
            $debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] Users NOT added to group: " . $num_users_new . ". Inconsistent group membership with LDAP/AD! Users:\n" . $users2add_flat, $sendmail = true);
        }
    }

    // Log number of added / removed users
    $debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] Users added / removed: " . $users_added . " / " . $users_removed . $users_added_list . $users_removed_list, $sendmail = false);

    // Update group last sync time
    if ( $isexecute ) {
	$values = array(
	    'organizationdirectoryuserslastsynchronized' => date("Y-m-d H:i:s")
	);
	$groupObj = $app->bootstrap->getModel('groups');
	$groupObj->select($ldap_group['id']);
	$groupObj->updateRow($values);
    }

    // Duration
    $debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] Group sync finished in " . secs2hms(time() - $time_start), $sendmail = false);

    $ldap_groups->MoveNext();
}
    
// Close DB connection if open
if ( ( $db !== false ) and is_resource($db->_connectionID) ) $db->close();

exit;

function updateUnconnectedGroupMembers() {
global $db, $myjobid, $debug, $jconf;
 
    // Update undefined (NULL) gm.userid from users table based on externalid (users that logged in using Kerberos and cached through LDAP/AD, but not yet connected)
    $query = "
        UPDATE
            groups_members AS gm,
            users AS u
        SET
            gm.userid = u.id
        WHERE
            gm.userid IS NULL AND
            gm.userexternalid IS NOT NULL AND
            LOWER(u.externalid) REGEXP CONCAT('^', gm.userexternalid, '@.*')
        ";

    //echo $query . "\n";
            
    try {
        $rs = $db->Execute($query);
    } catch (exception $err) {
        $debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] SQL query failed." . trim($query), $sendmail = true);
        return false;
    }

    // Log
    if ( ( $db->Affected_Rows() ) > 0 ) $debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] Unconnected group members updated (gm.userid = NULL): " . $db->Affected_Rows(), $sendmail = false);

    // Update undefined (NULL) gm.userexternalid from users table based on userid (users that logged in using Kerberos, but not yet cached from LDAP/AD)
    $query = "  
        UPDATE
            groups_members AS gm,
            users AS u
        SET
            gm.userexternalid = SUBSTRING_INDEX(LOWER(u.externalid), '@', 1)
        WHERE
            gm.userexternalid IS NULL AND
            gm.userid = u.id";

    try {
        $rs = $db->Execute($query);
    } catch (exception $err) {
        $debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] SQL query failed." . trim($query), $sendmail = true);
        return false;
    }

    // Log
    if ( ( $db->Affected_Rows() ) > 0 ) $debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] Unconnected group members updated (gm.userexternalid = NULL): " . $db->Affected_Rows(), $sendmail = false);
 
    return true;
}

/*
function updateGroupsMembersExternalID($groupid, $userid, $userprincipalname) {
global $db, $myjobid, $debug, $jconf;

    if ( empty($userprincipalname) ) return false;
    
    $query = "
        UPDATE
            groups_members AS gm
        SET
            gm.userexternalid = '" . $userprincipalname . "'
        WHERE
            gm.userid = " . $userid . " AND
            gm.groupid = " . $groupid;

	try {
		$rs = $db->Execute($query);
	} catch (exception $err) {
		$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] SQL query failed." . trim($query), $sendmail = true);
		return false;
	}

    return $db->Affected_Rows();
} */

function AddVSQGroupMembers($users2add) {
global $db, $myjobid, $debug, $jconf;

    if ( empty($users2add) ) return false;

    $query = "
        INSERT INTO
            groups_members (groupid, userid, userexternalid)
        VALUES " . $users2add;

	try {
		$rs = $db->Execute($query);
	} catch (exception $err) {
		$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] SQL query failed." . trim($query), $sendmail = true);
		return false;
	}

    return $db->Affected_Rows();
}

function DeleteVSQGroupMembers($groupid, $users2remove) {
global $db, $myjobid, $debug, $jconf;

    if ( empty($users2remove) ) return false;

    $query = "
		DELETE FROM
            groups_members
		WHERE
			groupid = " . $groupid . " AND
            userexternalid IN " . $users2remove;

	try {
		$rs = $db->Execute($query);
	} catch (exception $err) {
		$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] SQL query failed." . trim($query), $sendmail = true);
		return false;
	}
    
    return $db->Affected_Rows();
}

function getVSQGroupMembers($groupid) {
global $db, $myjobid, $debug, $jconf;

	$query = "
        SELECT
           	g.id,
            gm.userexternalid AS member_externalid,
            gm.userid,
            LOWER(u.externalid) AS user_externalid
		FROM
			groups AS g,
            groups_members AS gm
        LEFT JOIN
            users AS u
        ON
            gm.userid = u.id
		WHERE
            gm.groupid = " . $groupid . " AND
            gm.groupid = g.id
    ";

    try {
		$rs = $db->getArray($query);
	} catch (exception $err) {
		$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] SQL query failed." . trim($query), $sendmail = true);
		return false;
	}

    // Check if any record returned
	if ( count($rs) < 1 ) return false;

    return $rs;
}

function getLDAPGroups($synctimemin) {
global $db, $myjobid, $debug, $jconf;

	$query = "
		SELECT
           	g.id,
            g.name,
            g.source,
            o.id AS organizationid,
            g.organizationdirectoryid,
            g.organizationdirectoryldapdn,
            g.organizationdirectoryuserslastsynchronized,
            od.id AS organizationdirectoryid
		FROM
			groups AS g,
            organizations_directories AS od,
            organizations AS o
		WHERE
            g.source = 'directory' AND
            g.organizationdirectoryid = od.id AND
            g.organizationid = o.id AND
            o.disabled = 0 AND
            od.disabled = 0 AND
            ( g.organizationdirectoryuserslastsynchronized IS NULL OR TIMESTAMPADD(MINUTE, " . $synctimemin . ", g.organizationdirectoryuserslastsynchronized) < NOW() )
    ";

	try {
		$rs = $db->Execute($query);
	} catch (exception $err) {
		$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] SQL query failed." . trim($query), $sendmail = true);
		return false;
	}
        
    return $rs;
}

function searchLDAPDirectoriesByID($ldap_dirs, $id) {

    foreach ($ldap_dirs as $key => $ldap_dir) {
        if ( $ldap_dir['id'] == $id ) return $ldap_dir;
    }
    
    return false;
}

function getLDAPDirectories() {
global $db, $myjobid, $debug, $jconf;

	$query = "
		SELECT
        	od.id,
            o.name AS organizationname,
            od.organizationid,
            od.type,
            od.server,
            od.user,
            od.password,
            od.domains,
            od.name,
            od.ldapusertreedn,
            od.ldapgroupaccess,
            od.ldapgroupadmin
		FROM
			organizations_directories AS od,
            organizations AS o
		WHERE
			od.type = 'ldap' AND
            od.disabled = 0 AND
            od.organizationid = o.id AND
            o.disabled = 0
    ";
    
	try {
		$rs = $db->getArray($query);
	} catch (exception $err) {
		$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] SQL query failed." . trim($query), $sendmail = true);
		return false;
	}

    // Check if any record returned
    if ( count($rs) < 1 ) return false;
        
    return $rs;
}

function recursive_array_search($needle, $haystack) {
    
    if ( !is_array($haystack) ) return false;
    
    foreach( $haystack as $key => $value ) {
        $current_key = $key;
        if ( $needle === $value OR ( is_array($value) && recursive_array_search($needle, $value) !== false ) ) {
            return $current_key;
        }
    }

    return false;
}

?>
