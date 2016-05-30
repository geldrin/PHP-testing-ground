<?php
namespace Visitor;

class HelpForm extends \Visitor\Form {
  protected function assignHelp() {
    $helpkey   = $this->module . '_' . str_replace('submit', '', $this->action );
    $helpModel = $this->bootstrap->getModel('help_contents');
    $helpModel->addFilter('shortname', $helpkey, false, false );
    
    $this->controller->toSmarty['help'] = $helpModel->getRow();
  }

  public function displayForm( $submitted ) {
    $this->assignHelp();
    parent::displayForm( $submitted );
    
  }
  
}
