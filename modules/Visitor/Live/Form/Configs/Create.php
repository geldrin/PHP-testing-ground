<?php

$config = array(

  'action' => array(
    'type'  => 'inputHidden',
    'value' => 'submitcreate'
  ),

  'parent' => array(
    'type'  => 'inputHidden',
    'value' => $this->application->getNumericParameter('parent'),
  ),

  'isliveevent' => array(
    'type'     => 'inputHidden',
    'value'    => '1',
    'readonly' => true,
  ),

  'title' => array(
    'displayname' => $l('live', 'title'),
    'type'        => 'inputText',
    'validation'  => array(
      array( 'type' => 'required' ),
      array(
        'type' => 'string',
        'minimum' => 4,
        'maximum' => 512,
      ),
    ),
  ),

  'subtitle' => array(
    'displayname' => $l('live', 'subtitle'),
    'type'        => 'inputText',
    'validation'  => array(
      array(
        'type'     => 'string',
        'minimum'  => 4,
        'maximum'  => 512,
        'required' => false,
      ),
    ),
  ),

  'description' => array(
    'displayname' => $l('live', 'description'),
    'type'        => 'textarea',
    'validation'  => array(
      array(
        'type' => 'string',
        'minimum'  => 4,
        'required' => false,
      ),
    ),
  ),

  'channeltypeid' => array(
    'displayname' => $l('live', 'channelsubtype'),
    'type'        => 'selectDynamic',
    'sql'         => "
      SELECT ct.id, s.value
      FROM channel_types AS ct, strings AS s
      WHERE
        s.translationof   = ct.name_stringid AND
        s.language        = '" . \Springboard\Language::get() . "' AND
        ct.isfavorite     = 0 AND
        ct.organizationid = '" . $this->controller->organization['id'] . "' AND
        %s
      ORDER BY ct.weight
    ",
    'treeid'      => 'id',
    'treestart'   => '0', // TODO event channel types only
    'treeparent'  => 'ct.parentid',
    'validation'  => array(
      /* TODO
      array(
        'type' => 'required',
        'help' => $l('live', 'channelsubtypehelp'),
      ),
      */
    ),
  ),

  'starttimestamp' => array(
    'displayname' => $l('live', 'starttimestamp'),
    'type'        => 'inputText',
    'html'        =>
      'class="inputtext inputbackground clearonclick datetimepicker margin" ' .
      'data-dateyearrange="' . date('Y') . ':' . ( date('Y') + 1 ) . '"' .
      'data-datetimefrom="' . date('Y-m-d 00:00') . '"'
    ,
    'value'       => date('Y-m-d H:m'),
    'validation'  => array(
      array(
        'type'       => 'date',
        'format'     => 'YYYY-MM-DD hh:mm',
        'lesseqthan' => 'endtimestamp',
        'help'       => $l('live', 'starttimestamp_help'),
      )
    ),
  ),

  'endtimestamp' => array(
    'displayname' => $l('live', 'endtimestamp'),
    'type'        => 'inputText',
    'html'        =>
      'class="inputtext inputbackground clearonclick datetimepicker margin" ' .
      'data-dateyearrange="' . date('Y') . ':' . ( date('Y') + 1 ) . '"' .
      'data-datetimefrom="' . date('Y-m-d 00:00') . '"'
    ,
    'value'       => date('Y-m-d H:m', strtotime('+1 day')),
    'validation'  => array(
      array(
        'type'          => 'date',
        'format'        => 'YYYY-MM-DD hh:mm',
        'greatereqthan' => 'starttimestamp',
        'help'          => $l('live', 'endtimestamp_help'),
      )
    ),
  ),
);

$tokenDisabled = true;
include( $this->bootstrap->config['modulepath'] . 'Visitor/Form/Configs/Accesstype.php');

if (
     $this->parentchannelModel and $this->parentchannelModel->id and
     !$this->parentchannelModel->row['ispublic']
   ) {

  $config['ispublic']['html']    = 'disabled="disabled"';
  $config['ispublic']['postfix'] = $l('live', 'ispublic_disabled');

}

if (
     \Model\Userroles::userHasPrivilege(
       null,
       'live_feature',
       'or',
       'iseditor', 'isclientadmin', 'isliveadmin', 'isadmin'
     )
   ) {

  $config['isfeatured'] = array(
    'displayname' => $l('live', 'isfeatured'),
    'itemlayout'  => $this->radioitemlayout,
    'type'        => 'inputRadio',
    'value'       => '0',
    'values'      => $l->getLov('noyes'),
    'rowlayout'   => '
      <tr %errorstyle%>
        <td class="labelcolumn" style="width: 170px;">
          <label for="%id%">%displayname%</label>
        </td>
        <td class="elementcolumn">%prefix%%element%%postfix%%errordiv%</td>
      </tr>
    ',
  );

}
