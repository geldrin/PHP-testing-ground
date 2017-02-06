<?php

class A {
  private static $a = 'appul';
  
  public function __construct() { return $this; }
  
  public static function getInstance() { return(new A()); }
  
  /**
   * calls anonymus function via call_user_func()
   */
  public static function call_test1() {
    var_dump(
      call_user_func(
        function() {
          return(self::$a ." is best!\n");
        },
        self::$a
      )
    );
  }
  
  /**
   * calls userfunc() static method via variable extension
   * 
   * neato, huh?
   */
  public static function call_test2() {
    var_dump(self::{'userfunc'}(self::$a));
  }
  
  /*
   * calls userfunc() static method via variable extension (non-static method)
   */
  public function call_test3() {
    var_dump($this->{'userfunc'}(self::$a));
  }
  
  /**
   * @param string $a
   * @return string
   */
  public static function userfunc($a) {
    return "$a is best!";
  }
}

// TEST //
A::call_test1();                // anonymus function test
A::call_test2();                // static method call
A::getInstance()->call_test3(); // non-static method call
