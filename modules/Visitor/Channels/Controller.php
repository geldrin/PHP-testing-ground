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
    'search'              => 'member',
    'orderrecordings'     => 'uploader|editor|clientadmin',
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
  );
  
  public function deleteAction() {
    
    $channelModel = $this->modelOrganizationAndIDCheck(
      'channels',
      $this->application->getNumericParameter('id')
    );
    $l        = $this->bootstrap->getLocalization();
    $children = $channelModel->findChildrenIDs();

    // nem engedunk torolni csatornat aminek vannak gyerekei vagy kulonleges csatorna
    if ( empty( $children ) ) {
      $channelModel->markAsDeleted();
      $message = $l('channels', 'channels_deleted');
    } else
      $message = $l('channels', 'channels_deletefailed');

    $this->redirectWithMessage(
      $this->application->getParameter('forward', 'channels/mychannels'),
      $message
    );
    
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

  public function setorderAction() {

    $neworder = $this->application->getParameter('order');
    if ( empty( $neworder ) )
      $this->jsonOutput( array('status' => 'error', 'message' => 'nothingprovided') );

    $channelModel = $this->modelOrganizationAndUserIDCheck(
      'channels',
      $this->application->getNumericParameter('id')
    );

    $channelModel->startTrans();
    /* Get the current order of weights, exchange them for the new ones
     * the current order is simply an array of weights
     * the new order is an array of channelrecordingids
     */
    $currentorder = $channelModel->getRecordingWeights( $this->organization['id'] );

    if ( count( $neworder ) != count( $currentorder ) ) {

      $this->jsonOutput( array(
          'status' => 'error',
          'error'  => 'received order count does not equal server-side count',
        ), true
      );

    }

    foreach( $neworder as $key => $crid )
      $channelModel->setRecordingOrder( $crid, $currentorder[ $key ] );

    $channelModel->endTrans();
    $this->jsonoutput( array(
        'status' => 'success',
      )
    );

  }

}
