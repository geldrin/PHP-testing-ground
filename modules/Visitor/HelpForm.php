<?php
namespace Visitor;

class HelpForm extends \Visitor\Form {
  protected function assignHelp() {
    $helpkey = strtolower(
      $this->module . '_' . str_replace('submit', '', $this->action )
    );

    $this->controller->toSmarty['help'] =
      $this->controller->getHelp( $helpkey )
    ;
  }

  public function displayForm( $submitted ) {
    $this->assignHelp();
    parent::displayForm( $submitted );

  }

}
