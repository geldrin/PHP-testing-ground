<?php
$language       = \Springboard\Language::get();
$organizationid = $this->controller->organization['id'];
$advancedURL = $language . '/search/advanced';
$advancedLabel  = $l('', 'sitewide_search_advanced');
$advancedClearLabel = $l('', 'sitewide_search_clear');
$config         = array(

  // submitted = true, if set -> form was submitted
  's' => array(
    'type'  => 'inputHidden',
    'value' => 1,
  ),

  'q' => array(
    'displayname' => $l('search', 'q'),
    'type'        => 'inputText',
    'html'        => 'class="inputtext inputbackground clearonclick" data-origval="' . $l('search', 'q') . '"',
    'value'       => $l('search', 'q'),
    'validation'  => array(
      array(
        'type'     => 'string',
        'required' => false,
        'minimum'  => 3,
        'help'     => $l('search', 'q_help'),
      ),
      array(
        'type' => 'custom',
        'php'  => '
          $GLOBALS["formcontroller"]->checkAdvancedSearchInputs(
            <FORM.q>,
            <FORM.contributorjob>,
            <FORM.contributororganization>,
            <FORM.contributorname>
          )
        ',
        'js'   => 'checkAdvancedSearchInputs()',
        'help' => $l('search', 'q_help'),
      ),
    ),
    'rowlayout'   => '
      <tr>
        <td colspan="3" id="advancedsearchrow">
          <div class="wrap">
            <button id="searchadvancedsubmit" type="submit"></button>
            %prefix%%element%%postfix%

            <a href="#" id="searchadvancedclear" title="' . $advancedClearLabel . '"><span></span>' . $advancedClearLabel . '</a>
          </div>
          <div class="channelgradient"></div>
          %errordiv%
        </td>
      </tr>
      <tr>
        <td class="first">
    ',
  ),

  'wholeword' => array(
    'displayname' => $l('search', 'wholeword'),
    'type'        => 'inputRadio',
    'values'      => $l->getLov('search_wholeword'),
    'value'       => 0,
    'divide'      => 1,
    'divider'     => '</div><div class="radio last">',
    'validation'  => array(
    ),
    'rowlayout'   => '
      <div class="element wholewordcontainer">
        <div class="radio">%prefix%%element%%postfix%%errordiv%</div>
      </div>
    ',
  ),

  'category' => array(
    'displayname' => $l('search', 'category'),
    'type'        => 'selectDynamic',
    'html'        => 'class="inputtext inputbackground margin"',
    'values'      => array( $l('search', 'category') ),
    'treeid'      => 'id',
    'treestart'   => '0',
    'treeparent'  => 'parentid',
    'sql'         => "
      SELECT c.id, s.value AS name
      FROM
        categories AS c,
        strings AS s
      WHERE
        c.organizationid = '$organizationid' AND
        c.name_stringid  = s.translationof AND
        s.language       = '$language' AND
        %s
      ORDER BY c.weight, name
    ",
    'validation'  => array(
    ),
  ),

  'languages' => array(
    'type'        => 'selectDynamic',
    'html'        => 'class="inputtext inputbackground margin"',
    'htmlid'      => 'lang',
    'displayname' => $l('search', 'languages'),
    'values'      => array('' => $l('search', 'languages') ),
    'sql'         => "
      SELECT DISTINCT l.id, s.value
      FROM
        languages AS l,
        strings AS s,
        recordings AS r
      WHERE
        r.languageid     = l.id AND
        l.name_stringid  = s.translationof AND
        s.language       = '" . $language . "' AND
        r.status         = 'onstorage' AND
        r.organizationid = '" . $organizationid . "'
      ORDER BY l.weight
    ",
    'rowlayout'   => '
          <div class="element">%prefix%%element%%postfix%%errordiv%</div>
        </td>
        <td class="second">
    ',
  ),

  'department' => array(
    'displayname' => $l('search', 'department'),
    'type'        => 'selectDynamic',
    'html'        => 'class="inputtext inputbackground margin"',
    'values'      => array( $l('search', 'department') ),
    'treeid'      => 'id',
    'treestart'   => '0',
    'treeparent'  => 'parentid',
    'sql'         => "
      SELECT id, name
      FROM departments
      WHERE
        organizationid = '$organizationid' AND
        %s
      ORDER BY weight, name
    ",
    'validation'  => array(
    ),
  ),

  'contributorname' => array(
    'displayname' => $l('search', 'contributorname'),
    'type'        => 'inputText',
    'html'        => 'class="inputtext inputbackground clearonclick margin" data-origval="' . $l('search', 'contributorname') . '"',
    'value'       => $l('search', 'contributorname'),
    'validation'  => array(
    ),
  ),

  'contributorjob' => array(
    'displayname' => $l('search', 'contributorjob'),
    'type'        => 'inputText',
    'html'        => 'class="inputtext inputbackground clearonclick margin" data-origval="' . $l('search', 'contributorjob') . '"',
    'value'       => $l('search', 'contributorjob'),
    'validation'  => array(
    ),
  ),

  'contributororganization' => array(
    'displayname' => $l('search', 'contributororganization'),
    'type'        => 'inputText',
    'html'        => 'class="inputtext inputbackground clearonclick margin" data-origval="' . $l('search', 'contributororganization') . '"',
    'value'       => $l('search', 'contributororganization'),
    'validation'  => array(
    ),
    'rowlayout'   => '
          <div class="element">%prefix%%element%%postfix%%errordiv%</div>
        </td>
        <td class="third">
    ',
  ),

  'createdatefrom' => array(
    'displayname' => $l('search', 'createdatefrom'),
    'type'        => 'inputText',
    'html'        => 'class="inputtext inputbackground clearonclick datepicker margin" data-origval="' . $l('search', 'createdatefrom') . '"',
    'value'       => $l('search', 'createdatefrom'),
    'validation'  => array(
      array(
        'type'     => 'date',
        'required' => false,
        'format'   => 'YYYY-MM-DD',
        'anddepend' => array(
          array(
            'type' => 'custom',
            'php'  => '<FORM.createdatefrom> != "' . $l('search', 'createdatefrom') . '"',
            'js'   => '<FORM.createdatefrom> != "' . $l('search', 'createdatefrom') . '"',
          ),
        ),
      ),
    ),
  ),

  'createdateto' => array(
    'displayname' => $l('search', 'createdateto'),
    'type'        => 'inputText',
    'html'        => 'class="inputtext inputbackground clearonclick datepicker margin" data-origval="' . $l('search', 'createdateto') . '"',
    'value'       => $l('search', 'createdateto'),
    'validation'  => array(
      array(
        'type'     => 'date',
        'required' => false,
        'format'   => 'YYYY-MM-DD',
        'anddepend' => array(
          array(
            'type' => 'custom',
            'php'  => '<FORM.createdateto> != "' . $l('search', 'createdateto') . '"',
            'js'   => '<FORM.createdateto> != "' . $l('search', 'createdateto') . '"',
          ),
        ),
      ),
    ),
  ),

  'uploaddatefrom' => array(
    'displayname' => $l('search', 'uploaddatefrom'),
    'type'        => 'inputText',
    'html'        => 'class="inputtext inputbackground clearonclick datepicker margin" data-origval="' . $l('search', 'uploaddatefrom') . '"',
    'value'       => $l('search', 'uploaddatefrom'),
    'validation'  => array(
      array(
        'type'     => 'date',
        'required' => false,
        'format'   => 'YYYY-MM-DD',
        'anddepend' => array(
          array(
            'type' => 'custom',
            'php'  => '<FORM.uploaddatefrom> != "' . $l('search', 'uploaddatefrom') . '"',
            'js'   => '<FORM.uploaddatefrom> != "' . $l('search', 'uploaddatefrom') . '"',
          ),
        ),
      ),
    ),
  ),

  'uploaddateto' => array(
    'displayname' => $l('search', 'uploaddateto'),
    'type'        => 'inputText',
    'html'        => 'class="inputtext inputbackground clearonclick datepicker margin" data-origval="' . $l('search', 'uploaddateto') . '"',
    'value'       => $l('search', 'uploaddateto'),
    'validation'  => array(
      array(
        'type'     => 'date',
        'required' => false,
        'format'   => 'YYYY-MM-DD',
        'anddepend' => array(
          array(
            'type' => 'custom',
            'php'  => '<FORM.uploaddateto> != "' . $l('search', 'uploaddateto') . '"',
            'js'   => '<FORM.uploaddateto> != "' . $l('search', 'uploaddateto') . '"',
          ),
        ),
      ),
    ),
    'rowlayout'   => '
          <div class="element">%prefix%%element%%postfix%%errordiv%</div>
        </td>
      </tr>
    ',
  ),

);

$GLOBALS['formcontroller'] = $this;