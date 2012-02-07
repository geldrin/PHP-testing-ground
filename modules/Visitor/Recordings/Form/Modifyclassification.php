<?php
namespace Visitor\Recordings\Form;

class Modifyclassification extends \Visitor\Recordings\ModifyForm {
  public $configfile   = 'Modifyclassification.php';
  public $template     = 'Visitor/genericform.tpl';
  public $needdb       = true;
  
  public function onComplete() {
    
    $values = $this->form->getElementValues( 0 );
    
    $this->recordingsModel->clearGenres();
    $this->recordingsModel->addGenres( $values['genres'] );
    
    $this->recordingsModel->clearCategories();
    $this->recordingsModel->addCategories( $values['categories'] );
    
    $this->recordingsModel->updateRow( array(
        'keywords' => $values['keywords'],
      )
    );
    
    $this->recordingsModel->updateFulltextCache( true );
    
    $this->controller->redirect(
      'recordings/modifydescription/' . $this->recordingsModel->id,
      array( 'forward' => $values['forward'] )
    );
    
  }
  
}
