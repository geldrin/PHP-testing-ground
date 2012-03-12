<?php
$user = $this->bootstrap->getUser();
$organizationid = $this->controller->organization['id'];

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
  
  'accesstype' => array(
    'displayname' => $l('recordings', 'accesstype'),
    'itemlayout'  => '%radio% %label% <br/>',
    'type'        => 'inputRadio',
    'value'       => 'public',
    'values'      => $l->getLov('accesstype'),
  ),
  
  'organizations[]' => array(
    'displayname' => $l('recordings', 'organizations'),
    'type'        => 'inputCheckboxDynamic',
    'html'        => '',
    'sql'         => "
      SELECT
        id, " .
        ( \Springboard\Language::get() == 'en' ?
          "IF(LENGTH(nameshortenglish), CONCAT( nameenglish,  ' (', nameshortenglish,  ')' ), nameenglish  )" :
          "IF(LENGTH(nameshortoriginal),CONCAT( nameoriginal, ' (', nameshortoriginal, ')' ), nameoriginal )"
        ) . "
      FROM organizations
      WHERE %s
      ORDER BY
      " .
      (
        \Springboard\Language::get() == 'en' ?
        "IF(LENGTH(nameenglish)=0,nameshortenglish,nameenglish)" :
        "IF(LENGTH(nameoriginal)=0,nameshortoriginal,nameoriginal)"
      ) . "
    ",
    'prefix'      => '<div class="formoverflowframe" id="organizationscontainer">',
    'postfix'     => '</div>',
    'itemlayout'  =>
      '<div class="cbxdynamiclevel%level%">'.
        '<span class="indent">%indent%</span> %checkbox% '.
        '<span title="%valuehtmlescape%">%label%</span>'.
      '</div>' . "\r\n"
    ,
    'treeid'      => 'id',
    'treestart'   => $organizationid,
    'treestartinclusive' => true,
    'treeparent'  => 'parentid',
    'valuesql'    => "SELECT organizationid FROM recordings_access WHERE recordingid = " . $this->application->getNumericParameter('id'),
  ),
  
  'groups[]' => array(
    'displayname' => $l('recordings', 'groups'),
    'type'        => 'inputCheckboxDynamic',
    'sql'         => "
      SELECT
        groups.id, groups.name
      FROM
        groups, groups_members
      WHERE
        groups_members.userid = '" . $user->id . "' AND
        groups.id = groups_members.groupid
      ORDER BY groups.name DESC",
    'valuesql'    => "SELECT groupid FROM recordings_access WHERE recordingid = " . $this->application->getNumericParameter('id'),
    'validation'  => array(
      array(
        'type' => 'required',
        'help' => $l('recordings', 'groupshelp'),
        'anddepend' => Array(
          Array(
            'js'  => '<FORM.accesstype> == "groups"',
            'php' => '<FORM.accesstype> == "groups"',
          )
        ),
      ),
    ),
  ),
  
  'wanttimelimit' => array(
    'displayname' => $l('recordings', 'wanttimelimit'),
    'type'        => 'inputRadio',
    'value'       => 0,
    'values'      => $l->getLov('noyes'),
  ),
  
  'visiblefrom' => array(
    'displayname' => $l('recordings', 'visiblefrom'),
    'type'        => 'selectDate',
    'postfix'     => '<div class="datepicker"></div>',
    'format'      => '%Y-%M-%D',
    'yearfrom'    => date('Y') + 10, // current year + 10 years
    'value'       => date('Y-m-d'),
    'validation'  => array(
    ),
  ),
  
  'visibleuntil' => array(
    'displayname' => $l('recordings', 'visibleuntil'),
    'type'        => 'selectDate',
    'postfix'     => '<div class="datepicker"></div>',
    'format'      => '%Y-%M-%D',
    'yearfrom'    => date('Y') + 10, // current year + 10 years
    //'yearuntil'   => false, // current year only
    'value'       => date('Y-m-d', strtotime('+3 months')),
    'validation'  => array(
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
    'value'       => 1,
    'values'      => $l->getLov('noyes'),
  ),
  
  'isembedable' => array(
    'displayname' => $l('recordings', 'isembedable'),
    'type'        => 'inputRadio',
    'value'       => 1,
    'values'      => $l->getLov('noyes'),
  ),
  
  'ispublished' => array(
    'displayname' => $l('recordings', 'ispublished'),
    'postfix'     => '<div class="smallinfo">' . $l('recordings', 'ispublished_postfix') . '</div>',
    'type'        => 'inputRadio',
    'value'       => 0,
    'values'      => array(
      $l('recordings', 'ispublished_no'),
      $l('recordings', 'ispublished_yes'),
    ),
  ),
  
);
