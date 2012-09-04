<?php
$user = $this->bootstrap->getSession('user');

$config = array(
  
  'action' => array(
    'type'  => 'inputHidden',
    'value' => 'submitcreatefeed'
  ),
  
  'id' => array(
    'type'  => 'inputHidden',
    'value' => $this->application->getNumericParameter('id'),
  ),
  
  'fs1' => array(
    'type'   => 'fieldset',
    'legend' => $l('live', 'createfeed_title'),
    'prefix' => '<span class="legendsubtitle">' . $l('live', 'createfeed_subtitle') . '</span>',
  ),
  
  'name' => array(
    'displayname' => $l('live', 'feedname'),
    'type'        => 'inputText',
    'validation'  => array(
      array(
        'required' => true,
        'type'     => 'string',
        'minimum'  => 2,
        'maximum'  => 512,
      ),
    ),
  ),
  
  'isexternal' => array(
    'displayname' => $l('live', 'external'),
    'type'        => 'inputRadio',
    'values'      => $l->getLov('yesno'),
    'value'       => 0,
  ),
  
  'numberofstreams' => array(
    'displayname' => $l('live', 'numberofstreams'),
    'type'        => 'inputRadio',
    'values'      => $l->getLov('numberofstreams'),
    'value'       => 1,
    'divider'     => '<br/>',
    'divide'      => 1,
  ),
);

if ( $this->controller->organization['isvcrenabled'] ) {
  
  $config['feedtype'] = array(
    'type'        => 'inputRadio',
    'displayname' => $l('live', 'feedtype'),
    'values'      => $l->getLov('feedtype'),
    'value'       => 'live',
  );
  
  $config['recordinglinkid'] = array(
    'type'        => 'selectDynamic',
    'displayname' => $l('live', 'recordinglinkid'),
    'values'      => array('' => ''),
    'sql'         => "
      SELECT id, name
      FROM recording_links
      WHERE
        organizationid = '" . $this->controller->organization['id'] . "' AND
        disabled       = '0'
      ORDER BY name
    ",
  );
  
} else
  $config['feedtype'] = array(
    'type'     => 'inputHidden',
    'value'    => 'live',
    'readonly' => true,
  );

if ( $this->controller->organization['issecurestreamingenabled'] )
  $config['issecurestreamingforced'] = array(
    'type'        => 'inputRadio',
    'displayname' => $l('live', 'issecurestreamingforced'),
    'values'      => $l->getLov('encryption'),
  );
else
  $config['issecurestreamingforced'] = array(
    'type'     => 'inputHidden',
    'value'    => '0',
    'readonly' => true,
  );

include( $this->bootstrap->config['modulepath'] . 'Visitor/Form/Configs/Accesstype.php');

$config['moderationtype'] = array(
  'displayname' => $l('live', 'moderationtype'),
  'type'        => 'select',
  'values'      => $l->getLov('moderationtype'),
);
