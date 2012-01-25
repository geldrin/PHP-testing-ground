<?php

/**
 * Clonefish form generator class 
 * (c) phpformclass.com, Dots Amazing
 * All rights reserved.
 * 
 * @copyright  2010 Dots Amazing
 * @link       http://phpformclass.com
 * @package    clonefish
 * @subpackage validation
 */

/* 
 * Validation
 * @package clonefish
 * @subpackage validationTypes
 */
class inputRadioRequired extends validation {

  // -------------------------------------------------------------------------
  function getJSCode() {

    $code = '';

    $code .=
      'errors.addIf( \'' . $this->element->_getHTMLId() . '\', ' . 
        $this->getJSValue() . 
      ', "' .
        $this->_jsescape( sprintf( 
          $this->selecthelp( $this->element, CF_STR_REQUIRED_RADIO ), 
          $this->element->getDisplayName() 
        ) ) . 
      '" );'."\n"
      ;

    return $this->injectDependencyJS( $code );

  }

  // -------------------------------------------------------------------------
  function isValid() {

    $results = Array();

    if ( $this->checkDependencyPHP() ) {

    if ( !isset( $this->element->values[ $this->element->getValue( 0 ) ] ) ) {
      $message = 
        sprintf(
          $this->selecthelp( $this->element, CF_STR_REQUIRED_RADIO ),
          $this->element->getDisplayName()
        );
      $results[] = $message;
      $this->element->addMessage( $message );
      }

    }

    return $results;

  }

} 

?>