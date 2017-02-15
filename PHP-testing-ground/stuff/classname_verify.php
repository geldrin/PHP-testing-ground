<?php

$test = [
  'Pon'          => 'Pon',
  'Appul'        => 'Appul',
  'Pon object'   => new Pon(),
  'Appul object' => new Appul(),
  'Object'       => 'object',
];

$a = new Appul();
$a->test = $test;
$a->existentialCrysis();

echo "-----\n";

$b = new Pon();
$b->test = $test;
$b->existentialCrysis();


// ------------------------

/**
 * main class
 */
class Pon {
  protected $yay = "yay";
  protected $nay = "nay";
  public $test = [];
  
  /**
   * test instanceof() method with supplied testcases
   */
  public function existentialCrysis() {
    $myclass = get_class($this);
    print_r("Testing '{$myclass}' object:\n");

    foreach ($this->test as $k => $testcase) {
      print_r("instance of {$k}? ");

      try {
        if ($this instanceof $testcase) {
          print_r($this->yay);
        } else {
          print_r($this->nay);
        }
      } catch (Exception $e) {
        print_r("fugg :DD.DDD\n". $e->getMessage());
      }
      print_r(PHP_EOL);
    }
  }
}

/*
 * sub-class
 */
class Appul extends Pon {
  var $yay = "yeehah";
  var $nay = "aw shucks";
}
