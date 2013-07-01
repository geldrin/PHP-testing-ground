<?php
namespace Visitor\Users\Form;
class Changepassword extends \Visitor\Form {
  public $configfile = 'Changepassword.php';
  public $template   = 'Visitor/genericform.tpl';
  public $needdb     = true;
  
  protected $userModel;
  
  public function init() {
    
    $user = $this->bootstrap->getSession('user');
    if ( $user['id'] )
      $this->controller->redirect('index');
    
    $this->userModel = $this->bootstrap->getModel('users');
    $uservalid = $this->userModel->checkIDAndValidationCode(
      $this->application->getParameter('a'),
      $this->application->getParameter('b')
    );
    
    if ( !$uservalid )
      $this->controller->redirect('contents/badparameter');
    
    parent::init();
    
  }
  
  public function postSetupForm() {
    
    $l = $this->bootstrap->getLocalization();
    $this->controller->toSmarty['title'] = $l('users', 'changepassword_title');
    
  }
  
  public function onComplete() {
    
    $values    = $this->form->getElementValues( 0 );
    $l         = $this->bootstrap->getLocalization();
    $access    = $this->bootstrap->getSession('recordingaccess');
    $crypto    = $this->bootstrap->getEncryption();
    
    $this->userModel->updateRow( array(
        'password'       => $crypto->getHash( $values['password'] ),
        'validationcode' => 'validated',
      )
    );
    
    if ( $this->userModel->row['disabled'] == \Model\Users::USER_VALIDATED ) {
      
      $this->userModel->registerForSession();
      $this->userModel->updateLastLogin();
      $access->clear();
      
      $ipaddresses = $this->controller->getIPAddress(true);
      $ipaddress   = '';
      foreach( $ipaddresses as $key => $value )
        $ipaddress .= ' ' . $key . ': ' . $value;
      
      $d = \Springboard\Debug::getInstance();
      $d->log(false, 'login.txt', 'CHANGEPASSWORD LOGIN SESSIONID: ' . session_id() . ' IPADDRESS:' . $ipaddress );
      
    }
    
    $this->controller->redirectWithMessage('index', $l('users', 'changepass_changed') );
    
  }
  
}
