<?php

/**
 * 
 * Init and call methods of the same object in one expression.
 * 
 */

$a = $b = $c = null;

// easy mode
$a = new Appul();   // instantiate
$a->boop = "Heya!"; // set variable
$a->greet();        // call method

// hard mode (all in one expression)
$b = Appul::getInstance()->set("Howdy, partner!")->greet(); // PHP 5.6+ (needs an extra class returning an instance)
($b = new Appul())->set("Howdy, partner!")->greet();        // PHP 7.0+

// hard mode #2
// FATAL ERROR (Cannot use temporary expression in write context) at least one setter method needed.
//($c = new Appul())->$boop = "Bye!";
//$c->greet();

echo "---------\n";
var_dump($a,$b,$c);

/**
 * test class
 */
class Appul {
    public $boop = "";
    
    static function getInstance() { return new Appul(); } // extra class needed for PHP 5.6 above
    function set($s) { $this->boop = $s; return $this; }
    function greet() { print_r("{$this->boop}\n"); return $this; } // return can be omitted in PHP 7.0+
}
