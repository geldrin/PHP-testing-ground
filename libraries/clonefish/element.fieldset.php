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
class fieldset extends element {
 
  var $legend;
  var $layout = "<fieldset %html% id=\"%id%\">\n<%legendtag%>%legend%</%legendtag%>\n%prefix%%content%%postfix%\n</fieldset>\n";
  var $legendtag = 'legend';
  var $submit = false;
  var $value = false; 
    // $value: counter for elements included in fieldset.
    // when false, every field will be included after the fieldset
    // element until the last element.
    // if set to a number, the given number of elements will be 
    // included in the fieldset.
  var $html;
  var $readonly = 1;

  // --------------------------------------------------------------------------
  function getHTMLRow( $layout, $errorstyle, $showerroricon, $erroricon ) {

    $replace = Array(
      '%html%'    => $this->html,
      '%content%' => '%s',
      '%prefix%'  => $this->prefix,
      '%postfix%' => $this->postfix
    );

    if ( strlen( $this->legend ) ) {
      $replace['%legend%']    = $this->legend;
      $replace['%legendtag%'] = $this->legendtag;
    }
    else {
      $replace['%legend%']       = '';
      $replace['<%legendtag%>']  = '';
      $replace['</%legendtag%>'] = '';
    }

    $out = $this->replacePlaceholders( 
      $layout, $errorstyle, $showerroricon, $erroricon,
      $replace
    );

    return $this->applyElementInjection( 
      $out, $layout, $errorstyle, $showerroricon, $erroricon 
    );

  }

}

?>