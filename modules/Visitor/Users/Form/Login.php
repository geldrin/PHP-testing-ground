<?php
namespace Visitor\Users\Form;
class Login extends \Springboard\Controller\Form {
  public $configfile = 'Login.php';
  public $template = 'Visitor/Users/Login.tpl';
  
  public function onComplete() {
    echo 'VALID AND SUBMITTED';
  }
  
}
