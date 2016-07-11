<?php
namespace Visitor\Users\Form;
class Login extends \Visitor\Form {
  public $configfile = 'Login.php';
  public $template = 'Visitor/genericform.tpl';
  public $xsrfprotect = false; // hogy mukodjon a fooldali gyors belepes

  public function postSetupForm() {

    $l = $this->bootstrap->getLocalization();
    // ugyanaz submitnak mint title-nek
    $this->form->submit =
    $this->controller->toSmarty['title'] = $l('users', 'login_title');
    if ( $this->application->getParameter('nolayout') )
      $this->controller->toSmarty['nolayout'] = true;

    $this->controller->toSmarty['formclass'] = 'halfbox centerformwrap';
    $this->controller->toSmarty['titleclass'] = 'center';
    parent::postSetupForm();

  }

  public function onComplete() {

    $crypto         = $this->bootstrap->getEncryption();
    $values         = $this->form->getElementValues( 0 );
    $userModel      = $this->bootstrap->getModel('users');
    $organizationid = $this->controller->organization['id'];
    $access         = $this->bootstrap->getSession('recordingaccess');
    $autologinAllowed = true;

    $uservalid = $userModel->selectAndCheckUserValid(
      $organizationid,
      $values['email'],
      $values['password']
    );

    if ( $uservalid !== true ) {
      $uservalid = $this->handleAuthTypes();
      $autologinAllowed = false;
    }

    // single login location check
    $sessionvalid =
      $uservalid === true &&
      $userModel->checkSingleLoginUsers()
    ;

    if ( $uservalid !== true or !$sessionvalid ) {

      $l            = $this->bootstrap->getLocalization();
      $lang         = \Springboard\Language::get();
      $encodedemail = rawurlencode( $values['email'] );

      if ( !$uservalid or $uservalid == 'organizationinvalid' )
        $message = sprintf(
          $l('users','login_error'),
          $lang . '/users/forgotpassword?email=' . $encodedemail,
          $lang . '/users/resend?email=' . $encodedemail
        );
      elseif ( $uservalid == 'expired' )
        $message = $l('users', 'timestampdisabled');
      elseif ( !$sessionvalid )
        $message = sprintf(
          $l('users','login_sessionerror'),
          ceil( $this->bootstrap->config['sessiontimeout'] / 60 ),
          $lang . '/users/resetsession?email=' . $encodedemail
        );

      $this->form->addMessage( $message );
      $this->form->invalidate();
      return;

    }

    $access->clear();
    $userModel->registerForSession();
    $userModel->updateSessionInformation();
    $this->controller->toSmarty['member'] = $userModel->row;

    $diagnostics = '(diag information was not posted)';
    if ( $this->application->getParameter('diaginfo') )
      $diagnostics = $this->application->getParameter('diaginfo');

    $userModel->updateLastlogin(
      $diagnostics,
      $this->controller->getIPAddress(true)
    );
    $this->controller->logUserLogin('LOGIN');
    $forward = $this->application->getParameter('forward');

    if ( $autologinAllowed and $values['autologin'] )
      $userModel->setAutoLoginCookie( $this->bootstrap->ssl );

    if ( strpos( $forward, 'users/login' ) !== false ) {
      $forward = '';
      $values['welcome'] = true;
    }

    if ( $values['welcome'] )
      $this->controller->redirect('users/welcome', array(
          'forward' => $forward
        )
      );
    else
      $this->controller->redirect( $forward );

  }

  private function handleAuthTypes() {
    $organization = $this->controller->organization;
    if ( empty( $organization['authtypes'] ) )
      return false;

    $ipaddresses = $this->controller->getIPAddress(true);
    foreach( $organization['authtypes'] as $authtype ) {
      if ( $authtype['type'] === 'local' or !$authtype['isuserinitiated'] )
        continue;

      $class = "\\AuthTypes\\" . ucfirst( strtolower( $authtype['type'] ) );
      $auth = new $class( $this->bootstrap, $this->organization, $ipaddresses );

      try {

        $ret = $auth->handleForm( $authtype, $this->form );
        if ( $ret === true ) {
          $user = $this->bootstrap->getSession('user');
          $this->controller->toSmarty['member'] = $user->toArray();
          $this->controller->logUserLogin(
            $authtype['type'] . '-USERINIT-LOGIN'
          );
        }

        if ( $ret !== null )
          return true;

      } catch( \AuthTypes\Exception $e ) {

        $d    = \Springboard\Debug::getInstance();
        $line =
          $e->getMessage() . "\n" .
          var_export( $e->info, true ) . "\n" .
          \Springboard\Debug::formatBacktrace( $e->getTrace() ) . "\n"
        ;
        $d->log(false, 'ssologin.txt', $line);

        if ($e->redirectmessage)
          $this->controller->redirectWithMessage(
            $e->redirecturl,
            $e->redirectmessage,
            $e->redirectparams
          );
        else
          $this->controller->redirect(
            $e->redirecturl,
            $e->redirectparams
          );
      }
    }

    // ha ide eljutottunk nem lett handle-elve
    return false;
  }

}
