<?php
namespace Visitor\Recordings\Form;
set_time_limit(0);

class Upload extends \Visitor\Form {
  var $configfile  = 'Upload.php';
  var $template    = 'Visitor/Recordings/Upload.tpl';
  var $swfupload   = false;
  var $languages   = array();
  
  function init() {
    
    if ( $this->bootstrap->config['disable_uploads'] )
      $this->controller->redirectToFragment('contents/uploaddisabled');
    
    if ( $this->application->getParameter('swfupload') )
      $this->swfupload = true;
    
    $user = $this->bootstrap->getUser();
    
    if ( $this->swfupload and !$user->isuploader )
      $this->controller->swfuploadMessage( array(
          'error' => 'membersonly',
          'url'   => $this->controller->getUrlFromFragment('index'),
        )
      );
    elseif ( !$user->isuploader ) {
      
      $contentModel = $this->bootstrap->getModel('contents');
      $smarty       = $this->bootstrap->getSmarty(); 
      $content      = $contentModel->getContent(
        'recordingsupload',
        \Springboard\Language::get()
      );
      
      $smarty->assign( 'content', $content );
      $this->controller->output( $smarty->fetch( 'Visitor/contents.tpl' ) );
      
    }
    
    parent::init();
    
  }

  function values() {
    
    $languageModel = $this->bootstrap->getModel('language');
    $l             = $this->bootstrap->getLocale();
    
    $this->languages = $languageModel->getAssoc('id', 'originalname', false, false, false, 'weight');
    $this->toSmarty['languages'] = $this->languages;
    $this->toSmarty['title']     = $l('recordings', 'upload_title');
    
  }

  // ----------------------------------------------------------------------------
  function onComplete() {
    
    $l     = $this->bootstrap->getLocale();
    $debug = \Springboard\Debug::getInstance();
    $debug->log( false, 'upload.txt', var_export( $_FILES, true ) . "\n------\n" );
    
    if ( @$_FILES['file']['error'] == UPLOAD_ERR_INI_SIZE or @$_FILES['file']['error'] == UPLOAD_ERR_FORM_SIZE ) {
      
      $debug->log( false, 'upload.txt', 'size error');
      
      if ( $this->swfupload )
        $this->controller->swfuploadMessage( array(
            'error' => 'filetoobig',
          ), @$_FILES['file']
        );
      
      $this->form->addMessage( $l('', 'filetoobig') );
      $this->form->invalidate();
      return;
      
    }
    
    $recordingModel = $this->bootstrap->getModel('recordings');
    $values         = $this->form->getElementValues( 0 );
    
    if ( !isset( $this->languages[ $values['videolanguage'] ] ) and $this->swfupload )
      $this->controller->swfuploadMessage( array(
          'error' => 'securityerror',
        )
      );
    elseif ( !isset( $this->languages[ $values['videolanguage'] ] ) ) {
      
      $this->form->addMessage( $l('video', 'invalidlanguage') );
      $this->form->invalidate();
      return;
      
    }
    
    if ( intval( $values['recordingid'] ) > 0 ) {

      $recordingModel->select( intval( $values['recordingid'] ) );
      if ( !$recordingModel->row ) {

        if ( $this->swfupload )
          $this->controller->swfuploadMessage( array( 'error' => 'securityerror' ) );

        $this->form->addMessage( $l('video', 'invalidvideo') );
        $this->form->invalidate();
        return;

      }
      else
        $recordingid = $recordingModel->id;

    }
    else
      $recordingid = null;
    
    try {
      
      $recordingModel->uploadRecording(
        @$_FILES['file']['tmp_name'],
        @$_FILES['file']['name'],
        $recordingid,
        $values['videolanguage'],
        $values['isinterlaced'],
        $this->swfupload
      );
      
    } catch( InvalidFileTypeException $e ) {
      
      $error = 'invalidfiletype';
      $this->form->addMessage( $l('', 'swfupload_invalidfiletype') );
      
    } catch( InvalidLengthException $e ) {
      
      $error = 'invalidlength';
      $this->form->addMessage( $l('', 'swfupload_invalidlength') );
      $debug->log( false, 'upload.txt', 'uploadcontentrecording failed -- ' . var_export( $e->getMessage(), true ) );
      
    } catch( InvalidVideoResolutionException $e ) {
      
      $error = 'filetoobig';
      $this->form->addMessage( $l('', 'filetoobig') );
      $debug->log( false, 'upload.txt',
        'Video from user ' . getuser('email') . ' ' .
        'exceeded size constraints, metadata was ' .
        var_export( $recordingModel->metadata ),
        true
      );
      
    } catch( Exception $e ) {
      
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
    
    try {
      
      $recordingModel->handleFile( $_FILES['file']['tmp_name'] );
      $recordingModel->markRecordingUploaded();
      
    } catch( Exception $e ) {
      
      if ( !$recordingid )
        $recordingModel->markFailed();
      
      if ( $this->swfupload )
        $this->controller->swfuploadMessage( array('error' => 'movefailed'), $_FILES['file'] );
      
    }
    
    if ( $this->swfupload )
      $this->controller->swfuploadMessage( array(
          'url' => $this->controller->getUrlFromFragment('contents/uploadsuccessfull'),
        )
      );
    else
      $this->controller->redirectToFragment('contents/uploadsuccessfull');
    
  }
  
}
