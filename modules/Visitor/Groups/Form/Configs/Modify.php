<?php
include('Create.php');
$config['action']['value'] = 'submitmodify';

$config['id']              = Array(
  'type'  => 'inputHidden',
  'value' => $this->application->getNumericParameter('id'),
);

// valtoztatasnal lehet 1 ldapdn
if ( isset( $config['organizationdirectoryldapdn'] ) )
  $config['organizationdirectoryldapdn']['validation'][1]['value'] = '1';

if ( $this->groupModel->row['source'] === 'directory' ) {
  unset( $config['name']['validation'][0]['anddepend'] );
}
