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
class selectRequired extends validation {

  var $form;

  // -------------------------------------------------------------------------
  function selectRequired( $settings, &$element, &$form ) {

    // call parent constructor
    $parent_class_name = get_parent_class( $this );
    $this->$parent_class_name( $settings, $element );
    
    $this->form = &$form;
    
  }

  // -------------------------------------------------------------------------
  function getJSCode( ) {

    $code = 
      'errors.addIf( \'' . $this->element->_getHTMLId() . '\', ' .
        $this->getJSValue() . ' !== false, "' .
        $this->_jsescape( sprintf( 
          $this->selecthelp( $this->element, CF_STR_REQUIRED_SELECT ), 
          $this->element->getDisplayName() 
        ) ) . 
      '" );' . "\n"
    ;

    return $this->injectDependencyJS( $code );

  }

  // -------------------------------------------------------------------------
  function isValid() {

    $results = Array();

    if ( $this->checkDependencyPHP() ) {

    $found     = false;
    $thisvalue = $this->element->getValue( 0 );

    switch ( is_array( $thisvalue ) ) {

      // ARRAY VALUES OF THIS ELEMENT - FOR A MULTIPLE SELECT
      case true:

        foreach ( $thisvalue as $value ) {
          $found =
            $found ||
            $this->element->hasValue( $value );
        }

        break;

      // SINGLE VALUE FOR A PLAIN SELECT
      default:
        $found = $this->element->hasValue( $thisvalue );
        break;

    }

    if ( !$found ) {

      $message = 
        sprintf(
          $this->selecthelp( $this->element, CF_STR_REQUIRED_SELECT ),
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