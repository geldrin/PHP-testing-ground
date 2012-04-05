<?php

class MultilanguageElement extends Element {
  
  var $languages    = Array();
  var $values       = Array();
  var $itemlayout   = '%label%<br/>%input%<br/>';
  var $layout       = '%rows%';
  var $labelpostfix = ':';
  
  function htmlspecialchars( $value ) {
    return htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' );
  }
  
  function fillValuesFromDatabase() {
    
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
    
  }
  
}
