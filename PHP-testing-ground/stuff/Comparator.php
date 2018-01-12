<?php

/**
 * 
 * Playing with changeable comparison methods via "Comparator" class.
 * 
 */

$arr = [213, 45, 23,46, 767, 234, 2, 45, 123, 21, 4, 5];
usort($arr, myComparator::getComparator());
var_dump($arr); // returns a sorted array

//-----------------

/**
 * Comparator skeleton.
 */
abstract class Comparator {
  /**
   * Returns a function that can be used in sorting functions, such as usort, uasort, etc.
   */
  public static function getComparator(): callable {}
}

/**
 * Custom comparator class.
 */
class myComparator {
  public static function getComparator(): callable {
    return (function($a, $b):int {
      return $a <=> $b;
    });
  }
}
