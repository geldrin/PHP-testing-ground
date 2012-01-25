<?php

class textareaMultiLanguage extends Element {

  var $languages = Array();
  var $values = Array();
  var $value;

  // -------------------------------------------------------------------------   
  function getHTML() {

    $out = '';

    if ( is_numeric( $this->value ) && ( $this->value > 0 ) ) {

      $rs = $this->form->db->execute("
        SELECT 
          *
        FROM 
          strings
        WHERE 
          translationof = '" . $this->value . "'
      ");

      $dbvalues = Array();
      while ( !$rs->EOF ) {
        $dbvalues[ $rs->fields['language'] ] = $rs->fields['value'];
        $rs->moveNext();
      }

      $this->values = $dbvalues;

    }

    foreach ( $this->languages as $languageid => $language ) {
      $out .= 
         '<label for="' . $this->_gethtmlid() . $languageid . '">' . $language . ':</label><br />'.
         '<textarea ' .
           $this->html . ' ' .
           'name="strings[' . $this->name . '][' . $languageid . ']" ' .
           'id="' . $this->_gethtmlid() . $languageid . '">' .
           htmlspecialchars( @$this->values[ $languageid ] ) . 
         '</textarea><br>';
    }

    return 
      
         '<input ' .
           'type="hidden" ' .
           'name="' . $this->name . '" ' .
           'id="' . $this->_gethtmlid() . '" ' .
           'value="' . htmlspecialchars( @$this->value ) . '" ' . 
         ' />'.
        $out;

  }

}

?>