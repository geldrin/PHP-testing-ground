<?php
namespace Visitor\Recordings;

class Controller extends \Visitor\Controller {
  public $permissions = array(
    'index'                => 'public',
    'details'              => 'public',
    'getcomments'          => 'public',
    'getsubtitle'          => 'public',
    'newcomment'           => 'member',
    'rate'                 => 'member',
    'track'                => 'public',
    'upload'               => 'uploader',
    'uploadcontent'        => 'uploader',
    'uploadsubtitle'       => 'uploader',
    'myrecordings'         => 'uploader',
    'modifybasics'         => 'uploader',
    'modifyclassification' => 'uploader',
    'modifydescription'    => 'uploader',
    'modifysharing'        => 'uploader',
    'deletesubtitle'       => 'uploader',
    'delete'               => 'uploader',
    'checkstreamaccess'    => 'public',
  );
  
  public $forms = array(
    'upload'               => 'Visitor\\Recordings\\Form\\Upload',
    'uploadcontent'        => 'Visitor\\Recordings\\Form\\Uploadcontent',
    'uploadsubtitle'       => 'Visitor\\Recordings\\Form\\Uploadsubtitle',
    'modifybasics'         => 'Visitor\\Recordings\\Form\\Modifybasics',
    'modifyclassification' => 'Visitor\\Recordings\\Form\\Modifyclassification',
    'modifydescription'    => 'Visitor\\Recordings\\Form\\Modifydescription',
    'modifysharing'        => 'Visitor\\Recordings\\Form\\Modifysharing',
    'newcomment'           => 'Visitor\\Recordings\\Form\\Newcomment',
  );
  
  public $paging = array(
    'myrecordings' => 'Visitor\\Recordings\\Paging\\Myrecordings',
  );
  
  public $apisignature = array(
    'modifyrecording' => array(
      'id' => array(
        'type' => 'id',
      ),
    ),
    'apiupload' => array(
      'file' => array(
        'type' => 'file',
      ),
      'language' => array(
        'type' => 'string',
      ),
    ),
    'apiuploadcontent' => array(
      'id' => array(
        'type' => 'id',
      ),
      'file' => array(
        'type' => 'file',
      ),
    ),
  );
  
  public function init() {
    
    $action = str_replace('submit', '', $this->action );
    
    if ( $action == 'upload' or $action == 'uploadcontent' )
      $this->bootstrap->setupSession( true );
    
    parent::init();
    
  }
  
  public function indexAction() {
    $this->redirect('recordings/myrecordings');
  }
  
  public function rateAction() {
    
    $recordingid = $this->application->getNumericParameter('id');
    $rating      = $this->application->getNumericParameter('rating');
    $result      = array('status' => 'error');
    
    if ( !$recordingid or $rating < 1 or $rating > 5 ) {
      
      $result['reason'] = 'invalidparameters';
      $this->jsonoutput( $result );
      
    }
    
    $session = $this->bootstrap->getSession('rating');
    if ( $session[ $recordingid ] ) {
      
      $result['reason'] = 'alreadyvoted';
      $this->jsonoutput( $result );
      
    }
    
    $recordingsModel = $this->bootstrap->getModel('recordings');
    $recordingsModel->id = $recordingid;
    
    if ( !$recordingsModel->addRating( $rating ) )
      $this->jsonoutput( $result );
    
    $session[ $recordingid ] = true;
    $result = array(
      'status'          => 'success',
      'rating'          => $recordingsModel->row['rating'],
      'numberofratings' => $recordingsModel->row['numberofratings'],
    );
    
    $this->jsonoutput( $result );
    
  }
  
  public function detailsAction() {
    
    $recordingsModel = $this->modelIDCheck(
      'recordings',
      $this->application->getNumericParameter('id')
    );
    
    $user    = $this->bootstrap->getSession('user');
    $rating  = $this->bootstrap->getSession('rating');
    $access  = $this->bootstrap->getSession('recordingaccess');
    
    $access[ $recordingsModel->id ] = $recordingsModel->userHasAccess( $user );
    
    if ( $access[ $recordingsModel->id ] !== true )
      $this->redirectToController('contents', $access[ $recordingsModel->id ] );
    
    // TODO relatedvideos
    
    $this->toSmarty['recording']    = $recordingsModel->row;
    $this->toSmarty['flashdata']    = $recordingsModel->getFlashData( $this->toSmarty, session_id() );
    $this->toSmarty['comments']     = $recordingsModel->getComments();
    $this->toSmarty['commentcount'] = $recordingsModel->getCommentsCount();
    $this->toSmarty['author']       = $recordingsModel->getAuthor();
    $this->toSmarty['canrate']      = $rating[ $recordingsModel->id ];
    
    $this->smartyoutput('Visitor/Recordings/Details.tpl');
    
  }
  
  public function getcommentsAction() {
    
    $recordingid = $this->application->getNumericParameter('id');
    $start       = $this->application->getNumericParameter('start');
    
    if ( $recordingid <= 0 )
      $this->redirect('index');
    
    if ( $start < 0 )
      $start = 0;
    
    $l               = $this->bootstrap->getLocalization();
    $recordingsModel = $this->bootstrap->getModel('recordings');
    $recordingsModel->id = $recordingid;
    
    $comments     = $recordingsModel->getComments( $start );
    $commentcount = $recordingsModel->getCommentsCount();
    
    $this->jsonoutput( array(
        'comments'     => $comments,
        'nocomments'   => $l('recordings', 'nocomments'),
        'commentcount' => $commentcount,
      )
    );
    
  }
  
  public function deletesubtitleAction() {
    
    $subtitleModel   = $this->modelIDCheck(
      'subtitles',
      $this->application->getNumericParameter('id')
    );
    
    $recordingsModel = $this->modelOrganizationAndUserIDCheck(
      'recordings',
      $subtitleModel->row['recordingid']
    );
    
    $subtitleModel->delete( $subtitleModel->id );
    $this->redirect(
      $this->application->getParameter('forward', 'recordings/myrecordings')
    );
    
  }
  
  public function getsubtitleAction() {
    
    header('Content-Type: text/plain; charset=UTF-8');
    $subtitleid = $this->application->getNumericParameter('id');
    $cache      = $this->bootstrap->getCache('subtitle_' . $subtitleid, null, true );
    
    if ( $cache->expired() ) {
      
      $subtitleModel = $this->modelIDCheck('subtitles', $subtitleid );
      $subtitle      = $subtitleModel->row['subtitle'];
      $cache->put( $subtitle );
      unset( $subtitleModel );
      
    } else
      $subtitle = $cache->get();
    
    $this->output( $subtitle );
    
  }
  
  public function deleteAction() {
    
    $recordingModel = $this->modelOrganizationAndUserIDCheck(
      'recordings',
      $this->application->getNumericParameter('id')
    );
    
    if ( preg_match( '/^onstorage$|^failed.*$/', $recordingModel->row['status'] ) )
      $recordingModel->markAsDeleted();
    
    $this->redirect(
      $this->application->getParameter('forward', 'recordings/myrecordings')
    );
    
  }
  
  public function trackAction() {
    
    $views          = $this->bootstrap->getSession('views');
    $recordingModel = $this->modelIDCheck(
      'recordings',
      $this->application->getNumericParameter('id')
    );
    
    if ( !$views[ $recordingModel->id ] ) {
      
      $recordingModel->incrementViewCounters();
      $views[ $recordingModel->id ] = true;
      
    }
    
  }
  
  public function checkstreamaccessAction() {
    
    $param = $this->application->getParameter('sessionid');
    
    $pos         = strrpos( $param, '_' );
    $sessionid   = substr( $param, 0, $pos );
    $recordingid = intval( substr( $param, $pos + 1 ) );
    $result      = '0';
    
    if ( preg_match('/^[a-z0-9]{32}$/', $sessionid ) and $recordingid ) {
      
      $this->bootstrap->setupSession( true, $sessionid );
      $access = $this->bootstrap->getSession('recordingaccess');
      
      if ( $access[ $recordingid ] !== true ) {
        
        $user = $this->bootstrap->getSession('user');
        $recordingsModel = $this->modelIDCheck('recordings', $recordingid, false );
        
        if ( $recordingsModel ) {
          
          $access[ $recordingid ] = $recordingsModel->userHasAccess( $user );
          
          if ( $access[ $recordingid ] === true )
            $result = '1';
          
        }
        
      } else
        $result = '1';
      
    }
    
    echo
      '<?xml version="1.0"?>
      <result>
        <success>' . $result . '</success>
      </result>'
    ;
    
    die();
    
  }
  
  public function modifyrecordingAction( $id ) {
    
    $recordingModel = $this->modelUserAndIDCheck('recordings', $id, false );
    
    if ( !$recordingModel )
      throw new \Exception('No recording found with that ID');
    
    $values = $this->application->getParameters();
    unset( // TODO inkabb whitelistet mint blacklistet
      $values['id'],
      $values['format'],
      $values['layer'],
      $values['method'],
      $values['module'],
      $values['language'],
      $values['_module']
    );
    
    $recordingModel->updateRow( $values );
    return $recordingModel->row;
    
  }
  
  public function apiuploadAction( $file, $language ) {
    
    set_time_limit(0);
    $recordingModel = $this->bootstrap->getModel('recordings');
    $languageModel  = $this->bootstrap->getModel('languages');
    $user           = $this->bootstrap->getSession('user');
    
    $languageModel->addFilter('shortname', $language, false, false );
    $values         = array(
      'userid'     => $user['id'],
      'languageid' => $languageModel->getOne('id'),
    );
    
    if ( !$values['languageid'] )
      throw new \Exception('Invalid language: ' . $language );
    
    // throws exception, nothing done to the DB yet
    $recordingModel->analyze(
      $file['tmp_name'],
      $file['name']
    );
    
    $recordingModel->insertUploadingRecording(
      $user['id'],
      $user['organizationid'],
      $values['languageid'],
      $file['name'],
      'stream.teleconnect.hu'
    );
    
    try {
      
      $recordingModel->handleFile( $file['tmp_name'] );
      $recordingModel->updateRow( array(
          'masterstatus' => 'uploaded',
          'status'       => 'uploaded',
        )
      );
      
    } catch( Exception $e ) {
      
      $recordingModel->updateRow( array(
          'masterstatus' => 'failedmovinguploadedfile',
        )
      );
      
      // rethrow
      throw $e;
      
    }
    
    return $recordingModel->row;
    
  }
  
  public function apiuploadcontentAction( $recordingid, $file ) {
    
    set_time_limit(0);
    $recordingModel = $this->modelOrganizationAndUserIDCheck(
      'recordings',
      $recordingid,
      false
    );
    
    if ( !$recordingModel )
      throw new \Exception('No recording found with that ID');
    
    if ( !$recordingModel->canUploadContentVideo() )
      throw new \Exception(
        'Uploading a content video is denied at this point: ' .
        var_export( $recordingModel->row, true )
      );
    
    $recordingModel->analyze(
      $file['tmp_name'],
      $file['name']
    );
    
    $recordingModel->addContentRecording(
      0, // TODO interlaced?
      'stream.teleconnect.hu'
    );
    
    try {
      
      $recordingModel->handleFile(
        $file['tmp_name'],
        'upload',
        '_content'
      );
      
      $recordingModel->markContentRecordingUploaded();
      
    } catch( Exception $e ) {
      
      $recordingModel->updateRow( array(
          'contentstatus' => 'failedmovinguploadedfile',
        )
      );
      
      throw $e;
      
    }
    
    return $recordingModel->row;
    
  }
  
}
