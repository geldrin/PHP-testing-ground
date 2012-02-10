<?php
namespace Visitor;

class Controller extends \Springboard\Controller\Visitor {
  
  public function handleAccessFailure( $permission ) {
    
    if ( $permission == 'member' )
      return parent::handleAccessFailure( $permission );
    
    header('HTTP/1.0 403 Forbidden');
    $this->redirectToController('contents', 'nopermission' . $permission );
    
  }
  
}
