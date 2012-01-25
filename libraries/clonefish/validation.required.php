<?php

/**
 * Clonefish form generator class 
 * (c) phpformclass.com, Dots Amazing
 * All rights reserved.
 * 
 * @copyright  2010 Dots Amazing
 * @version    v2, 2010-01-03
 * @link       http://phpformclass.com
 * @package    clonefish
 * @subpackage validation
 */

/* 
 * Validation
 * @package clonefish
 * @subpackage validationTypes
 */
class requiredValidation extends validation {

  // -------------------------------------------------------------------------
  function getJSField( $element ) {
    return $element->getName() . '.value';
  }

} 

?>