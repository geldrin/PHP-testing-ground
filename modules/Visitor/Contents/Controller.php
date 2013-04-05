<?php
namespace Visitor\Contents;

class Controller extends \Visitor\Controller {
  
  public function route() {
    
    if ( $this->action == 'language' )
      return $this->languageAction();
    
    $contentsModel = $this->bootstrap->getModel('contents');
    $language      = \Springboard\Language::get();
    
    $content = $contentsModel->getContent( $this->action, $language );
    
    if ( empty( $content ) ) {
      
      $this->toSmarty['missingcontent'] = $this->action;
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
    
    $this->toSmarty['content'] = $content;
    $this->smartyoutput('Visitor/Contents/Contents.tpl');
    
  }
  
  public function languageAction() {
    
    $l = $this->bootstrap->getLocalization();
    
    $this->toSmarty['localization']     =
      json_encode( $l->get('contents'), JSON_HEX_TAG )
    ;
    $this->toSmarty['allowedfiletypes'] = implode(
      ',',
      $this->bootstrap->config['allowedextensions']
    );
    
    $output = $this->fetchSmarty('Visitor/Contents/Language.tpl');
    
    header('Content-Type: application/javascript; charset=UTF-8');
    $this->output(
      $output,
      false,
      true // preserve message
    );
    
  }
  
}
