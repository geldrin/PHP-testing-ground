<?php
namespace Visitor\Users\Form;
class Forgotpassword extends \Visitor\HelpForm {
  public $configfile = 'Forgotpassword.php';
  public $template   = 'Visitor/genericform.tpl';
  public $needdb     = true;

  public function init() {

    $user = $this->bootstrap->getSession('user');

    if ( isset( $user['id'] ) )
      $this->controller->redirect('index');

  }

  public function postSetupForm() {

    $l = $this->bootstrap->getLocalization();
    $this->controller->toSmarty['title'] = $l('users', 'forgotpassword_title');

  }

  public function onComplete() {

    $values    = $this->form->getElementValues( 0 );
    $userModel = $this->bootstrap->getModel('users');
    $crypto    = $this->bootstrap->getEncryption();
    $code      = $crypto->randomPassword( 10 );
    $l         = $this->bootstrap->getLocalization();
    $orgid     = $this->controller->organization['id'];

    if ( !$userModel->checkEmailAndUpdateValidationCode( $values['email'], $code, $orgid ) ) {

      $this->form->addMessage( $l('users', 'forgotpassword_error') );
      $this->form->invalidate();
      return;

    }

    if ( $userModel->row['isusergenerated'] ) {

      $this->form->addMessage( $l('users', 'forgotpassword_generror') );
      $this->form->invalidate();
      return;

    }

    $userModel->row['id'] = $crypto->asciiEncrypt( $userModel->row['id'] );
    $this->controller->toSmarty['values'] = $userModel->row;

    $this->controller->sendOrganizationHTMLEmail(
      $userModel->row['email'],
      $l('users', 'forgotpass_emailsubject'),
      $this->controller->fetchSmarty('Visitor/Users/Email/Forgotpassword.tpl')
    );

    $this->controller->redirect('contents/passwordreminder');

  }

}
