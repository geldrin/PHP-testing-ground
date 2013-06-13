<?php
namespace Admin\Mailqueue;

class Form extends \Springboard\Controller\Admin\Form {
  
  public function route() {
    
    switch( $this->action ) {
      
      case 'changeform': // FALLTHROUGH
      case 'removeform': // FALLTHROUGH
        $submitted = false;
        $values    = false;
      case 'changemultiple': // FALLTHROUGH
      case 'removemultiple':
        
        if ( !isset( $submitted ) )
          $submitted = true;
        
        $this->controller->loadConfigFile( $this->controller->configfile );
        $this->loadConfig();
        
        $this->form = $this->getForm( $this->action );
        $action     = $this->action . 'Action';
        
        if ( !isset( $values ) )
          $values = $this->preAddElements( $this->action, $this->application->getParameters() );
        
        $this->form->addElements( $this->config, $values, false );
        
        if ( $submitted and $this->form->validate() )
          $this->$action();
        
        $this->controller->toSmarty['hidenavigation'] = false;
        $this->displayForm( $submitted );
        break;
      
      default:
        return parent::route();
        break;
      
    }
    
  }
  
  public function loadConfig() {
    
    $this->configfile  =
      $this->application->config['modulepath'] . 'Admin/Configs/'
    ;
    
    if ( in_array( $this->action, array('changeform', 'changemultiple') ) )
      $this->configfile .= 'mailqueue_change.php';
    elseif ( in_array( $this->action, array('removeform', 'removemultiple') ) )
      $this->configfile .= 'mailqueue_remove.php';
    
    parent::loadConfig();
    
  }
  
  public function removemultipleAction() {
    $values     = $this->form->getElementValues( false );
    $queueModel = $this->bootstrap->getModel('mailqueue');
    $queueModel->remove( $values );
    $this->controller->redirect(
      'mailqueue/index'
    );
  }
  
  public function changemultipleAction() {
    $values     = $this->form->getElementValues( false );
    $queueModel = $this->bootstrap->getModel('mailqueue');
    $queueModel->change( $values );
    $this->controller->redirect(
      'mailqueue/index'
    );
  }
  
}
