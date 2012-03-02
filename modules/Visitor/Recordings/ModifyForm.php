<?php
namespace Visitor\Recordings;

class ModifyForm extends \Visitor\HelpForm {
  public $recordingsModel;
  public $toSmarty = array(
    'insertbefore' => 'Visitor/Recordings/ModifyTimeline.tpl',
  );
  
  public function init() {
    
    $recordingsModel = $this->bootstrap->getModel('recordings');
    $user            = $this->bootstrap->getUser();
    $recordingid     = $this->application->getNumericParameter('id');
    
    $recordingsModel->select( $recordingid );
    if ( !$recordingsModel->row )
      $this->controller->redirect('index');
    
    $this->recordingsModel       = $recordingsModel;
    $this->values                = $recordingsModel->row;
    $this->toSmarty['recording'] = $recordingsModel->row;
    $this->toSmarty['step']      =
      strtolower(
        str_replace(
          'Visitor\\Recordings\\Form\\',
          '',
          get_class( $this )
        )
      )
    ;
    
  }
  
}
