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
//$myjobid = $jconf['jobid_ldap_cache'];
$myjobid = "job_ldap_cache";

// Log related init
$debug = Springboard\Debug::getInstance();
$debug->log($jconf['log_dir'], $myjobid . ".log", "*************************** Job: LDAP/AD cache started ***************************", $sendmail = false);

// Check operating system - exit if Windows
if ( iswindows() ) {
    $debug->log($jconf['log_dir'], $myjobid . ".log", "ERROR: Non-Windows process started on Windows platform" , $sendmail = false);
    exit;
}

// DB
$db = db_maintain();

// Config
$isexecute = true;
$synctimeseconds = 3600;

// Get LDAP/AD directories for all organizations
$ldap_dirs = getLDAPDirectories();
if ( $ldap_dirs === false ) {
    $debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] Cannot query LDAP/AD servers. Exiting..." . trim($query), $sendmail = false);
    exit;
}

//var_dump($ldap_dirs);

// Get LDAP/AD groups to be synchronized
$ldap_groups = getLDAPGroups($synctimeseconds);
if ( $ldap_groups === false ) {
    $debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] Cannot query LDAP/AD groups. Exiting..." . trim($query), $sendmail = false);
    exit;
}

while ( !$ldap_groups->EOF ) {

    $ldap_group = $ldap_groups->fields;
var_dump($ldap_group);

    $ldap_dir = searchLDAPDirectoriesByID($ldap_dirs, $ldap_group['organizationdirectoryid']);

    // Already connected to this LDAP/AD?
    if ( !isset($ldap_dir['connected']) ) {

        // Connect to AD
        try {
            $ldap_dir['ldap_handler'] = ldap_connect($ldap_dir['server']);
        } catch (exception $err) {
            $debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] Skipping syncing " . $ldap_group['organizationdirectoryldapdn'] . ". Cannot connect to LDAP/AD server: " . $ldap_dir['server'] . "\nERROR:\n\n" . $err, $sendmail = false);
            $ldap_groups->MoveNext();
            continue;
        }
        
        ldap_set_option($ldap_dir['ldap_handler'], LDAP_OPT_REFERRALS, 0);
        ldap_set_option($ldap_dir['ldap_handler'], LDAP_OPT_PROTOCOL_VERSION, 3);

        // Bind LDAP admin user to connection
        try {
            $ldap_bind = ldap_bind($ldap_dir['ldap_handler'], $ldap_dir['user'], $ldap_dir['password']);
        } catch (exception $err) {
            $debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] Skipping syncing " . $ldap_group['organizationdirectoryldapdn'] . ". Cannot authenticate to LDAP/AD server: " . $ldap_dir['user'] . "@" . $ldap_dir['server'] . " (orgdir#" . $ldap_group['organizationdirectoryid'] . ")\n\nERROR:\n\n" . $err, $sendmail = false);
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
var_dump($ldap_dir);

    // Log
    $debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] Synchronizing group: " . $ldap_group['organizationdirectoryldapdn'], $sendmail = false);

    // Request all members of nested group
    $filter = "memberOf:1.2.840.113556.1.4.1941:=" . $ldap_group['organizationdirectoryldapdn'];
	$attr_filter = array("sAMAccountName", "userPrincipalName");
    try {
        $result = ldap_search($ldap_dir['ldap_handler'], $ldap_dir['ldapusertreedn'], $filter, $attr_filter);
    } catch (exception $err) {
		$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] Skipping syncing " . $ldap_group['organizationdirectoryldapdn'] . ". LDAP/AD query failed at server: " . $ldap_dir['user'] . "@" . $ldap_dir['server'] . "\n\nFilter: " . $filter . "\nAttribute filter: " . $attr_filter . "\n\nERROR:\n\n" . $err, $sendmail = false);
        $ldap_groups->MoveNext();
        continue;
    }
    
    // Collect users from LDAP result set
    $i = 0;
    $users = array();
	$ldap_groups = ldap_first_entry($ldap_dir['ldap_handler'], $result);
    do {      

        $ldap_user = ldap_get_attributes($ldap_dir['ldap_handler'], $ldap_groups);
        if ( $ldap_user['sAMAccountName']['count'] > 1 ) $debug->log($jconf['log_dir'], $myjobid . ".log", "[WARN] Multiple sAMAccountName for user.\n\n" . print_r($ldap_user, true), $sendmail = false);
        
        $users[$i]['sAMAccountName'] = strtolower($ldap_user['sAMAccountName'][0]);
        $users[$i]['userPrincipalName'] = strtolower($ldap_user['userPrincipalName'][0]);
        $users[$i]['isnew'] = false;
        
        $i++;
    } while ( $ldap_groups = ldap_next_entry($ldap_dir['ldap_handler'], $ldap_groups) );

    // Log
    $debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] Users in LDAP/AD group: " . count($users), $sendmail = false);
    
    //// Maintain database
    // Get members of this Videosquare group
    $vsq_group_members = getVSQGroupMembers($ldap_group['id']);

    // If groups_members.userexternalid is NULL, then update. Might happen with existing Kerberos originated users logged in before the first LDAP synch.
    foreach ($vsq_group_members as $key => $vsq_user) {
        if ( empty($vsq_user['userexternalid']) and !empty($vsq_user['userPrincipalName']) ) {
            $err = updateGroupsMembersExternalID($ldap_group['id'], $vsq_user['userid'], $vsq_user['userPrincipalName']);
            if ( $err === false ) {
                $debug->log($jconf['log_dir'], $myjobid . ".log", "[WARN] Cannot update groups_members userexternalid for existing user#" . $vsq_user['userid'] . " (" . $vsq_user['userPrincipalName'] . ")", $sendmail = true);
            } else {
                $debug->log($jconf['log_dir'], $myjobid . ".log", "[OK] groups_members.userexternalid updated for existing user#" . $vsq_user['userid'] . " (" . $vsq_user['userPrincipalName'] . ")", $sendmail = false);
                $vsq_group_members[$key]['userexternalid'] = $vsq_user['userPrincipalName'];
                var_dump($vsq_user);
            }
        }
    }

    // Variables to track number of changes
    $num_users_new = 0;
    $num_users_remove = 0;

var_dump($vsq_group_members);

    // Who is new?
echo "NEW users:\n";
    $users2add = array();
    $users2add_sql = array();
    foreach ($users as $key => $user) {
        var_dump($user);
        $res = recursive_array_search($user['userPrincipalName'], $vsq_group_members);
        echo "resid = " . $res . "\n";
        // New user in LDAP/AD
        if ( $res === false ) {
            echo $user['userPrincipalName'] . " is new.\n";

            $userid = "NULL";
            // ... getUserIDByUserExternalID() - nezzuk meg letezik-e mar ez az externalid user es azzal szurjuk be!!!
//            if ( !empty($vsq_group_members[$res]['userid']) ) $userid = $vsq_group_members[$res]['userid'];
exit;

            array_push($users2add, $user['userPrincipalName']);
            array_push($users2add_sql, "(" . $ldap_group['id'] . "," . $userid . ",'" . $user['userPrincipalName'] . "')");

            $users[$key]['isnew'] = true;
            $num_users_new++;
        }
    }
exit;

    // Who is removed?
echo "REMOVED users:\n";
    $users2remove = array();
    $users2remove_sql = array();
    foreach ($vsq_group_members as $key => $vsq_user) {
        $res = recursive_array_search($vsq_user['userexternalid'], $users);
        echo $vsq_user['userexternalid'] . " searchres:\n";
        var_dump($res);
// !!!
/*if ( $vsq_user['userexternalid'] == "akovacs@streamnet.hu" ) {
    var_dump($vsq_user);
    $res = false;
} */
        // Removed user from LDAP/AD
        if ( $res === false ) {
            $vsq_group_members[$key]['isremoved'] = true;
            $num_users_remove++;
            echo $vsq_user['userexternalid'] . " removed.\n";
            array_push($users2remove, $vsq_user['userexternalid']);
            array_push($users2remove_sql, "'" . $vsq_user['userexternalid'] . "'");
        } else {
            echo $vsq_user['userexternalid'] . " found.\n";
            $vsq_group_members[$key]['isremoved'] = false;
        }
    }
    
var_dump($users2remove);
    
    // Remove users from group
    $err = 0;
    $msg = "";
    if ( $isexecute and !empty($users2remove_sql) ) {
        $users2remove_sql_flat = "(" . implode(",", $users2remove_sql) . ")";
        $err = DeleteVSQGroupMembers($ldap_group['id'], $users2remove_sql_flat);
    }        
    if ( $err !== false ) {
        // Log
        if ( !empty($users2remove_sql) ) $msg = ". Users: " . implode(",", $users2remove);
        $debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] Users removed from group: " . $err . $msg, $sendmail = false);
    } else {
        $debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] Users NOT removed from group: " . $num_users_remove . ". Inconsistent group membership with LDAP/AD!", $sendmail = true);
    }

    // Add new users to group
    $err = 0;
    $msg = "";
    if ( $isexecute and !empty($users2add_sql) ) {
        $users2add_sql_flat = implode(",", $users2add_sql);
        $err = AddVSQGroupMembers($users2add_sql_flat);
    }
    if ( $err !== false ) {
        // Log
        if ( !empty($users2add_sql) ) $msg = ". Users: " . implode(",", $users2add);
        $debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] New users added to this group: " . $err . $msg, $sendmail = false);
    } else {
        $debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] Users NOT added to group: " . $num_users_new . ". Inconsistent group membership with LDAP/AD! Users:\n" . $users2add_flat, $sendmail = true);
    }
    
exit;
    $ldap_groups->MoveNext();
}
    
// Close DB connection if open
if ( ( $db !== false ) and is_resource($db->_connectionID) ) $db->close();

exit;

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
//echo $query . "\n";

	try {
		$rs = $db->Execute($query);
	} catch (exception $err) {
		$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] SQL query failed." . trim($query), $sendmail = true);
		return false;
	}

    return $db->Affected_Rows();
}

function AddVSQGroupMembers($users2add) {
global $db, $myjobid, $debug, $jconf;

    if ( empty($users2add) ) return false;

    $query = "
        INSERT INTO
            groups_members (groupid, userid, userexternalid)
        VALUES " . $users2add;

//echo $query . "\n";

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

echo $query . "\n";

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
            gm.userexternalid,
            gm.userid,
            LOWER(u.externalid) AS userPrincipalName
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

function getLDAPGroups($synctimeseconds) {
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
            g.organizationid AND o.id AND
            o.disabled = 0 AND
            od.disabled = 0 AND
            ( g.organizationdirectoryuserslastsynchronized IS NULL OR TIMESTAMPADD(SECOND, " . $synctimeseconds . ", g.organizationdirectoryuserslastsynchronized) < NOW() )
    ";

//echo $query . "\n";

	try {
		$rs = $db->Execute($query);
	} catch (exception $err) {
		$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] SQL query failed." . trim($query), $sendmail = true);
		return false;
	}

    // Check if any record returned
	if ( $rs->RecordCount() < 1 ) return false;
        
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
    
    foreach( $haystack as $key => $value ) {
        $current_key = $key;
        if ( $needle === $value OR ( is_array($value) && recursive_array_search($needle, $value) !== false ) ) {
            return $current_key;
        }
    }

    return false;
}

?>