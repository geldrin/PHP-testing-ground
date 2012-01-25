<?php

class inputTextMultiLanguage extends Element {

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
      $out .= 
       '<tr>'.
         '<td><label for="' . $this->_gethtmlid() . $languageid . '">' . $language . '</label></td>'.
         '<td>' .
         '<input ' .
           $this->html . ' ' .
           'type="text" ' .
           'name="strings[' . $this->name . '][' . $languageid . ']" ' .
           'id="strings[' . $this->_gethtmlid() . '][' . $languageid . ']" ' .
           'value="' . htmlspecialchars( @$this->values[ $languageid ] ) . '" ' . 
         ' />'.
         '</td>'.
       '</tr>';
    }

    return 
      
         '<input ' .
           'type="hidden" ' .
           'name="' . $this->name . '" ' .
           'id="' . $this->_gethtmlid() . '" ' .
           'value="' . htmlspecialchars( @$this->value ) . '" ' . 
         ' />'.
      '<table>' . 
        $out . 
      '</table>';

  }

}

?>