<?php
include('Createfeed.php');

$config['action']['value']             = 'submitmodifyfeed';
$config['fs1']['legend']               = $l('live', 'modifyfeed_title');
$config['fs1']['prefix']               =
  '<span class="legendsubtitle">' . $l('live', 'modifyfeed_subtitle') . '</span>'
;
$config['departments[]']['valuesql'] = "
  SELECT departmentid
  FROM access
  WHERE livefeedid = " . $this->application->getNumericParameter('id')
;
$config['groups[]']['valuesql']        = "
  SELECT groupid
  FROM access
  WHERE livefeedid = " . $this->application->getNumericParameter('id')
;
