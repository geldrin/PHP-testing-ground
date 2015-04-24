<?php
namespace AuthTypes;

class Exception extends \Exception {
  public $redirecturl;
  public $redirectmessage;
  public $redirectparams;
  public $info = array();
}
