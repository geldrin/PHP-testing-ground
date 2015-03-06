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
$debug->log($jconf['log_dir'], $jconf['jobid_system_health'] . ".log", "*************************** Job: LDAP/AD cache started ***************************", $sendmail = false);

// Check operating system - exit if Windows
if ( iswindows() ) {
    $debug->log($jconf['log_dir'], $myjobid . ".log", "ERROR: Non-Windows process started on Windows platform" , $sendmail = false);
    exit;
}

// DB
$db = db_maintain();

// Config
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
            $debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] Skipping syncing " . $ldap_group['organizationdirectoryldapdn'] . ". Cannot authenticate to LDAP/AD server: " . $ldap_dir['user'] . "@" . $ldap_dir['server'] . "\n\nERROR:\n\n" . $err, $sendmail = false);
            $ldap_groups->MoveNext();
            continue;
        }
        
        $ldap_dir['connected'] = true;
        
    } else {
        
        if ( $ldap_dir['connected'] === false ) {
            $debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] Skipping syncing " . $ldap_group['organizationdirectoryldapdn'] . ". Cannot connect previously to LDAP/AD server: " . $ldap_dir['server'] . ". Please check!" . $err, $sendmail = false); 
        }
        
        $ldap_groups->MoveNext();
        continue;
    }
var_dump($ldap_dir);

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
    $users = array();
	$ldap_groups = ldap_first_entry($ldap_dir['ldap_handler'], $result);
    do {      

        $ldap_user = ldap_get_attributes($ldap_dir['ldap_handler'], $ldap_groups);
        if ( $ldap_user['sAMAccountName']['count'] > 1 ) $debug->log($jconf['log_dir'], $myjobid . ".log", "[WARN] Multiple sAMAccountName for user.\n\n" . print_r($ldap_user, true), $sendmail = false);
        
        array_push($users, array($ldap_user['sAMAccountName'][0], $ldap_user['userPrincipalName'][0]));

    } while ( $ldap_groups = ldap_next_entry($ldap_dir['ldap_handler'], $ldap_groups) );

    // Maintain database
    $vsq_group_members = getVSQGroupMembers($ldap_group['id']);
    while ( !$vsq_group_members->EOF ) {

        $vsq_group_member = $vsq_group_members->fields;
var_dump($vsq_group_member);

        $vsq_group_members->MoveNext();
    }
exit;

var_dump($users);
    
exit;
    $ldap_groups->MoveNext();
}
    
// Close DB connection if open
if ( ( $db !== false ) and is_resource($db->_connectionID) ) $db->close();

exit;

function getVSQGroupMembers($groupid) {
global $db, $myjobid, $debug, $jconf;

	$query = "
		SELECT
           	g.id,
            gm.userid,
            u.externalid,
            u.email
		FROM
			groups AS g,
            groups_members AS gm,
            users AS u
		WHERE
            gm.groupid = " . $groupid . " AND
            gm.groupid = g.id AND
            gm.userid = u.id
    ";

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

?>