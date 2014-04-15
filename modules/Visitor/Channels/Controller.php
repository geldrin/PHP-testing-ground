<?php
namespace Visitor\Channels;

class Controller extends \Visitor\Controller {
  public $permissions = array(
    'index'               => 'public',
    'details'             => 'public',
    'create'              => 'uploader|editor|clientadmin',
    'modify'              => 'uploader|editor|clientadmin',
    'delete'              => 'uploader|editor|clientadmin',
    'mychannels'          => 'member',
    'addrecording'        => 'member',
    'deleterecording'     => 'member',
    'listfavorites'       => 'member',
    'addtofavorites'      => 'member',
    'deletefromfavorites' => 'member',
    'search'              => 'member',
    'orderrecordings'     => 'uploader|editor|clientadmin',
    'swaporder'           => 'uploader|editor|clientadmin',
    'setorder'            => 'uploader|editor|clientadmin',
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
  
  public function searchAction() {
    
    $term   = $this->application->getParameter('term');
    $output = array(
    );
    
    if ( !$term )
      $this->jsonoutput( $output );
    
    $user         = $this->bootstrap->getSession('user');
    $channelModel = $this->bootstrap->getModel('channels');
    $results      = $channelModel->search( $term, $user['id'], $this->organization['id'] );
    
    if ( empty( $results ) )
      $this->jsonoutput( $output );
    
    foreach( $results as $result ) {
      
      $title = $result['title'];
      if ( strlen( trim( $result['subtitle'] ) ) )
        $title .= '<br/>' . $result['subtitle'];

      $data = array(
        'value' => $result['id'],
        'label' => $title,
        'img'   => $this->bootstrap->staticuri,
      );
      
      if ( $result['indexphotofilename'] )
        $data['img'] .= 'files/' . $result['indexphotofilename'];
      else
        $data['img'] .= 'images/videothumb_audio_placeholder.png';
      
      $output[] = $data;
      
    }
    
    $this->jsonoutput( $output );
    
  }
  
  public function orderrecordingsAction() {

    $channelModel = $this->modelOrganizationAndUserIDCheck(
      'channels',
      $this->application->getNumericParameter('id')
    );

    $items = $channelModel->getRecordings(
      $this->organization['id']
    );

    $items = $this->bootstrap->getModel('recordings')->addPresentersToArray(
      $items, true, $this->organization['id']
    );

    $helpModel = $this->bootstrap->getModel('help_contents');
    $helpModel->addFilter('shortname', 'channels_orderrecordings', false, false );
    
    $this->toSmarty['help']    = $helpModel->getRow();
    $this->toSmarty['items']   = $items;
    $this->toSmarty['channel'] = $channelModel->row;
    $this->toSmarty['forward'] = $this->application->getParameter(
      'forward', \Springboard\Language::get() . '/channels/mychannels'
    );
    $this->smartyOutput('Visitor/Channels/Orderrecordings.tpl');

  }

  public function swaporderAction() {

    $channelModel = $this->modelOrganizationAndUserIDCheck(
      'channels',
      $this->application->getNumericParameter('id')
    );

    $channelModel->startTrans();
    $channelrecwhatModel  = $this->modelIDCheck(
      'channels_recordings',
      $this->application->getNumericParameter('what')
    );
    $channelrecwhereModel = $this->modelIDCheck(
      'channels_recordings',
      $this->application->getNumericParameter('where')
    );
    
    if (
         $channelrecwhatModel->row['channelid'] != $channelModel->id or
         $channelrecwhatModel->row['channelid'] != $channelModel->id
       )
      $this->redirect();
    
    if ( $channelrecwhereModel->row['weight'] == $channelrecwhatModel->row['weight'] ) {

      $d = \Springboard\Debug::getInstance();
      $d->log(
        false,
        'channels.txt',
        'channels/swaporder failed, a honnan/hova sulyozasa megegyezik (' .
        $channelrecwhatModel->row['weight'] . '); honnan id: ' . $channelrecwhatModel->id .
        ' hova id: ' . $channelrecwhereModel->id,
        true
      );

      if ( $this->isAjaxRequest() )
        $this->jsonoutput( array('status' => 'error') );
      else
        $this->redirectWithMessage(
          $this->application->getParameter('forward', 'channels/orderrecordings') .
          '#cr' . $channelrecwhatModel->id,
          'System error occured, our teams have been notified, sorry for the inconvenience'
        );

    }

    $whatweight = $channelrecwhatModel->row['weight'];
    $channelrecwhatObj->updateRow( array(
        'weight' => $channelrecwhereModel->row['weight'],
      )
    );
    
    $channelrecwhereObj->updateRow( array(
        'weight' => $whatweight,
      )
    );

    $channelModel->endTrans();

    if ( $this->isAjaxRequest() )
      $this->jsonoutput( array('status' => 'success') );
    else
      $this->redirect(
        $this->application->getParameter('forward', 'channels/orderrecordings') .
        '#cr' . $channelrecwhatModel->id
      );

  }

  public function setorderAction() {

    $order = $this->application->getParameter('order');
    if ( empty( $order ) )
      $this->jsonOutput( array('status' => 'error', 'message' => 'nothingprovided') );

    $channelModel = $this->modelOrganizationAndUserIDCheck(
      'channels',
      $this->application->getNumericParameter('id')
    );

    $channelModel->startTrans();
    $currentorder = $channelModel->getRecordingWeights( $this->organization['id'] );

    if ( count( $order ) != count( $currentorder ) ) {

      $this->jsonOutput( array(
          'status' => 'error',
          'error'  => 'received order count does not equal server-side count',
        ), true
      );

    }

    foreach( $order as $key => $crid )
      $channelModel->setRecordingOrder( $crid, $currentorder[ $key ] );

    $channelModel->endTrans();
    $this->jsonoutput( array(
        'status' => 'success',
      )
    );

  }

}
