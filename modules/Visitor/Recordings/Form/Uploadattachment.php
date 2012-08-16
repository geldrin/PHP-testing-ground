<?php
namespace Visitor\Recordings\Form;
set_time_limit(0);

class Uploadattachment extends \Visitor\HelpForm {
  public $configfile   = 'Uploadattachment.php';
  public $template     = 'Visitor/genericform.tpl';
  public $recordingModel;
  
  public function init() {
    
    $this->recordingModel = $this->controller->modelOrganizationAndUserIDCheck(
      'recordings',
      $this->application->getNumericParameter('recordingid')
    );
    
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
    $user   = $this->bootstrap->getSession('user');
    $values = $this->form->getElementValues( 0 );
    
    if ( $_FILES['file']['error'] != 0 ) {
      
      $this->form->addMessage( $l('recordings', 'attachment_help') . ' (#' . $_FILES['file']['error'] . ')' );
      $this->form->invalidate();
      return;
      
    }
    
    $attachmentModel           = $this->bootstrap->getModel('attached_documents');
    $values['masterfilename']  = $_FILES['file']['name'];
    $values['masterextension'] = \Springboard\Filesystem::getExtension( $_FILES['file']['name'] );
    $values['timestamp']       = date('Y-m-d H:i:s');
    $values['status']          = 'uploading';
    $values['sourceip']        = 'stream.videosquare.eu';
    
    $attachmentModel->insert( $values );
    $destination =
      $this->bootstrap->config['uploadpath'] . 'attachments/' .
      $attachmentModel->id . '.' . $values['masterextension']
    ;
    
    if ( !move_uploaded_file( $_FILES['file']['tmp_name'],  $destination ) ) {
      
      $attachmentModel->updateRow( array(
          'status' => 'movefailed',
        )
      );
      
      $this->form->addMessage('System error');
      $this->form->invalidate();
      return;
      
    } else
      $attachmentModel->updateRow( array(
          'status' => 'uploaded',
        )
      );
    
    $this->controller->redirectWithMessage(
      $this->application->getParameter('forward', 'recordings/myrecordings'),
      $l('recordings', 'attachment_success')
    );
    
  }
  
}
