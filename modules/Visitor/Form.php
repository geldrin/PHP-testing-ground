<?php
namespace Visitor;

class Form extends \Springboard\Controller\Form {
  
  public function postGetForm() {
    
    $this->form->layout = 'rowbyrow';
    $this->form->formopenlayout =
      '<form enctype="multipart/form-data" target="%target%" name="%name%" ' .
      'id="%name%" action="%action%" %onsubmit% method="%method%">'
    ;
    
    $this->form->messagecontainerlayout =
      '<div class="formerrors"><ul>%s</ul></div>'
    ;
    
    $this->form->messageprefix = '';
    
    $this->form->layouts['rowbyrow']['button'] =
      '<input type="submit" value="OK" />'
    ;
    
    $this->form->layouts['rowbyrow']['buttonrow'] = '%s';
    
  }
  
}
