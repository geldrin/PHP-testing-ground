<?php

class addcodeValidation extends validation {
 
  var $php;
  var $js;

  // -------------------------------------------------------------------------
  function getJSCode( ) {

    $code = '';
    $fieldvalue = $this->getJSField( $this->element ) . '.value';

    if ( strlen( $this->js ) ) {

      preg_match_all( 
        '/<FORM\.(.+)>/Ums',
        $this->js,
        $templatevars, 
        PREG_SET_ORDER 
      );

      $jstemplate = $this->js;
      foreach ( $templatevars as $match ) {

        $element = $this->form->getElementByName( $match[ 1 ] );
        $replaceto = 
          $this->getJSField( $element ) . '.value';

        $jstemplate = str_replace(
            $match[ 0 ], $replaceto, $jstemplate
        );
      }

      $code .= $jstemplate . "\n";
    
    }

    return $this->injectDependencyJS( $code );

  }

  // -------------------------------------------------------------------------
  function isValid() {

    $results = Array();

    if ( $this->checkDependencyPHP() ) {

      if ( strlen( $this->php ) ) {

        $code = $this->php;

        $code = preg_replace(
          '/<FORM\.(.+)>/Ums',
          '$this->form->getvalue( "\\1", 0 )',
          $code
        );

        eval( $code );

      }

    }

    return $results;

  }

}

?>