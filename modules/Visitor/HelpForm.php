<?php
namespace Visitor;

class HelpForm extends \Visitor\Form {
  
  public function displayForm() {
    
    $helpkey = $this->module . '_' . str_replace('submit', '', $this->action );
    $cache   = $this->bootstrap->getCache( 'help_' . $helpkey );
    
    if ( $cache->expired() or !PRODUCTION ) {
      
      $helpModel = $this->bootstrap->getModel('help_contents');
      $helpModel->addFilter('shortname', $helpkey, false, false );
      
      $cache->put( $helpModel->getRow() );
      
    }
    
    $this->toSmarty['help'] = $cache->get();
    
    parent::displayForm();
    
  }
  
}
