<?php
namespace Admin\Login;

class Form extends \Springboard\Controller\Admin\Form {
  
  public function route() {
    
    switch( $this->action ) {
      case 'index':
        $this->loadConfig();
        $this->preSetupForm();
        $this->form = $this->bootstrap->getForm( $this->action );
        $this->form->addElements( $this->config, null, false );
        $this->indexAction();
        break;
      case 'login':
        $this->loadConfig();
        $this->preSetupForm();
        $this->form = $this->bootstrap->getForm( $this->action );
        $this->form->addElements( $this->config, $this->application->getParameters(), false );
        $this->loginAction();
        break;
      default:
        $this->handleNotFound();
        break;
      
    }
    
  }
  
  protected function indexAction() {
    $this->controller->toSmarty['bareheading'] = true;
    $this->controller->toSmarty['form']        = $this->form->getHTML();
    $this->controller->smartyoutput('Admin/login.tpl');
  }
  
  protected function loginAction() {
    
    if ( $this->form->validate() ) {
      
      $values = $this->form->getElementValues(false);
      $users  = $this->bootstrap->getModel('users');
      
      if ( $users->selectAndCheckUserValid( null, $values['email'], $values['password'], true ) ) {
        
        $users->registerForSession('adminuser');
        
        if ( $forward = $this->application->getParameter('forward') )
          $this->controller->redirect( $forward );
        else
          $this->controller->redirect('index');
        
      } else {
        
        $l = $this->bootstrap->getLocalization();
        $this->form->addMessage( $l('admin', 'login_failed') );
        $this->form->invalidate();
        
      }
      
    }
    
    $this->controller->toSmarty['form'] = $this->form->getHTML();
    $this->controller->smartyoutput('Admin/login.tpl');
    
  }
  
  protected function handleXSRFIndex() {
    
    switch( $this->action ) {
      
      case 'login':
        $this->submitted = true; // FALLTHROUGH
      case 'index':
        $action = 'login';
        break;
      
    }
    
    $index  = sprintf('admin_%s_%s', $this->module, $action );
    return $index;
    
  }
  
}
