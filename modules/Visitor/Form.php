<?php
namespace Visitor;

class Form extends \Springboard\Controller\Form {
  public $xsrfprotect = false;
  public $checkboxitemlayout = '
    <div class="checkboxwrap indentlevel-%level%">
      <div class="checkboxindent indentlevel-%level%">%indent%</div>
      <div class="checkboxitem">%checkbox%</div>
      <div class="checkboxlabel" title="%valuehtmlescape%">%label%</div>
    </div>
  ';
  public $radioitemlayout = '
    <div class="radiowrap">
      <div class="radioitem">%radio%</div>
      <div class="radiolabel">%label%</div>
    </div>
  ';

  public function redirectToMainDomain() {}
  
  public function postGetForm() {
    
    $this->form->jspath =
      $this->controller->toSmarty['STATIC_URI'] . 'js/clonefish.js'
    ;
    
    $this->form->messagecontainerlayout =
      '<div class="formerrors"><ul>%s</ul></div>'
    ;
    
    $this->form->messageprefix = '';
    $this->form->layouts['tabular']['container']  =
      "<table cellpadding=\"0\" cellspacing=\"0\" class=\"formtable\">\n%s\n</table>\n"
    ;
    
    $this->form->layouts['tabular']['element'] =
      '<tr %errorstyle%>' .
        '<td class="labelcolumn">' .
          '<label for="%id%">%displayname%</label>' .
        '</td>' .
        '<td class="elementcolumn">%prefix%%element%%postfix%%errordiv%</td>' .
      '</tr>'
    ;
    
    $this->form->layouts['tabular']['buttonrow'] =
      '<tr class="buttonrow"><td colspan="2">%s</td></tr>'
    ;
    
    $this->form->layouts['tabular']['button'] =
      '<input type="submit" value="%s" class="submitbutton" />'
    ;
    
    $this->form->layouts['rowbyrow']['errordiv'] =
      '<div id="%divid%" style="display: none; visibility: hidden; ' .
      'padding: 2px 5px 2px 5px; background-color: #d03030; color: white;' .
      'clear: both;"></div>'
    ;
    
  }
  
  public function handleAccesstypeForModel( $model, &$values, $shouldclear = true ) {
    
    $model->clearAccess();
    switch( $values['accesstype'] ) {
      
      case 'public':
      case 'registrations':
        // kiuritettuk mar elobb az `access`-t az adott recordinghoz
        // itt nincs tobb dolgunk
        break;
      
      case 'departments':
        
        if ( isset( $_REQUEST['departments'] ) and !empty( $values['departments'] ) )
          $model->restrictDepartments( $values['departments'] );
        
        break;
      
      case 'groups':
        
        if ( isset( $_REQUEST['groups'] ) and !empty( $values['groups'] ) )
          $model->restrictGroups( $values['groups'] );
        
        break;
      
      default:
        throw new \Exception('Unhandled accesstype');
        break;
      
    }
    
  }
  
}
