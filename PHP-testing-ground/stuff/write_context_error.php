<?php
/**
 * Context write error presentation
 */

$r = new A();
$r->setValue("Something");

if (!empty($r->getValue())) { // throws fatal error with <PHP5.5
//if ($r->getError()) { // backward compatible
  echo "err";
} else {
  echo "ok";
}

///////////////////////////////////////////////////////////////////
class A
{
  private $error;
  
  public function setValue($err = null) { $this->error = $err; }
  public function getValue() { return $this->error; }
}
