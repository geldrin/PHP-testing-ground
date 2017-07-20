<?php

/**
 * Testing the cases where "__get()" magic method fires.
 */

class A {
    private $food    = 'Apples';
    private $snootle = null;
    
    public function __construct() { $this->snootle = new B(); }
    public function __get($key) {
        echo "GETTIN {$this->$key}!\n";
        return $this->$key;
    }
    public function getFood() { return $this->food; }
}

class B {
    public function boop() { echo "HEY!\n"; }
    public function __toString() { return "Snootle"; }
}

//-----------------------
$appul = new A();
$a = $appul->food;
$appul->food;            // Works too, assignment is not a requirement!!
$appul->snootle->boop(); // Works even if you access a property's property trough __get()
$b = $appul->getFood();  // Doesn't fires __get() !!

// Prints:
//
//  GETTIN Apples!
//  GETTIN Apples!
//  GETTIN Snootle!
//  HEY!
