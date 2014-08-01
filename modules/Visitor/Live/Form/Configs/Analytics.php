<?php
$fromdatetime  = substr( $this->channelModel->row['starttimestamp'], 0, 16 );
$endts         = min( strtotime( $this->channelModel->row['endtimestamp'] ), time() );
$untildatetime = date('Y-m-d H:i', $endts );

$config = array(

  'action' => array(
    'type'  => 'inputHidden',
    'value' => 'submitanalytics'
  ),

  'feedids' => array(
    'type'        => 'inputCheckboxDynamic',
    'displayname' => $l('live', 'analytics_feedids'),
    'rowlayout'   => $this->singlecolumnlayout,
    'values'      => $this->feeds,
    'value'       => $this->application->getParameter('feedids', $this->feedids ),
  ),

  'starttimestamp' => array(
    'displayname' => $l('live', 'analytics_starttimestamp'),
    'type'        => 'inputText',
    'html'        =>
      'class="inputtext inputbackground clearonclick datetimepicker margin" ' .
      'data-datetimefrom="' . $fromdatetime . '"' .
      'data-datetimeuntil="' . $untildatetime . '"'
    ,
    'rowlayout'   => $this->singlecolumnlayout,
    'value'       => $fromdatetime,
    'validation'  => array(
      array(
        'type'       => 'date',
        'format'     => 'YYYY-MM-DD h:m',
        'lesseqthan' => 'endtimestamp',
        'help'       => $l('live', 'analytics_starttimestamp_help'),
      )
    ),
  ),

  'endtimestamp' => array(
    'displayname' => $l('live', 'analytics_endtimestamp'),
    'type'        => 'inputText',
    'html'        =>
      'class="inputtext inputbackground clearonclick datetimepicker margin" ' .
      'data-datetimefrom="' . $fromdatetime . '"' .
      'data-datetimeuntil="' . $untildatetime . '"'
    ,
    'rowlayout'   => $this->singlecolumnlayout,
    'value'       => $untildatetime,
    'validation'  => array(
      array(
        'type'          => 'date',
        'format'        => 'YYYY-MM-DD h:m',
        'greatereqthan' => 'starttimestamp',
        'help'          => $l('live', 'analytics_endtimestamp_help'),
      )
    ),
  ),

  'resolution' => array(
    'displayname' => $l('live', 'analytics_resolution'),
    'type'        => 'inputRadio',
    'rowlayout'   => $this->singlecolumnlayout,
    'divide'      => 1,
    'divider'     => '<br/>',
    'values'      => $l->getLov('live_analytics_resolutions'),
    'value'       => $this->feedModel->getMinStep(
      $this->channelModel->row['starttimestamp'],
      $this->channelModel->row['endtimestamp']
    ),
  ),

  'datapoints' => array(
    'displayname' => $l('live', 'analytics_datapoints'),
    'type'        => 'inputCheckboxDynamic',
    'rowlayout'   => $this->singlecolumnlayout,
    'values'      => $l->getLov('live_analytics_datapoints'),
    'value'       => array( '4' ),
  ),
);
