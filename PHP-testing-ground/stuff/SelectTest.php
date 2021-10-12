<?php

/**
 * Testing select() function to switch between subarrays and access variables trough
 * the selected sub-array passed by reference.
 */
class SelectTest {
  const A = 'A';
  const B = 'B';

  private $selected = '';
  public $data = [
    self::A => [],
    self::B => [],
  ];
  public $current = [];

  function select($stuff) {
    switch ($stuff) {
      case self::A:
        $this->selected = self::A;
        break;
      
      case self::B:
        $this->selected = self::B;
        break;
      
      default:
    }
    // "current" was passed by reference, so "data" folows it:
    $this->current = &$this->data[$this->selected];
  }

  function addStuff($stuff) {
    // test inside the class
    $this->current[] = $stuff;
  }
}

$t = new SelectTest();
$t->data[SelectTest::A] = [ "DATA_A" ];
$t->data[SelectTest::B] = [ "DATA_B" ];

// test inside the class:
$t->select(SelectTest::B);
$t->addStuff("STUFF"); // "STUFF" is added to data['B']

// test outside the class:
$t->current[] = "APPUL"; // string is also added to data['B'] too
var_export($t);

// test after switching to the other sub-array:
$t->select(SelectTest::A);
$t->addStuff("STUFF"); // "STUFF" is now added to data['A']
var_dump($t);
