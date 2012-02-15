<?php
namespace Visitor\Genres\Form;

class Modify extends \Visitor\Form {
  public $configfile = 'Modify.php';
  public $template   = 'Visitor/genericform.tpl';
  public $needdb     = true;
  
  public function init() {
    $this->genreModel = $this->controller->modelIDCheck('genres');
    $this->values     = $this->genreModel->row;
  }
  
  public function postSetupForm() {
    
    $l = $this->bootstrap->getLocalization();
    $this->toSmarty['title'] = $l('genres', 'create_title');
    
  }
  
  public function onComplete() {
    
    $values = $this->form->getElementValues( 0 );
    $this->genreModel->updateRow( $values );
    
    $this->redirect(
      $this->application->getParameter('forward', 'genres/index' )
    );
    
  }
  
}
