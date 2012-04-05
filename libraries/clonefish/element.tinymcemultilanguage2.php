<?php
include_once('element.multilanguage.php');

class tinyMCEMultiLanguage2 extends MultiLanguageElement {

  var $languages      = Array();
  var $values         = Array();
  var $config         = Array();
  var $value;
  var $width          = 700;
  var $height         = 300;
  var $jspath         = 'js/tiny_mce/tiny_mce.js';
  var $jsinitfunction = 'tinyMCE.init';

  // -------------------------------------------------------------------------   
  function getHTML() {

    $ids = array();
    $out =
      '<input ' .
        'type="hidden" ' .
        'name="' . $this->name . '" ' .
        'id="' . $this->_gethtmlid() . '" ' .
        'value="' . $this->htmlspecialchars( @$this->value ) . '" ' . 
      ' />'
    ;
    
    // make sure we load tinymce only once, as it will bug out if loaded multiple times
    // also, dont load anything if jspath is not set
    if ( !@$GLOBALS['cf_fckeditorinjected'] and $this->jspath ) {
      
      $out .= '<script language="javascript" type="text/javascript" src="' . $this->htmlspecialchars( $this->jspath ) . '"></script>';
      $GLOBALS['cf_fckeditorinjected'] = true;
      
    }
    
    $this->fillValuesFromDatabase();
    
    foreach ( $this->languages as $languageid => $language ) {
      
      $ids[ $languageid ] = $this->_gethtmlid() . '_' . $languageid;
      $replace = Array(
        '%label%' => '<label for="' . $ids[ $languageid ] . '">' . $language . $this->labelpostfix . '</label>',
        '%input%' =>
          '<textarea id="' . $ids[ $languageid ] . '" ' . $this->html . ' ' .
          ' name="strings[' . $this->name . '][' . $languageid . ']' . '">' .
          $this->htmlspecialchars( @$this->values[ $languageid ] ) .
          '</textarea>',
      );
      
      $out .= strtr( $this->itemlayout, $replace );
      
    }
    
    if ( @$this->config['width'] === null )
      $this->config['width'] = $this->width . 'px';
    
    if ( @$this->config['height'] === null )
      $this->config['height'] = $this->height . 'px';
    
    $this->config['mode']     = 'exact';
    $this->config['elements'] = implode(',', $ids );
    
    $configjson = json_encode( $this->config );
    $configjson = str_replace( '<', '\\u003C', $configjson );
    $configjson = str_replace( '>', '\\u003E', $configjson );
    
    $out .=
      '<script language="javascript" type="text/javascript">' .
      $this->jsinitfunction . '(' . $configjson . ');' .
      '</script>'
    ;
    
    $replace = Array(
      '%rows%' => $out,
    );
    
    return strtr( $this->layout, $replace );
    
  }

}
