<?php
namespace Visitor\Recordings\Form;
set_time_limit(0);

class Uploadcontent extends \Visitor\HelpForm {
  public $configfile   = 'Uploadcontent.php';
  public $template     = 'Visitor/Recordings/Upload.tpl';
  public $swfupload    = false;
  public $languages    = array();
  public $uploads      = array();
  public $recordingModel;

  public function init() {

    if ( $this->bootstrap->inMaintenance('upload') )
      $this->controller->redirectToController('contents', 'uploaddisabled');

    if ( $this->application->getParameter('swfupload') )
      $this->swfupload = true;

    $user = $this->bootstrap->getSession('user');
    $isuploader = \Model\Userroles::userHasPrivilege(
      $user,
      'recordings_upload',
      'or',
      'isuploader', 'ismoderateduploader', 'isadmin'
    );

    if ( $this->swfupload and !$isuploader )
      $this->controller->swfuploadMessage( array(
          'error' => 'membersonly',
          'url'   => $this->controller->getUrlFromFragment('index'),
        )
      );
    elseif ( !$isuploader )
      $this->controller->redirectToController('contents', 'nopermissionuploader');

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

    $uploadModel   = $this->bootstrap->getModel('uploads');
    if ( !$uploadModel->isUploadingAllowed() )
      $this->controller->redirectToController('contents', 'uploaddisabled');

    $this->uploads = $uploadModel->getUploads( $user, true );
    parent::init();

  }

  public function preSetupForm() {

    $l = $this->bootstrap->getLocalization();
    $this->controller->toSmarty['title']     = $l('recordings', 'uploadcontent_title');
    $this->controller->toSmarty['uploadurl'] =
      $this->controller->getUrlFromFragment('recordings/uploadcontent' )
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

    $user   = $this->bootstrap->getSession('user');
    $values = $this->form->getElementValues( 0 );
    $info   = array(
      'iscontent'  => true,
      'filepath'   => $_FILES['file']['tmp_name'],
      'filename'   => $_FILES['file']['name'],
      'handlefile' => 'upload',
    );

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

      $this->recordingModel->upload( $info );

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
        var_export( $this->recordingModel->metadata ),
        true
      );

    } catch( \Model\HandleFileException $e ) {

      $error = 'movefailed';
      $this->form->addMessage( );
      $debug->log( false, 'upload.txt',
        'Handlefile exception for content! -- ' .
        var_export( $e->getMessage(), true )
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

    if ( $this->swfupload )
      $this->controller->swfuploadMessage( array(
          'url' => $this->controller->getUrlFromFragment('contents/uploadcontentsuccessfull'),
        )
      );
    else
      $this->controller->redirect('contents/uploadcontentsuccessfull');

  }

}
