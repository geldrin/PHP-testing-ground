<?php
$groupSelect = array(
  'displayname' => $l('live', 'livestreamgroupid'),
  'type'        => 'selectDynamic',
  'sql'         => "
    SELECT id, name
    FROM livestream_groups
    WHERE disabled = '0'
    ORDER BY name
  ",
  'values' => array( 0 => $l('live', 'livestreamgroupid_default') ),
);

$config = array(
  
  'action' => array(
    'type'  => 'inputHidden',
    'value' => 'submitcreatefeed'
  ),
  
  'id' => array(
    'type'  => 'inputHidden',
    'value' => $this->application->getNumericParameter('id'),
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
  
);

if ( $this->controller->organization['isvcrenabled'] ) {
  
  $config['feedtype'] = array(
    'type'        => 'inputRadio',
    'displayname' => $l('live', 'feedtype'),
    'values'      => $l->getLov('feedtype'),
    'value'       => 'live',
  );

  $config['livestreamgroupid'] = $groupSelect;
  
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
    'validation' => array(
      array(
        'type'      => 'string',
        'minimum'   => 1,
        'help'      => $l('live', 'recordinglinkid_help'),
        'anddepend' => array(
          array(
            'js'  => '<FORM.feedtype> == "vcr"',
            'php' => '<FORM.feedtype> == "vcr"',
          ),
        ),
      ),
    ),
  );
  
  $config['needrecording'] = array(
    'displayname' => $l('live', 'needrecording'),
    'type'        => 'inputRadio',
    'values'      => $l->getLov('yesno'),
    'value'       => 0,
  );
  
} else {
  $config['feedtype'] = array(
    'type'     => 'inputHidden',
    'value'    => 'live',
    'readonly' => true,
  );
  $config['livestreamgroupid'] = $groupSelect;
}

if ( $this->controller->organization['issecurestreamingenabled'] )
  $config['issecurestreamingforced'] = array(
    'type'        => 'inputRadio',
    'displayname' => $l('live', 'issecurestreamingforced'),
    'values'      => $l->getLov('encryption'),
    'value'       => 0,
    'validation'  => array(
      array('type' => 'required'),
    ),
  );
else
  $config['issecurestreamingforced'] = array(
    'type'     => 'inputHidden',
    'value'    => '0',
    'readonly' => true,
  );

$config['slideonright'] = array(
  'displayname' => $l('live', 'slideonright'),
  'type'        => 'inputRadio',
  'value'       => 1,
  'itemlayout'  => $this->radioitemlayout,
  'values'      => array(
    0 => $l('live', 'slideright'),
    1 => $l('live', 'slideleft'),
  ),
);

$recordingsModel = $this->bootstrap->getModel('recordings');
if ( $recordingsModel->getIntroOutroCount( $this->controller->organization['id'] ) ) {

  $recordings =
    $recordingsModel->getIntroOutroAssoc( $this->controller->organization['id'] )
  ;

  $introoutro = array(
    'introrecordingid' => array(
      'displayname' => $l('recordings', 'introrecordingid'),
      'type'        => 'select',
      'values'      => array('' => $l('recordings', 'nointro') ) + $recordings,
    ),
  );

  $config = \Springboard\Tools::insertAfterKey( $config, $introoutro, 'slideonright' );

}

include( $this->bootstrap->config['modulepath'] . 'Visitor/Form/Configs/Accesstype.php');

$config['moderationtype'] = array(
  'displayname' => $l('live', 'moderationtype'),
  'type'        => 'select',
  'values'      => $l->getLov('moderationtype'),
  'value'       => 'nochat',
);

$config['anonymousallowed'] = array(
  'displayname' => $l('live', 'anonymousallowed'),
  'type'        => 'inputRadio',
  'value'       => 0,
  'values'      => $l->getLov('noyes'),
);

$config['isnumberofviewspublic'] = array(
  'displayname' => $l('live', 'isnumberofviewspublic'),
  'type'        => 'inputRadio',
  'value'       => 1,
  'itemlayout'  => $this->radioitemlayout,
  'values'      => array(
    0 => $l('live', 'isnumberofviewspublic_nonpublic'),
    1 => $l('live', 'isnumberofviewspublic_public'),
  ),
);
