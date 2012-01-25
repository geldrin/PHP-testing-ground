<?php

class tinyMCE extends Element {
  
  var $value;
  var $jspath;
  var $config         = Array();
  var $width          = 700;
  var $height         = 300;
  var $jsinitfunction = 'tinyMCE.init';

  // -------------------------------------------------------------------------   
  function getHTML() {

    $out = '<textarea ' . 
        'id="' . $this->_getHTMLId() . '" ' . 
        'name="' . $this->name . '" ' .
        $this->html .'>' .
      htmlspecialchars( $this->value ) . 
    '</textarea>';
    
    // make sure we load tinymce only once, as it will bug out if loaded multiple times
    // also, dont load anything if jspath is not set
    if ( !@$GLOBALS['cf_fckeditorinjected'] and $this->jspath ) {
      
      $out .= '<script language="javascript" type="text/javascript" src="' . htmlspecialchars( $this->jspath ) . '"></script>';
      $GLOBALS['cf_fckeditorinjected'] = true;
      
    }
    
    if ( @$this->config['width'] === null )
      $this->config['width'] = $this->width . 'px';
    
    if ( @$this->config['height'] === null )
      $this->config['height'] = $this->height . 'px';
    
    $this->config['mode']     = 'exact';
    $this->config['elements'] = $this->_getHTMLId();
    
    $configjson = json_encode( $this->config ); // php5.3 -> json_encode( $this->config, JSON_HEX_TAG )
    $configjson = str_replace( '<', '\\u003C', $configjson );
    $configjson = str_replace( '>', '\\u003E', $configjson );
    
    $out .=
      '<script language="javascript" type="text/javascript">' .
      $this->jsinitfunction . '(' . $configjson . ');' .
      '</script>';

    return $out;

  }

}
