<?php

class tinyMCEMultiLanguage extends Element {

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
    $out = '';
    
    // make sure we load tinymce only once, as it will bug out if loaded multiple times
    // also, dont load anything if jspath is not set
    if ( !@$GLOBALS['cf_fckeditorinjected'] and $this->jspath ) {
      
      $out .= '<script language="javascript" type="text/javascript" src="' . htmlspecialchars( $this->jspath ) . '"></script>';
      $GLOBALS['cf_fckeditorinjected'] = true;
      
    }
    
    if ( is_numeric( $this->value ) && ( $this->value > 0 ) ) {

      $rs = $this->form->db->execute("
        SELECT *
        FROM strings
        WHERE 
          translationof = " . $this->value . "
      ");

      $dbvalues = Array();
      while ( !$rs->EOF ) {
        $dbvalues[ $rs->fields['language'] ] = $rs->fields['value'];
        $rs->moveNext();
      }

      $this->values = $dbvalues;

    }

    foreach ( $this->languages as $languageid => $language ) {
      
      $ids[ $languageid ] = $this->_gethtmlid() . '_' . $languageid;
      
      $out .=
        '<b>' . $language . '</b><br />' .
        '<textarea id="' . $ids[ $languageid ] . '"' .
        ' name="' . 'strings[' . $this->name . '][' . $languageid . ']' . '">' .
        htmlspecialchars( @$this->values[ $languageid ] ) .
        '</textarea><br/><br/>';
      
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
      '</script>';

    return 
      
      '<input ' .
        'type="hidden" ' .
        'name="' . $this->name . '" ' .
        'id="' . $this->_gethtmlid() . '" ' .
        'value="' . htmlspecialchars( @$this->value ) . '" ' . 
      ' />'.
      $out
    ;

  }

}

?>