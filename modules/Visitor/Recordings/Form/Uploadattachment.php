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
      $this->application->getNumericParameter('id')
    );
    
    $back =
      $this->application->getParameter(
        'forward',
        $this->controller->getUrlFromFragment('recordings/myrecordings')
      )
    ;
    $this->controller->toSmarty['back']         = $back;
    $this->controller->toSmarty['insertbefore'] = 'Visitor/Recordings/Uploadattachment.tpl';
    $this->controller->toSmarty['recording']    = $this->recordingModel->row;
    $this->controller->toSmarty['attachments']  =
      $this->recordingModel->getAttachments( false )
    ;
    parent::init();
    
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
    $values['recordingid']     = $this->recordingModel->id;
    $values['userid']          = $user['id'];
    
    $attachmentModel->insert( $values );
    $destination =
      $this->bootstrap->config['uploadpath'] . 'attachments/' .
      $attachmentModel->id . '.' . $values['masterextension']
    ;
    
    if ( !move_uploaded_file( $_FILES['file']['tmp_name'],  $destination ) ) {
      
      $attachmentModel->updateRow( array(
          'status' => 'failedmove',
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
    /*
    $this->controller->redirectWithMessage(
      $this->application->getParameter('forward', 'recordings/myrecordings'),
      $l('recordings', 'attachment_success')
    );
    */
  }
  
}
