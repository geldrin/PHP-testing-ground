<?php
namespace Visitor\Organizations\Form;

class Modifynews extends Createnews {
  public $configfile = 'Modifynews.php';
  public $template   = 'Visitor/genericform.tpl';
  public $needdb     = true;
  public $newsModel;
  
  public function init() {
    
    $id              = $this->application->getNumericParameter('id');
    $user            = $this->bootstrap->getSession('user');
    
    if (
         !$user['isnewseditor'] or
         $this->controller->organization['id'] != $user['organizationid']
       )
      $this->controller->redirect('index');
    
    $this->newsModel        = $this->controller->modelIDCheck('organizations_news', $id );
    $this->values           = $this->newsModel->row;
    $this->values['starts'] = substr( $this->values['starts'], 0, 16);
    $this->values['ends']   = substr( $this->values['ends'], 0, 16);
    
    foreach( $this->bootstrap->config['languages'] as $language ) {
      
      $langvalues = $this->newsModel->getStringsForLanguage( $language );
      foreach( $langvalues as $k => $v )
        $this->values[ $k . '_' . $language ] = $v;
      
    }
    
    parent::init();
    
  }
  
  public function postSetupForm() {
    
    $l = $this->bootstrap->getLocalization();
    $this->controller->toSmarty['title'] = $l('organizations', 'modifynews_title');
    $this->controller->toSmarty['formclass'] = 'leftdoublebox';
    $this->controller->toSmarty['helpclass'] = 'rightbox small';
    
  }
  
  public function onComplete() {
    
    $values  = $this->form->getElementValues( 0 );
    $false   = false;
    $strings = $this->ensureStringsExist( $this->newsModel->multistringfields, $values );
    if ( !$this->form->validate() )
      return;
    
    $strings = $this->assembleStrings( $values );
    $this->newsModel->update( $false, $values, false, $strings, false );
    
    $this->controller->redirect(
      $this->application->getParameter('forward', 'organizations/listnews' )
    );
    
  }
  
}
