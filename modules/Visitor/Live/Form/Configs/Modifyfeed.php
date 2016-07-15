<?php
include('Createfeed.php');
unset( $config['livestreamgroupid'] );

$config['action']['value']           = 'submitmodifyfeed';

$config['departments[]']['valuesql'] = "
  SELECT departmentid
  FROM access
  WHERE
    livefeedid = " . $this->application->getNumericParameter('id') . " AND
    departmentid IS NOT NULL
";
$config['groups[]']['valuesql']      = "
  SELECT groupid
  FROM access
  WHERE
    livefeedid = " . $this->application->getNumericParameter('id') . " AND
    groupid IS NOT NULL
";

if ( $this->feedModel->row['feedtype'] == 'live' ) {

  $config['feedtype']['validation'] = array(
    array(
      'type' => 'custom',
      'php'  => 'true',
      'js'   => '(<FORM.feedtype> != "live")? confirm(' . json_encode( $l('live', 'feedtypechange') ) . '): true',
    ),
  );

} elseif ( $this->feedModel->row['feedtype'] == 'vcr' ) {

  if ( !$this->feedModel->canDeleteFeed() )
    unset(
      $config['feedtype'], // nem változtatható
      $config['recordinglinkid']['validation'][0]['anddepend']
    );

}
