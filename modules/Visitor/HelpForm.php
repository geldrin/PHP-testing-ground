<?php
namespace Visitor;

class HelpForm extends \Visitor\Form {
  
  public function displayForm() {
    
    $helpkey   = $this->module . '_' . str_replace('submit', '', $this->action );
    $helpModel = $this->bootstrap->getModel('help_contents');
    $helpModel->addFilter('shortname', $helpkey, false, false );
    
    $this->toSmarty['help'] = $helpModel->getRow();
    
    parent::displayForm();
    
  }
  
}
