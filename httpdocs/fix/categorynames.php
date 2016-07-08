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

echo "<pre>Consolidating hyphenated category names and deleting them afterwards:\n";
flush();

$rs = $db->execute("
  SELECT
    c.id AS categoryid,
    names.id AS nameid,
    names.value AS namevalue,
    names.language AS namelang,
    hyph.id AS hyphid,
    hyph.value AS hyphvalue,
    hyph.language AS hyphlang
  FROM
    categories AS c,
    strings AS names,
    strings AS hyph
  WHERE
    names.translationof = c.name_stringid AND
    hyph.translationof = c.namehyphenated_stringid AND
    hyph.language = names.language
");

$names = array();
$deleteids = array();

foreach( $rs as $row ) {
  $name = $row['namevalue'];
  if ( $row['hyphvalue'] )
    $name = $row['hyphvalue'];

  $deleteids[] = $row['hyphid'];
  if ( !$name ) // ures volt minden
    continue;

  $names[ $row['nameid'] ] = $name;

  echo "+";
  flush();
}

$rs->close();
echo "\n";
flush();

foreach( $names as $id => $value ) {
  $value = $db->qstr( $value );
  $db->execute("
    UPDATE strings
    SET value = $value
    WHERE id = '$id'
    LIMIT 1
  ");
  echo ".";
  flush();
}

echo "\n";
flush();

while( !empty( $deleteids ) ) {
  $chunk = array_splice( $deleteids, 0, 50 );
  $db->execute("
    DELETE FROM strings
    WHERE id IN('" . implode("', '", $chunk ) . "')
  ");

  echo str_repeat( '-', count( $chunk ) );
  flush();
}

echo "\nDone!\n";
