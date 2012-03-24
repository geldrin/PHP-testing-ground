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
    $this->toSmarty['flashdata']    = $recordingsModel->getFlashData();
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
    
    $views          = $this->getSession('views');
    $recordingModel = $this->modelIDCheck(
      'recordings',
      $this->application->getNumericParameter('id')
    );
    
    if ( !$views[ $recordingModel->id ] ) {
      
      $recordingModel->incrementViewCounters();
      $views[ $recordingModel->id ] = true;
      
    }
    
  }
  
}
