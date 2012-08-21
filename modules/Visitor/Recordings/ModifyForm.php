<?php
namespace Visitor\Recordings;

class ModifyForm extends \Visitor\HelpForm {
  public $recordingsModel;
  
  public function init() {
    
    $recordingsModel = $this->controller->modelOrganizationAndUserIDCheck(
      'recordings',
      $this->application->getNumericParameter('id')
    );
    
    $this->recordingsModel                   = $recordingsModel;
    $this->values                            = $recordingsModel->row;
    $this->controller->toSmarty['recording'] = $recordingsModel->row;
    $this->controller->toSmarty['step']      =
      strtolower(
        str_replace(
          'Visitor\\Recordings\\Form\\',
          '',
          get_class( $this )
        )
      )
    ;
    $this->controller->toSmarty['formclass']    = 'leftdoublebox';
    $this->controller->toSmarty['helpclass']    = 'small right';
    $this->controller->toSmarty['insertbefore'] = 'Visitor/Recordings/ModifyTimeline.tpl';
    parent::init();
    
  }
  
}
