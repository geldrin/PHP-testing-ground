<?php
namespace Visitor\Recordings\Form;

class Modifyattachment extends \Visitor\HelpForm {
  public $configfile   = 'Modifyattachment.php';
  public $template     = 'Visitor/genericform.tpl';
  public $recordingModel;
  public $attachmentModel;
  
  public function init() {
    
    $this->attachmentModel = $this->controller->modelIDCheck(
      'attached_documents',
      $this->application->getNumericParameter('id')
    );
    
    $this->recordingModel = $this->controller->modelOrganizationAndUserIDCheck(
      'recordings',
      $this->attachmentModel->row['recordingid']
    );
    
    $this->values = $this->attachmentModel->row;
    parent::init();
    
  }
  
  public function preSetupForm() {
    
    $this->config['fs1']['prefix'] =
      sprintf( $this->config['fs1']['prefix'],
        htmlspecialchars( $this->recordingModel->row['title'], ENT_QUOTES, 'UTF-8', true )
      )
    ;
    
  }
  
  public function onComplete() {
    
    $l      = $this->bootstrap->getLocalization();
    $values = $this->form->getElementValues( 0 );
    
    $this->attachmentModel->updateRow( $values );
    
    $this->controller->redirect(
      $this->application->getParameter(
        'forward',
        'recordings/manageattachments/' . $this->recordingModel->id
      )
    );
    
  }
  
}
