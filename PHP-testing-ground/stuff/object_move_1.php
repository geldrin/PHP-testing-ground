<?php

// Object moving test

/**
 * Sample object with one property with additional getter/setter methods
 */
class A {
  protected $name = null;

  public function __construct($name) {
    $this->name = $name;
  }

  public function __get($prop) {
    if (property_exists($this, $prop)) { return $this->$prop; }
  }

  public function __set($prop, $val) {
    if (property_exists($this, $prop)) { $this->$prop = $val; }
  }
}

$arr1 = [];
$arr2 = [];

$arr1[] = new A("apul");   // put new object in array
$tmp = $arr1[0];           // "copy" array instance into a temporary variable
$arr1[0]->name = "APPUL!"; // set object property trough temp var
unset($arr1[0]);           // remove object reference from 1st array
$arr2[] = $tmp;            // put object to the second array

var_dump($arr1, $arr2);    // check results

// Works!
