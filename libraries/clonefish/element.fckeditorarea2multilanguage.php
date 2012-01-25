<?php

if ( !isset( $GLOBALS['FCKeditorBasePath'] ) ) 
  $GLOBALS['FCKeditorBasePath'] = BASE_URI . 'rich/';
include_once('rich/fckeditor.php');

class fckeditorarea2MultiLanguage extends Element {

  var $languages = Array();
  var $values = Array();
  var $value;
  var $width  = 700;
  var $height = 300;
  var $configpath;

  // -------------------------------------------------------------------------   
  function getHTML() {

    $out = '';

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

      $oFCKeditor = new FCKeditor( 'strings[' . $this->name . '][' . $languageid . ']' );
      $oFCKeditor->BasePath = $GLOBALS['FCKeditorBasePath'];
      
      if ( $this->configpath )
        $oFCKeditor->Config['CustomConfigurationsPath'] = $this->configpath;
      else
        $oFCKeditor->Config['CustomConfigurationsPath'] =
         dirname( $GLOBALS['FCKeditorBasePath'] ) . '/richconfig/config.js';

      $oFCKeditor->Value = @$this->values[ $languageid ];

  //    $oFCKeditor->CanUpload = false ;	// Overrides fck_config.js default configuration
  //    $oFCKeditor->CanBrowse = false ;	// Overrides fck_config.js default configuration

      $oFCKeditor->Width    = $this->width;
      $oFCKeditor->Height   = $this->height;
      
      $out .= 
         '<b>' . $language . '</b><br />'.
         $oFCKeditor->CreateHTML() .
         "<br><br>";
    }

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