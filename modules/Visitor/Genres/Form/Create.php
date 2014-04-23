<?php
namespace Visitor\Genres\Form;

class Create extends \Visitor\HelpForm {
  public $configfile = 'Create.php';
  public $template   = 'Visitor/genericform.tpl';
  public $needdb     = true;
  
  public function postSetupForm() {
    
    $l = $this->bootstrap->getLocalization();
    $this->controller->toSmarty['title'] = $l('genres', 'create_title');
    $this->controller->toSmarty['helpclass'] = 'rightbox small';

  }
  
  public function onComplete() {
    
    $values     = $this->form->getElementValues( 0 );
    $genreModel = $this->bootstrap->getModel('genres');
    
    $genreModel->insert( $values );
    
    $this->controller->redirect(
      $this->application->getParameter('forward', 'genres/admin' )
    );
    
  }
  
}
