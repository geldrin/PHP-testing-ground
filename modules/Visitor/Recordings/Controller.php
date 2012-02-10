<?php
namespace Visitor\Recordings;

class Controller extends \Visitor\Controller {
  public $permissions = array(
    'index'  => 'public',
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
  
}
