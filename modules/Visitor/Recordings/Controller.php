<?php
namespace Visitor\Recordings;

class Controller extends \Visitor\Controller {
  public $permissions = array(
    'index'  => 'public',
    'details' => 'public',
    'getcomments' => 'public',
    'newcomment' => 'member',
    'rate'   => 'member',
    'upload' => 'uploader',
    'myrecordings' => 'uploader',
    'modifybasics'         => 'uploader',
    'modifyclassification' => 'uploader',
    'modifydescription'    => 'uploader',
    'modifysharing'        => 'uploader',
  );
  
  public $forms = array(
    'upload' => 'Visitor\\Recordings\\Form\\Upload',
    'modifybasics' => 'Visitor\\Recordings\\Form\\Modifybasics',
    'modifyclassification' => 'Visitor\\Recordings\\Form\\Modifyclassification',
    'modifydescription' => 'Visitor\\Recordings\\Form\\Modifydescription',
    'modifysharing' => 'Visitor\\Recordings\\Form\\Modifysharing',
    'newcomment' => 'Visitor\\Recordings\\Form\\Newcomment',
  );
  
  public $paging = array(
    'myrecordings' => 'Visitor\\Recordings\\Paging\\Myrecordings',
  );
  
  // TODO override acl handling, swfuploadnal megfelelo uzenetet kuldeni
  /*
  
    if ( $this->swfupload and !$user->id )
      $this->controller->swfuploadMessage( array(
          'error' => 'membersonly',
          'url'   => sprintf( tools::$membersonly_login_url, rawurlencode( @$_SERVER['REQUEST_URI'] ) ),
        )
      );
    
  */
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
    
    $smarty  = $this->bootstrap->getSmarty();
    $user    = $this->bootstrap->getUser();
    $session = $this->bootstrap->getSession('rating');
    
    if ( ( $access = $recordingsModel->userHasAccess( $user ) ) !== true )
      $this->redirectToController('contents', $access );
    
    // TODO relatedvideos, json generalast smarty pluginba, magat a tomb generalast a modelbe
    // ugyanez slidokra
    $smarty->assign('comments',     $recordingsModel->getComments() );
    $smarty->assign('commentcount', $recordingsModel->getCommentsCount() );
    
    $smarty->assign('canrate',  $session[ $recordingsModel->id ] );
    $this->output( $smarty->fetch('Visitor/Recordings/Details.tpl') );
    
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
  
}
