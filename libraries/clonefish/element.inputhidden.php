<?php

/**
 * Clonefish form generator class 
 * (c) phpformclass.com, Dots Amazing
 * All rights reserved.
 * 
 * @copyright  2010 Dots Amazing
 * @link       http://phpformclass.com
 * @package    clonefish
 * @subpackage elements
 */

/* 
 * Element
 * @package clonefish
 * @subpackage elements
 */
class inputHidden extends element {

  var $rowlayout = '%prefix%%element%%postfix%';

  // -------------------------------------------------------------------------
  function getHTML() {
    return 
      '<input ' .
        'type="hidden" ' .
        'id="' . $this->_getHTMLId() . '" ' .
        'name="' . $this->name . '" ' .
        'value="' . htmlspecialchars( $this->value ) . '" ' . 
        $this->html . 
      ' />' . "\n";
  }

}

?>