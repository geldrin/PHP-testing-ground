<?php
namespace Visitor\Users\Form;
class Login extends \Springboard\Controller\Form {
  public $configfile = 'Signup.php';
  public $template = 'Visitor/Users/Login.tpl';
  
  public function onComplete() {
    echo 'VALID AND SUBMITTED';
  }
  
}
