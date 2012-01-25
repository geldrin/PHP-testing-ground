<?php
namespace Visitor\Users\Form;
class Login extends \Visitor\Form {
  public $configfile = 'Login.php';
  public $template = 'Visitor/Users/Login.tpl';
  
  public function postSetupForm() {
    
    $l = $this->bootstrap->getLocale();
    $this->toSmarty['title'] = $l('users', 'login_title');
    
  }
  
  public function onComplete() {
    echo 'VALID AND SUBMITTED';
  }
  
}
