<?php
namespace Visitor\Recordings\Form;
set_time_limit(0);

class Upload extends \Visitor\HelpForm {
  public $configfile   = 'Upload.php';
  public $template     = 'Visitor/Recordings/Upload.tpl';
  public $swfupload    = false;
  public $languages    = array();
  public $uploads      = array();
  public $user;
  
  public function init() {

    if ( $this->bootstrap->inMaintenance('upload') )
      $this->controller->redirectToController('contents', 'uploaddisabled');

    if ( $this->application->getParameter('swfupload') )
      $this->swfupload = true;
    
    $this->user = $this->bootstrap->getSession('user');
    
    if ( $this->swfupload and !$this->user['isuploader'] and !$this->user['ismoderateduploader'] )
      $this->controller->swfuploadMessage( array(
          'error' => 'membersonly',
          'url'   => $this->controller->getUrlFromFragment('index'),
        )
      );
    elseif ( !$this->user['isuploader'] and !$this->user['ismoderateduploader'] )
      $this->controller->redirectToController('contents', 'nopermissionuploader');
    
    $uploadModel   = $this->bootstrap->getModel('uploads');
    if ( !$uploadModel->isUploadingAllowed() )
      $this->controller->redirectToController('contents', 'uploaddisabled');

    $this->uploads = $uploadModel->getUploads( $this->user );
    parent::init();
    
  }
  
  public function preSetupForm() {
    
    $languageModel = $this->bootstrap->getModel('languages');
    $l             = $this->bootstrap->getLocalization();
    
    $this->languages = $languageModel->getAssoc('id', 'originalname', false, false, false, 'weight');
    $this->controller->toSmarty['title']     = $l('recordings', 'upload_title');
    $this->controller->toSmarty['languages'] = $this->languages;
    $this->config['videolanguage']['values'] = $this->languages;
    $this->controller->toSmarty['uploadurl'] =
      $this->controller->getUrlFromFragment('recordings/upload' )
    ;
    
  }
  
  public function postGetForm() {
    $this->form->js       = false;
    $this->form->nosubmit = true;
    return parent::postGetForm();
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
    
    $recordingModel = $this->bootstrap->getModel('recordings');
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
    
    $info = array(
      'filepath'   => $_FILES['file']['tmp_name'],
      'filename'   => $_FILES['file']['name'],
      'language'   => $values['videolanguage'],
      'user'       => $this->user,
      'handlefile' => 'upload',
      'isintrooutro' => $values['isintrooutro'],
    );
    
    try {
      
      $recordingModel->upload( $info );
      
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
        'Video from user ' . $this->user['email'] . ' ' .
        'exceeded size constraints, metadata was ' .
        var_export( $recordingModel->metadata ),
        true
      );
      
    } catch( \Model\HandleFileException $e ) {
      
      $error = 'movefailed';
      $this->form->addMessage( );
      $debug->log( false, 'upload.txt',
        'Handlefile exception! -- ' .
        var_export( $e->getMessage(), true )
      );
      
    } catch( \Exception $e ) {
      
      $error = 'failedvalidation';
      $this->form->addMessage( $l('', 'swfupload_failedvalidation') );
      $debug->log( false, 'upload.txt', 'uploadcontentrecording failed -- ' . var_export( $e->getMessage(), true ) );
      
    }
    
    $channelid = $this->application->getNumericParameter('channelid');
    if ( $channelid ) {
      
      $channelModel = $this->modelOrganizationAndUserIDCheck(
        'channels',
        $channelid,
        false
      );
      
      if ( !$channelModel ) {
        
        $error = 'invalidchannel';
        $this->form->addMessage( $l('recordings', 'invalidchannel') );
        
      } else {
        
        $channelModel->insertIntoChannel( $recordingModel->id, $this->user );
        
      }
      
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
    
    if ( $this->swfupload )
      $this->controller->swfuploadMessage( array(
          'url' => $this->controller->getUrlFromFragment('contents/uploadsuccessfull'),
        )
      );
    else
      $this->controller->redirect('contents/uploadsuccessfull');
    
  }
  
}
