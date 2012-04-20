<?php
namespace Visitor\Recordings\Form;

class Uploadsubtitle extends \Visitor\HelpForm {
  public $configfile   = 'Uploadsubtitle.php';
  public $template     = 'Visitor/genericform.tpl';
  
  public function onComplete() {
    
    $l             = $this->bootstrap->getLocalization();
    $subtitleModel = $this->bootstrap->getModel('subtitles');
    $values        = $this->form->getElementValues( 0 );
    $values['recordingid'] = $this->recordingsModel->id;
    
    $subtitleModel->insert( $values );
    
    $this->controller->redirectWithMessage(
      $this->application->getParameter('forward', 'recordings/myrecordings'),
      $l('recordings', 'subtitleuploadsuccessfull')
    );
    
  }
  
}
