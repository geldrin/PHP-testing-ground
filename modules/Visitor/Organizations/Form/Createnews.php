<?php
namespace Visitor\Organizations\Form;
class Createnews extends \Visitor\Form {
  public $configfile = 'Createnews.php';
  public $template   = 'Visitor/genericform.tpl';
  
  public function postSetupForm() {
    
    $l = $this->bootstrap->getLocalization();
    $this->controller->toSmarty['title'] = $l('organizations', 'createnews_title');
    $this->controller->toSmarty['formclass'] = 'leftdoublebox';
    
  }
  
  public function onComplete() {
    
    $values    = $this->form->getElementValues( 0 );
    $newsModel = $this->bootstrap->getModel('organizations_news');
    
    $values['timestamp']      = date('Y-m-d H:i:s');
    $values['organizationid'] = $this->controller->organization['id'];
    
    $this->ensureStringsExist( $newsModel->multistringfields, $values );
    if ( !$this->form->validate() )
      return;
    
    $strings = $this->assembleStrings( $values );
    //echo "<pre>";var_dump( $strings, $values ); die();
    $newsModel->insert( $values, $strings, false );
    
    $this->controller->redirect(
      $this->application->getParameter('forward', 'organizations/listnews' )
    );
    
  }
  
  public function ensureStringsExist( $fields, &$values ) {
    
    $maxerrors = count( $this->bootstrap->config['languages'] );
    $l         = $this->bootstrap->getLocalization();
    
    foreach( $fields as $field ) {
      $errors = 0;
      foreach( $this->bootstrap->config['languages'] as $language ) {
        
        $value = trim( $values[ $field . '_' . $language ] );
        if ( !$value )
          $errors++;
        
      }
      
      if ( $errors == $maxerrors ) {
        
        $this->form->addMessage( $l('organizations', $field . '_help') );
        $this->form->invalidate();
        
      }
      
    }
    
  }
  
  public function assembleStrings( &$values ) {
    
    $strings = array(
      'title_stringid' => array(),
      'lead_stringid' => array(),
      'body_stringid' => array(),
    );
    
    foreach( $strings as $k => $v ) {
      
      $index = str_replace( 'stringid', '', $k );
      $values[ $index ] = $values[ $index . \Springboard\Language::get() ];
      foreach( $this->bootstrap->config['languages'] as $language )
        $strings[ $k ][ $language ] = $values[ $index . $language ];
      
    }
    
    return $strings;
    
  }
  
}
