<?php
namespace Visitor\Recordings\Form;

class Modifydescription extends \Visitor\Recordings\ModifyForm {
  public $configfile   = 'Modifydescription.php';
  public $template     = 'Visitor/genericform.tpl';
  
  function postSetupForm() {
    
    // $this->recordingsModel a parent class ->check() metodusabol
    $languageModel = $this->bootstrap->getModel('languages');
    $languageModel->addFilter('id', $this->recordingsModel->row['languageid'], true, false );
    $language = $languageModel->getOne('name');
    
    $elem = $this->form->getElementByName('descriptionoriginal');
    $elem->displayname = sprintf( $elem->displayname, $language );
    
    $elem = $this->form->getElementByName('copyrightoriginal');
    $elem->displayname = sprintf( $elem->displayname, $language );
    
  }
  
  public function onComplete() {
    
    $values = $this->form->getElementValues( 0 );
    
    $this->recordingsModel->updateRow( $values );
    $this->recordingsModel->updateFulltextCache( true );
    
    $this->controller->redirect(
      'recordings/modifycontributors/' . $this->recordingsModel->id,
      array( 'forward' => $values['forward'] )
    );
    
  }
  
}
