<?php
namespace Visitor\Live\Paging;
class Details extends \Visitor\Paging {
  protected $orderkey = 'createtime_desc';
  protected $sort = array(
    
    'createtime_desc' => 'id DESC',
    'createtime'      => 'id',
  );
  protected $insertbeforepager = Array( 'Visitor/Live/Paging/DetailsBeforepager.tpl' );
  protected $template = 'Visitor/Live/Paging/Details.tpl';
  protected $insertafterpager = Array( 'Visitor/Live/Paging/DetailsAfterpager.tpl' );
  protected $channelModel;
  
  public function init() {
    
    $l                 = $this->bootstrap->getLocalization();
    $this->foreachelse = $l('live','live_foreachelse');
    $this->title       = $l('','sitewide_live');
    $this->channelModel = $this->controller->modelIDCheck(
      'channels',
      $this->application->getNumericParameter('id')
    );
    
    $this->channelModel->clearFilter();
    $rootid = $this->channelModel->id;
    if ( $this->channelModel->row['parentid'] )
      $rootid = $this->channelModel->findRootID( $this->channelModel->row['parentid'] );;
    
    $channeltree = $this->channelModel->getSingleChannelTree( $rootid );
    
    $this->controller->toSmarty['channeltree'] = $channeltree;
    $this->controller->toSmarty['listclass']   = 'recordinglist';
    $this->controller->toSmarty['feeds']       = $this->channelModel->getFeeds();
    $this->controller->toSmarty['channel']     = $this->channelModel->row;
    
    $this->controller->toSmarty['streamingactive'] =
      ( strtotime( $this->channelModel->row['starttimestamp'] ) <= time() ) and
      (
        !strlen( $this->channelModel->row['endtimestamp'] )
        or
        ( strtotime( $this->channelModel->row['endtimestamp'] ) >= time() )
      )
    ;
    
    parent::init();
    
  }
  
  protected function setupCount() {
    
    return $this->itemcount = $this->channelModel->getLiveRecordingCount();
    
  }
  
  protected function getItems( $start, $limit, $orderby ) {
    
    $items = $this->channelModel->getLiveRecordingArray( $start, $limit, $orderby );
    
    return $items;
    
  }
  
}
