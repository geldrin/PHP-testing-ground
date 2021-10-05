<?php

/**
 * Recursively walks trough the provided multidimensional associative array, and puts the path of
 * all leaf-objects into the "$result" array.
 * 
 * Usage example:
 * 
 * <code>
 *   $input = [1 => [2 => [3 => ['END']]]];<br />
 *   $flattened = flatten($input);<br />
 *   print_r(implode('.', end($flattened)));  // will print "1.2.3.END"<br />
 * </code>
 * 
 * @param array $arr Multidimensionarray to be flattened.
 * @param array $_result Result carry variable passed between recursive cycles. Results are written back into this variable.
 * @param array $_path Path carry variable passed between recursive cycles.
 * 
 * @return array Returns the path of each leaf objects in a two dimensional array.
 */
function flatten(array $arr, array $_result = [], array $_path = []): array {
  if (!is_array($_result)) { $_result = []; }
  if (!is_array($_path)) { $_path = []; }
  if (is_array($arr)) {
    if (count($arr)) {
      foreach ($arr as $name => $item) {
        $tmp = $_path;
        if (!is_array($item)) {
          // non-array leaf objects
          $_result[] = array_merge($_path, [ $item ]);
        } elseif (count($item)) {
          // branch arrays
          array_push($tmp, $name);
          $_result = flatten($item, $_result, $tmp);
        } else {
          // array leaf objects
          $_result[] = array_merge($_path, [ $name ]);
        }
      }
    }
  } else {
    $_path[]      = $arr;
    $_result[] = $_path;
  }
  return $_result;
}

// To simulate a filesystem, each array represents a folder, while 
$testarray = [
  'folder1' => [
    'appul.txt',
    'banana.txt'
  ],
  'folder2' => [
    'subfolder2-1' => [
      'subfolder2-1-1' => [],
      'orange.txt',
    ],
    'subfolder2-2' => [],
    'subfolder2-3' => [
      'subfolder2-3-1' => [],
    ],
  ],
];

$expected = [
    ['folder1','appul.txt'],
    ['folder1', 'banana.txt'],
    ['folder2', 'subfolder2-1', 'subfolder2-1-1'],
    ['folder2', 'subfolder2-1', 'orange.txt'],
    ['folder2', 'subfolder2-2'],
    ['folder2', 'subfolder2-3', 'subfolder2-3-1'],
];


//// TEST #1
$result = flatten($testarray);

print_r("RESULT = ". var_export(($result == $expected), 1) ."\n."); // will print "true" as the two arrays will be similar
var_dump($result);
