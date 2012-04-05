<?php
include_once('element.multilanguage.php');

class inputTextMultiLanguage2 extends MultiLanguageElement {

  // -------------------------------------------------------------------------   
  function getHTML() {

    $out =
      '<input ' .
        'type="hidden" ' .
        'name="' . $this->name . '" ' .
        'id="' . $this->_gethtmlid() . '" ' .
        'value="' . $this->htmlspecialchars( @$this->value ) . '" ' . 
      ' />'
    ;
    
    $this->fillValuesFromDatabase();
    
    foreach ( $this->languages as $languageid => $language ) {
      
      $id      = $this->_gethtmlid() . '_' . $languageid;
      $replace = Array(
        '%label%' => '<label for="' . $id . '">' . $language . $this->labelpostfix . '</label>',
        '%input%' => 
          '<input id="' . $id . '" ' . $this->html . ' type="text" ' .
            'name="strings[' . $this->name . '][' . $languageid . ']" ' .
            'value="' . $this->htmlspecialchars( @$this->values[ $languageid ] ) . '" ' .
          ' />'
      );
      
      $out .= strtr( $this->itemlayout, $replace );
      
    }
    
    $replace = Array(
      '%rows%' => $out,
    );

    return strtr( $this->layout, $replace );

  }
  
}
