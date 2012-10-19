<?php
define('BASE_PATH',  realpath( __DIR__ . '/../../..' ) . '/' );
define('PRODUCTION', 0 );
//define('DEBUG', true );
include_once( BASE_PATH . 'libraries/Springboard/Application/Cli.php');

$app = new Springboard\Application\Cli( BASE_PATH, PRODUCTION );
$fd  = fopen('channeltypes.csv', 'r');

$channeltypes = array();

while ( ( $data = fgetcsv( $fd, 1000, ';') ) !== false )
  $channeltypes[] = array(
		'name'          => $data[0],
		'name_stringid' => 0,
		'namehungarian' => $data[0],
		'nameenglish'   => $data[1],
		'weight'        => $data[2],
  );

$file = $app->bootstrap->config['datapath'] . 'defaultvalues/channeltypes.php';
file_put_contents(
  $file,
  "<?php\nreturn " . var_export( $channeltypes, true ) . ";\n"
);
