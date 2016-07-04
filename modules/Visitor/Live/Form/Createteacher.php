<?php
namespace Visitor\Live\Form;

class Createteacher extends \Visitor\HelpForm {
  public $configfile = 'Createteacher.php';
  public $template   = 'Visitor/genericform.tpl';
  public $user;
  public $anonuser;
  public $feedModel;

  public function init() {
    if ( !$this->controller->organization['islivepinenabled'] )
      $this->controller->redirect('');

    $this->feedModel = $this->controller->modelOrganizationAndUserIDCheck(
      'livefeeds',
      $this->application->getNumericParameter('id')
    );

    parent::init();

  }

  public function onComplete() {

    $values = $this->form->getElementValues( 0 );
    // TODO
  }

}
