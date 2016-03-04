<?php
namespace Visitor\Recordings\Form;

class Uploadsubtitle extends \Visitor\Recordings\ModifyForm {
  public $configfile   = 'Uploadsubtitle.php';
  public $template     = 'Visitor/genericform.tpl';
  
  public function init() {
    parent::init();
    unset( $this->controller->toSmarty['insertbefore'] );
    $l = $this->bootstrap->getLocalization();
    $this->controller->toSmarty['title'] = $l('recordings', 'uploadsubtitle_title');
  }
  
  public function onComplete() {
    
    $l             = $this->bootstrap->getLocalization();
    $subtitleModel = $this->bootstrap->getModel('subtitles');
    $values        = $this->form->getElementValues( 0 );
    $values['recordingid'] = $this->recordingsModel->id;
    
    if ( $values['isdefault'] )
      $this->recordingsModel->clearDefaultSubtitle();
    
    // egy nyelvhez mindig csak egy felirat tartozhat
    $this->recordingsModel->clearSubtitleWithLanguage( $values['languageid'] );
    $subtitleModel->insert( $values );
    
    $this->controller->redirectWithMessage(
      $this->application->getParameter('forward', 'recordings/myrecordings'),
      $l('recordings', 'subtitleuploadsuccessfull')
    );
    
  }
  
}
