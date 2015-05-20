<?php

if ( !isset( $user ) )
  $user = $this->bootstrap->getSession('user');

if ( $user['isadmin'] or $user['isclientadmin'] or $user['iseditor'] )
  $approvalstatuses = $l->getLov('recordings_approvalstatus_full');
elseif (
         $user['ismoderateduploader'] and
         $this->recordingsModel->row['approvalstatus'] != 'approved'
       )
  $approvalstatuses = $l->getLov('recordings_approvalstatus_min');
// ha mar engedve van akkor mindent mutatunk
elseif (
         $user['ismoderateduploader'] and
         $this->recordingsModel->row['approvalstatus'] == 'approved'
       )
  $approvalstatuses = $l->getLov('recordings_approvalstatus_full');
else
  $approvalstatuses = $l->getLov('recordings_approvalstatus_default');

$config = array(

  'action' => array(
    'type'  => 'inputHidden',
    'value' => 'submitmodifysharing'
  ),

  'id' => array(
    'type'  => 'inputHidden',
    'value' => $this->application->getNumericParameter('id'),
  ),
  
  'forward' => array(
    'type'  => 'inputHidden',
    'value' => $this->application->getParameter('forward'),
  ),
  
  'fs1' => array(
    'type'   => 'fieldset',
    'legend' => $l('recordings', 'sharing_title'),
    'prefix' => '<span class="legendsubtitle">' . $l('recordings', 'sharing_subtitle') . '</span>',
  ),
  
);

include( $this->bootstrap->config['modulepath'] . 'Visitor/Form/Configs/Accesstype.php');

if ( isset( $config['departments[]'] ) )
  $config['departments[]']['valuesql'] = "
    SELECT departmentid
    FROM access
    WHERE
      recordingid = '" . $this->recordingsModel->id . "' AND
      departmentid IS NOT NULL
  ";

if ( isset( $config['groups[]'] ) )
  $config['groups[]']['valuesql'] = "
    SELECT groupid
    FROM access
    WHERE
      recordingid = '" . $this->recordingsModel->id . "' AND
      groupid IS NOT NULL
  ";

$config = array_merge( $config, array(
  'wanttimelimit' => array(
    'displayname' => $l('recordings', 'wanttimelimit'),
    'type'        => 'inputRadio',
    'value'       => 0,
    'values'      => $l->getLov('noyes'),
  ),
  
  'visiblefrom' => array(
    'displayname' => $l('recordings', 'visiblefrom'),
    'type'        => 'inputText',
    'html'        =>
      'class="inputtext inputbackground clearonclick datepicker margin" ' .
      'data-dateyearrange="' . date('Y') . ':' . ( date('Y') + 10 ) . '"' .
      'data-datefrom="' . date('Y-m-d') . '"'
    ,
    'value'       => date('Y-m-d'),
    'validation'  => array(
      array(
        'type'       => 'date',
        'format'     => 'YYYY-MM-DD',
        'lesseqthan' => 'visibleuntil',
        'help'       => $l('recordings', 'visiblefrom_help'),
      )
    ),
  ),
  
  'visibleuntil' => array(
    'displayname' => $l('recordings', 'visibleuntil'),
    'type'        => 'inputText',
    'html'        =>
      'class="inputtext inputbackground clearonclick datepicker margin" ' .
      'data-dateyearrange="' . date('Y') . ':' . ( date('Y') + 10 ) . '"' .
      'data-datefrom="' . date('Y-m-d') . '"'
    ,
    'value'       => date('Y-m-d', strtotime('+3 months')),
    'validation'  => array(
      array(
        'type'          => 'date',
        'format'        => 'YYYY-MM-DD',
        'greatereqthan' => 'visiblefrom',
        'help'          => $l('recordings', 'visibleuntil_help'),
      )
    ),
  ),
  
  'isdownloadable' => array(
    'displayname' => $l('recordings', 'isdownloadable'),
    'type'        => 'inputRadio',
    'value'       => 1,
    'values'      => $l->getLov('noyes'),
  ),
  
  'isaudiodownloadable' => array(
    'displayname' => $l('recordings', 'isaudiodownloadable'),
    'type'        => 'inputRadio',
    'value'       => 0,
    'values'      => $l->getLov('noyes'),
  ),
  
  'isembedable' => array(
    'displayname' => $l('recordings', 'isembedable'),
    'type'        => 'inputRadio',
    'value'       => 1,
    'values'      => $l->getLov('noyes'),
  ),
  
  'approvalstatus' => array(
    'displayname' => $l('recordings', 'approvalstatus'),
    'postfix'     => '<div class="smallinfo">' . $l('recordings', 'approvalstatus_postfix') . '</div>',
    'type'        => 'inputRadio',
    'value'       => 'draft',
    'values'      => $approvalstatuses,
    'itemlayout'  => $this->radioitemlayout,
  ),

));

if ( $this->controller->organization['issecurestreamingenabled'] )
  $config['issecurestreamingforced'] = array(
    'type'        => 'inputRadio',
    'displayname' => $l('live', 'issecurestreamingforced'),
    'values'      => $l->getLov('encryption'),
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

$config['isseekbardisabled'] = array(
  'type'        => 'inputRadio',
  'displayname' => $l('recordings', 'isseekbardisabled'),
  'values'      => $l->getLov('noyes'),
  'value'       => 0,
  'validation'  => array(
    array(
      'type' => 'custom',
      'help' => $l('recordings', 'isseekbardisabled_help'),
      'js'  => '( <FORM.isseekbardisabled> == 1 && <FORM.accesstype> != "public" ) || <FORM.isseekbardisabled> == 0',
      'php' => '( <FORM.isseekbardisabled> == 1 && <FORM.accesstype> != "public" ) || <FORM.isseekbardisabled> == 0',
    ),
  ),
);

$config['isanonymouscommentsenabled'] = array(
  'type'        => 'inputRadio',
  'displayname' => $l('recordings', 'isanonymouscommentsenabled'),
  'values'      => $l->getLov('noyes'),
  'value'       => 0,
  'validation'  => array(
  ),
);

if ( $user['isadmin'] or $user['isclientadmin'] or $user['iseditor'] ) {

  $config['isfeatured'] = array(
    'displayname' => $l('recordings', 'isfeatured'),
    'type'        => 'inputRadio',
    'value'       => 0,
    'values'      => $l->getLov('noyes'),
  );

}
