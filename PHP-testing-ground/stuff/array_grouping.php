<?php

/* 
 * Array grouping and filtering test
 * (using high-order functions, without classic iterators)
 * 
 */

$t = [
  [ 'name' => 'Appul', 'type' => 'mud' ],
  [ 'name' => 'Ponka', 'type' => 'mud' ],
  [ 'name' => 'Banana', 'type' => 'fancy' ],
  [ 'type' => null ],
  [ 'some_garbage' ],
]; // test array

echo ">> FILTER BY 'type=mud':", PHP_EOL;
var_dump(filterBy($t, 'type', 'mud'));

echo ">> GROUP BY 'type':", PHP_EOL;
var_dump(groupBy($t, 'type'));
die();

//----------------

/**
 * Filter sub-arrays with specific type.
 * Check performed by loose comparison.
 * 
 * @param array $array Array to be filtered.
 * @param int|string $by The array key on which the comparisons should be performed.
 * @param mixed $value The value that the types should be matched against.
 * @return array The filtered array.
 */
function filterBy($array, $by, $value) {
  if (!is_array($array)) { return null; }
  
  return array_filter($array,
    function($item) use ($by, $value) {
      if (array_key_exists($by, $item) && $item[$by] == $value) { return true; }
    }
  );
}


/**
 * Group by type.
 * 
 * @param array $array Array to be grouped.
 * @param int|string $by The array key on which the comparisons should be performed.
 * @return boolean|array The grouped array, or FALSE on error.
 */
function groupBy($array, $by) {
  if (!is_array($array)) { return null; }

  $grouped = [];
  $_collect =
    function ($val, $key, $by) use (&$grouped) {
      if (is_array($val) && array_key_exists($by, $val)) {
        $type = $val[$by];
        if (!$type) { return; }
        if (isset($grouped[$type])) { $grouped[$type][] = $val; }
        else { $grouped[$type] = [$val]; }
      }
    };

  if (array_walk($array, $_collect, $by)) { return $grouped; }
  return false;
}

// very, very bad english here
