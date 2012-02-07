<?php

$httpInfo =
  'REMOTE_ADDR:          ' . @$_SERVER['REMOTE_ADDR'] . "\n" .
  'REMOTE_HOST:          ' . @$_SERVER['REMOTE_HOST'] . "\n" .
  'HTTP_ACCEPT:          ' . @$_SERVER['HTTP_ACCEPT'] . "\n" .
  'HTTP_ACCEPT_CHARSET:  ' . @$_SERVER['HTTP_ACCEPT_CHARSET'] . "\n" .
  'HTTP_ACCEPT_ENCODING: ' . @$_SERVER['HTTP_ACCEPT_ENCODING'] . "\n" .
  'HTTP_ACCEPT_LANGUAGE: ' . @$_SERVER['HTTP_ACCEPT_LANGUAGE'] . "\n" .
  'HTTP_CACHE_CONTROL:   ' . @$_SERVER['HTTP_CACHE_CONTROL'] . "\n" .
  'HTTP_HOST:            ' . @$_SERVER['HTTP_HOST'] . "\n" .
  'HTTP_VIA:             ' . @$_SERVER['HTTP_VIA'] . "\n" .
  'HTTP_X_FORWARDED_FOR: ' . @$_SERVER['HTTP_X_FORWARDED_FOR']
;

include_once( $this->bootstrap->config['libpath'] . 'clonefish/constants.php');

$config = Array(
  
  'fs1' => array(
    'type'   => 'fieldset',
    'legend' => $l('users', 'login_title'),
    'prefix' => '<span class="legendsubtitle">' . $l('users', 'login_subtitle') . '</span>',
    
  ),
  
  'action' => Array(
    'type'  => 'inputHidden',
    'value' => 'submitlogin'
  ),
  
  'forward' => Array(
    'type'  => 'inputHidden',
    'value' => ( isset( $_REQUEST['forward'] ) ? $_REQUEST['forward'] : '' )
  ),
  
  'welcome' => Array(
    'type'  => 'inputHidden',
    'value' => ( isset( $_REQUEST['welcome'] ) ? $_REQUEST['welcome'] : '' )
  ),
  
  'email' => Array(
    'displayname' => $l('users', 'email'),
    'type'        => 'inputText',
    'validation'  => Array(
      Array( 'type' => 'string', 'regexp' => CF_EMAIL, 'help' => $l('users', 'emailhelp') ),
    )
  ),
  
  'password' => Array(
    'displayname' => $l('users', 'password'),
    'type'        => 'inputPassword',
    'validation' => Array(
      Array( 'type' => 'required' ),
    )
  ),
  
  'diagnostics' => Array(
    'type'  => 'text',
    'value' => '

    <textarea style="display: none" name="diaginfo" id="diaginfo">' .
        'Browser:' . "\n" .
        @$_SERVER['HTTP_USER_AGENT'] . "\n" .
        "\n" .
        'JavaScript:           DISABLED' . "\n" .
        $httpInfo .
    '</textarea>

    <script type="text/javascript">
      var flashVersion     = swfobject.getFlashPlayerVersion(); 
      var flashVersionText = 
        flashVersion.major + "." + 
        flashVersion.minor + "." + 
        flashVersion.release
      ;

      $j("#diaginfo").val(
        \'Browser:\n' . @$_SERVER['HTTP_USER_AGENT'] . '\n\n\' +
        \'JavaScript:           ENABLED\n\' + 
        \'Flash version:        \' + flashVersionText + \'\n\' +
        \'' .
        // create a multiline JS expression with quotes as needed
        str_replace(
          "\n",
          '\n\' + ' . "\n          " . '\'',
          addslashes( $httpInfo )
        ) . '\'
      );
    </script>
  '
  )

);
