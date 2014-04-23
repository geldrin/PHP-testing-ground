<?php
namespace Visitor\Genres\Form;

class Modify extends \Visitor\HelpForm {
  public $configfile = 'Modify.php';
  public $template   = 'Visitor/genericform.tpl';
  public $needdb     = true;
  
  public function init() {
    $this->genreModel = $this->controller->modelIDCheck(
      'genres',
      $this->application->getNumericParameter('id')
    );
    $this->values     = $this->genreModel->row;
  }
  
  public function postSetupForm() {
    
    $l = $this->bootstrap->getLocalization();
    $this->controller->toSmarty['title'] = $l('genres', 'create_title');
    
  }
  
  public function onComplete() {
    
    $values = $this->form->getElementValues( 0 );
    $this->genreModel->updateRow( $values );
    
    $this->controller->redirect(
      $this->application->getParameter('forward', 'genres/admin' )
    );
    
  }
  
}
