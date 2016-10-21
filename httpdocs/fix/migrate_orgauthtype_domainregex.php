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

$db              = $application->bootstrap->getAdoDB();
$rs              = $db->query("
  SELECT *
  FROM organizations_authtypes
  WHERE
    domainregex IS NOT NULL AND
    domainregex <> ''
");

echo "<pre>Migrating organizations_authtypes domains to domainregexes:\n";
flush();

foreach( $rs as $row ) {
  $domains = \Springboard\Tools::explodeAndTrim(',', $row['domainregex'] );
  $parts = array();
  foreach( $domains as $domain )
    $parts[] = preg_quote( $domain );

  $regex = '(' . implode('|', $parts ) . ')';
  $db->execute("
    UPDATE organizations_authtypes
    SET domainregex = " . $db->qstr( $regex ) . "
    WHERE id = " . $row['id']
  );
  echo $row['id'], "\t", $row['domainregex'], "\t=>\t", $regex, "\n";
  flush();
}

unset( $rs );


$rs = $db->query("
  SELECT *
  FROM organizations_directories
  WHERE
    domainregex IS NOT NULL AND
    domainregex <> ''
");

echo "<pre>Migrating organizations_directories domains to domainregexes:\n";
flush();

foreach( $rs as $row ) {
  $domains = \Springboard\Tools::explodeAndTrim(',', $row['domainregex'] );
  $parts = array();
  foreach( $domains as $domain )
    $parts[] = preg_quote( $domain );

  $regex = '(' . implode('|', $parts ) . ')';
  $db->execute("
    UPDATE organizations_directories
    SET domainregex = " . $db->qstr( $regex ) . "
    WHERE id = " . $row['id']
  );
  echo $row['id'], "\t", $row['domainregex'], "\t=>\t", $regex, "\n";
  flush();
}

unset( $rs );
