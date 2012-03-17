<?php
namespace Visitor\Recordings\Form;
set_time_limit(0);

class Uploadcontent extends \Visitor\HelpForm {
  public $configfile   = 'Uploadcontent.php';
  public $template     = 'Visitor/Recordings/Uploadcontent.tpl';
  public $swfupload    = false;
  public $languages    = array();
  public $recordingModel;
  
  public function init() {
    
    if ( $this->bootstrap->config['disable_uploads'] )
      $this->controller->redirectToController('contents', 'uploaddisabled');
    
    if ( $this->application->getParameter('swfupload') )
      $this->swfupload = true;
    
    $user = $this->bootstrap->getSession('user');
    
    if ( $this->swfupload and !$user['isuploader'] )
      $this->controller->swfuploadMessage( array(
          'error' => 'membersonly',
          'url'   => $this->controller->getUrlFromFragment('index'),
        )
      );
    elseif ( !$user['isuploader'] )
      $this->controller->redirectToController('contents', 'recordingsupload');
    
    $this->recordingModel = $this->controller->modelOrganizationAndUserIDCheck(
      'recordings',
      $this->application->getNumericParameter('id')
    );
    
    if ( !$this->recordingModel->canUploadContentVideo() ) {
      
      if ( $this->swfupload )
        $this->controller->swfuploadMessage( array( 'error' => 'unknownerror' ) );
      else
        $this->redirectToController('contents', 'uploadcontentunavailable');
      
    }
    
    parent::init();
    
  }
  
  public function preSetupForm() {
    
    $languageModel = $this->bootstrap->getModel('languages');
    $l             = $this->bootstrap->getLocalization();
    
    $this->languages = $languageModel->getAssoc('id', 'originalname', false, false, false, 'weight');
    $this->controller->toSmarty['title']     = $l('recordings', 'uploadcontent_title');
    $this->controller->toSmarty['languages'] = $this->languages;
    $this->config['videolanguage']['values'] = $this->languages;
    
  }
  
  public function onComplete() {
    
    $l     = $this->bootstrap->getLocalization();
    $debug = \Springboard\Debug::getInstance();
    $debug->log( false, 'upload.txt', var_export( $_FILES, true ) . "\n------\n" );
    
    if ( @$_FILES['file']['error'] == UPLOAD_ERR_INI_SIZE or @$_FILES['file']['error'] == UPLOAD_ERR_FORM_SIZE ) {
      
      $debug->log( false, 'upload.txt', 'size error');
      
      if ( $this->swfupload )
        $this->controller->swfuploadMessage( array(
            'error' => 'filetoobig',
          ),
          $_FILES['file']
        );
      
      $this->form->addMessage( $l('', 'filetoobig') );
      $this->form->invalidate();
      return;
      
    }
    
    $recordingModel = $this->recordingModel;
    $user           = $this->bootstrap->getSession('user');
    $values         = $this->form->getElementValues( 0 );
    
    if ( !isset( $this->languages[ $values['videolanguage'] ] ) and $this->swfupload )
      $this->controller->swfuploadMessage( array(
          'error' => 'securityerror',
        )
      );
    elseif ( !isset( $this->languages[ $values['videolanguage'] ] ) ) {
      
      $this->form->addMessage( $l('recordings', 'invalidlanguage') );
      $this->form->invalidate();
      return;
      
    }
    
    try {
      
      $recordingModel->analyze(
        $_FILES['file']['tmp_name'],
        $_FILES['file']['name']
      );
      
    } catch( \Model\InvalidFileTypeException $e ) {
      
      $error = 'invalidfiletype';
      $this->form->addMessage( $l('', 'swfupload_invalidfiletype') );
      
    } catch( \Model\InvalidLengthException $e ) {
      
      $error = 'invalidlength';
      $this->form->addMessage( $l('', 'swfupload_invalidlength') );
      $debug->log( false, 'upload.txt', 'uploadcontentrecording failed -- ' . var_export( $e->getMessage(), true ) );
      
    } catch( \Model\InvalidVideoResolutionException $e ) {
      
      $error = 'filetoobig';
      $this->form->addMessage( $l('', 'filetoobig') );
      $debug->log( false, 'upload.txt',
        'Video from user ' . getuser('email') . ' ' .
        'exceeded size constraints, metadata was ' .
        var_export( $recordingModel->metadata ),
        true
      );
      
    } catch( \Exception $e ) {
      
      $error = 'failedvalidation';
      $this->form->addMessage( $l('', 'swfupload_failedvalidation') );
      $debug->log( false, 'upload.txt', 'uploadcontentrecording failed -- ' . var_export( $e->getMessage(), true ) );
      
    }
    
    if ( isset( $error ) ) {
      
      if ( $this->swfupload )
        $this->controller->swfuploadMessage( array(
            'error' => $error,
          ), @$_FILES['file']
        );
      
      $this->form->invalidate();
      return;
      
    }
    
    $recordingModel->addContentRecording(
      $values['isinterlaced'],
      'stream.teleconnect.hu'
    );
    
    try {
      
      $recordingModel->handleFile(
        $_FILES['file']['tmp_name'],
        'upload',
        '_content'
      );
      
      $recordingModel->markContentRecordingUploaded();
      
    } catch( Exception $e ) {
      
      $recordingModel->updateRow( array(
          'masterstatus' => 'failedmovinguploadedfile',
        )
      );
      
      if ( $this->swfupload )
        $this->controller->swfuploadMessage( array('error' => 'movefailed'), $_FILES['file'] );
      
    }
    
    if ( $this->swfupload )
      $this->controller->swfuploadMessage( array(
          'url' => $this->controller->getUrlFromFragment('contents/uploadcontentsuccessfull'),
        )
      );
    else
      $this->controller->redirect('contents/uploadcontentsuccessfull');
    
  }
  
}
