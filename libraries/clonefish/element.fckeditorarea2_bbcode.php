<?php

include_once( CLASSPATH . 'fckeditor/fckeditor.php');

class fckeditorarea2_bbcode extends Element {

  var $width  = 700;
  var $height = 300;
  var $path;

  // -------------------------------------------------------------------------
  function fckeditorarea2_bbcode( $name, $configvalues ) {
    
    Element::Element( $name, $configvalues );

    if ( !strlen( $this->path ) )
      if ( !isset( $GLOBALS['FCKeditorBasePath'] ) )
        die( 'fckeditorarea2_bbcode: path not set');
      else
        $this->path = $GLOBALS['FCKeditorBasePath'];

  }

  // -------------------------------------------------------------------------
  function gethtml() {

    // FCKEditor 2.x

    $oFCKeditor           = new FCKeditor( $this->name );
    $oFCKeditor->BasePath = $this->path;

    // we assume there's a custom config file in the outer directory
    // of FCK path - set to your own taste, or feel free to remove
    $oFCKeditor->Config['CustomConfigurationsPath'] =
       dirname( $this->path ) . '/fckconfig/config.js';

    $oFCKeditor->Value    = $this->value;
    $oFCKeditor->Width    = $this->width;
    $oFCKeditor->Height   = $this->height;

    return 
      $oFCKeditor->CreateHTML() ;

  }

}

?>