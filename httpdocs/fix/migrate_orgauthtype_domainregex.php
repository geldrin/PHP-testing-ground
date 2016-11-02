<?php

define('BASE_PATH',  realpath( dirname( __FILE__ ) . '/../..' ) . '/' );
set_time_limit(0);

if ( isset( $_SERVER['APPLICATION_ENV'] ) and $_SERVER['APPLICATION_ENV'] == 'developer' )
  define('PRODUCTION', false );
else
  define('PRODUCTION', true );

include_once( BASE_PATH . 'libraries/Springboard/Application.php');
$application = new Springboard\Application( BASE_PATH, PRODUCTION, array() );
$application->loadConfig('config.php');
$application->loadConfig('config_local.php');

$application->bootstrap();

$db = $application->bootstrap->getAdoDB();
$shouldquit = $db->getOne("
  SELECT value
  FROM springboardconfiguration
  WHERE name = 'migratedOrganizationsAuthTypes'
  LIMIT 1
");

if ( $shouldquit )
  die('Already migrated, refusing');

migrateDomains('organizations_authtypes');
migrateDomains('organizations_directories');

$db->execute("
  INSERT INTO springboardconfiguration
  (name, value, timestamp) VALUES
  ('migratedOrganizationsAuthTypes', '1', NOW())
");

function migrateDomains( $table ) {
  global $db;

  echo "<pre>Migrating ", $table, " domains to domainregexes:\n";
  flush();

  $rs = $db->query("
    SELECT *
    FROM $table
    WHERE
      domainregex IS NOT NULL AND
      domainregex <> ''
  ");
  foreach( $rs as $row ) {
    $domains = \Springboard\Tools::explodeAndTrim(',', $row['domainregex'] );
    $parts = array();
    foreach( $domains as $domain )
      $parts[] = preg_quote( $domain );

    // igy jeleztuk regebben hekkelve hogy barmire matcheljen, helyette normalisan vegre
    if ( strpos( $row['domainregex'], ',,' ) !== false )
      $parts[] = '.*';

    $regex = '(' . implode('|', $parts ) . ')';
    $db->execute("
      UPDATE $table
      SET domainregex = " . $db->qstr( $regex ) . "
      WHERE id = " . $row['id']
    );
    echo $row['id'], "\t", $row['domainregex'], "\t=>\t", $regex, "\n";
    flush();
  }
}
