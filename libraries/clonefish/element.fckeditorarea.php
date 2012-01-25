<?php

if ( !isset( $GLOBALS['FCKeditorBasePath'] ) ) 
  $GLOBALS['FCKeditorBasePath'] = BASE_URI . 'rich/';
include_once('rich/fckeditor.php');

class fckeditorarea extends Element {

  var $width  = 700;
  var $height = 300;

  // -------------------------------------------------------------------------
  function getHTML() {

    $oFCKeditor = new FCKeditor ;
    $oFCKeditor->Value = $this->value;

//    $oFCKeditor->CanUpload = false ;	// Overrides fck_config.js default configuration
//    $oFCKeditor->CanBrowse = false ;	// Overrides fck_config.js default configuration
    return 
      $oFCKeditor->ReturnFCKeditor( $this->getname() , $this->width, $this->height ) ;
  }

}

?>