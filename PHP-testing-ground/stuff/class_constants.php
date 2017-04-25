<?php

/**
 *  Dunno
 */

class A {
    const A = "APPUL";
    const B = "POOCHIE";
    const C = "PONKA";
    
    static function test($v = self::A) { var_dump($v); }
}

$a = new A();

A::test();
A::test(A::B);
A::test($a::C);
//A::test($a->C); // DOESN'T WORK!
