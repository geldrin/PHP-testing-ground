<?php

/**
 * A lightweight array-access function, which lets you use a dot separated
 * string to access array params.
 *
 * @param array  $dat Input array.
 * @param string $fld The path to array element. Array keys are to be separated with a dot ('.') character.
 */
/*function getFields($dat, $fld) {
  if (!is_array($fld)) { $fld = explode('.', $fld); }
  $current_level = array_shift($fld);
  
  if (empty($fld)) {
    if (is_array($dat)) {
      return ($dat)[$current_level]);
    } else {
      return $dat;
    }
  } else {
    return (getFields($dat[$current_level], $fld));
  }
}*/

// getFields - lambda version
$getFields = function($dat, $fld) use (&$getFields) {
  if (!is_array($fld)) { $fld = explode('.', $fld); }
  $current_level = array_shift($fld);

  if (empty($fld)) {
      if (is_array($dat)) { return ($dat[$current_level]); } else { return $dat;}
  } else {
      return ($getFields($dat[$current_level], $fld));
  }
};

// TESTS -------------------------------------------------

$testArray = [
  'a' =>
  [
    'b' =>
    [
      'c' =>
      [
        'd' => 'APPUL',
      ],
      'best' => 'TWIGGLE',
    ]
  ],
  0 => [ 1 => [ 2 => 'TWO' ]]
];

var_dump($getFields($testArray, 'a.b.c.d'));  // "APPUL"
var_dump($getFields($testArray, 'a.b.c.d.')); // "APPUL"
var_dump($getFields($testArray, 'a.b.best')); // "TWIGGLE"
var_dump($getFields($testArray, 'a.b.d'));    // "NULL" (PHP Notice: Undefined index: d)
var_dump($getFields($testArray, ''));         // "NULL" (PHP Notice: Undefined index:  )
var_dump($getFields($testArray, 0));          // "array(1)"
var_dump($getFields($testArray, '0.1.2'));    // "TWO"
