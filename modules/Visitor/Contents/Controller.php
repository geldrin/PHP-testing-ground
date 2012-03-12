<?php
namespace Visitor\Contents;

class Controller extends \Visitor\Controller {
  
  public function route() {
    // TODO caching?
    $contentsModel = $this->bootstrap->getModel('contents');
    $smarty        = $this->bootstrap->getSmarty();
    $language      = \Springboard\Language::get();
    
    $content = $contentsModel->getContent( $this->action, $language );
    
    if ( empty( $content ) ) {
      
      $smarty->assign('action',  $this->action );
      $content = $contentsModel->getContent( 'http404', $language );
      
    }
    
    if ( !headers_sent() ) {
      
      switch( $content['shortname'] ) {
        case 'http404':
          header("HTTP/1.1 404 Not Found");
          header("Status: 404 Not Found");  // FastCGI alternative
          break;
        case 'http401403':
          header('HTTP/1.1 403 Forbidden');
          break;
      }
      
    }
    
    $smarty->assign('content', $content );
    $this->output( $smarty->fetch('Visitor/contents.tpl') );
    
  }
  
}
