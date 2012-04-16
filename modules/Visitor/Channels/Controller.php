<?php
namespace Visitor\Channels;

class Controller extends \Visitor\Controller {
  public $permissions = array(
    'index'               => 'public',
    'details'             => 'public',
    'create'              => 'member',
    'modify'              => 'member',
    'delete'              => 'member',
    'mychannels'          => 'member',
    'addrecording'        => 'member',
    'deleterecording'     => 'member',
    'listfavorites'       => 'member',
    'addtofavorites'      => 'member',
    'deletefromfavorites' => 'member',
  );
  
  public $forms = array(
    'create' => 'Visitor\\Channels\\Form\\Create',
    'modify' => 'Visitor\\Channels\\Form\\Modify',
  );
  
  public $paging = array(
    'index'          => 'Visitor\\Channels\\Paging\\Index',
    'details'        => 'Visitor\\Channels\\Paging\\Details',
    'mychannels'     => 'Visitor\\Channels\\Paging\\Mychannels',
    'listfavorites'  => 'Visitor\\Channels\\Paging\\Listfavorites',
  );
  
  public function deleteAction() {
    
    $channelModel = $this->modelOrganizationAndIDCheck(
      'channels',
      $this->application->getNumericParameter('id')
    );
    $channelModel->delete( $channelModel->id );
    
    $this->redirect(
      $this->application->getParameter('forward', 'channels/mychannels')
    );
    
  }
  
  public function addtofavoritesAction() {
    
    $user           = $this->bootstrap->getSession('user');
    $recordingModel = $this->modelIDCheck(
      'recordings',
      $this->application->getNumericParameter('id')
    ); // $_GET[id] az a recordingid
    $channelModel   = $this->bootstrap->getModel('channels');
    
    $channelModel->insertIntoFavorites( $recordingModel->id, $user );
    
    if ( $this->isAjaxRequest() )
      $this->jsonoutput( array(
          'success' => true,
        )
      );
    
    $this->redirect( $this->application->getParameter('forward') );
    
  }
  
  public function deletefromfavoritesAction() {
    
    // $_GET[id] az a channels_recordings.id
    $channelrecordingModel = $this->modelUserAndIDCheck(
      'channels_recordings',
      $this->application->getNumericParameter('id')
    );
    $channelrecordingModel->delete( $channelrecordingModel->id );
    
    $this->redirect( $this->application->getParameter('forward') );
    
  }
  
  public function addrecordingAction() {
    
    $recordingid    = $this->application->getNumericParameter('recordingid');
    
    if ( $recordingid <= 0 )
      $this->redirect('index');
    
    $user           = $this->bootstrap->getSession('user');
    $channelModel   = $this->modelOrganizationAndUserIDCheck(
      'channels',
      $this->application->getNumericParameter('id')
    );
    $recordingModel = $this->bootstrap->getModel('recordings');
    $recordingModel->addFilter('id', $recordingid );
    
    if ( !$recordingModel->getCount() )
      $this->redirect('index');
    
    if ( $channelModel->insertIntoChannel( $recordingid, $user ) ) {
      
      $channelModel->updateIndexFilename();
      $channelModel->updateVideoCounters();
      
    }
    
    if ( $this->isAjaxRequest() )
      $this->jsonoutput( array('status' => 'success') );
    else
      $this->redirect( $this->application->getParameter('forward') );
    
  }
  
  public function deleterecordingAction() {
    
    // $_GET[id] az a channels_recordings.id
    $channelrecordingModel = $this->modelIDCheck(
      'channels_recordings',
      $this->application->getNumericParameter('id')
    );
    
    $channelModel = $this->modelOrganizationAndUserIDCheck(
      'channels',
      $channelrecordingModel->row['channelid']
    );
    
    $channelrecordingModel->delete( $channelrecordingModel->id );
    
    $channelModel->updateIndexFilename( true );
    $channelModel->updateVideoCounters();
    
    $this->redirect( $this->application->getParameter('forward') );
    
  }
  
}
