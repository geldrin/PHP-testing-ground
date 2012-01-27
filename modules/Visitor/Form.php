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
      '<input type="submit" value="%s" class="submitbutton" />'
    ;
    
    $this->form->layouts['rowbyrow']['element'] =
      '<div class="formrow">' .
        '<span class="label"><label for="%id%">%displayname%</label></span>' .
        '<div class="element">%prefix%%element%' .
        '<span class="postfix">%postfix%</span>%errordiv%</div>' .
      '</div>'
    ;
    
    $this->form->layouts['rowbyrow']['errordiv'] =
      '<div id="%divid%" style="display: none; visibility: hidden; ' .
      'padding: 2px 5px 2px 5px; background-color: #d03030; color: white;' .
      'clear: both;"></div>'
    ;
    
  }
  
}
