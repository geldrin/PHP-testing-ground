<?php
namespace Visitor\Live\Paging;
class Index extends \Visitor\Paging {
  protected $orderkey = 'starttime_desc';
  protected $sort = array(
    'starttime_desc'  => 'starttimestamp DESC',
    'starttime'       => 'starttimestamp',
    'createtime_desc' => 'id DESC',
    'createtime'      => 'id',
  );
  protected $insertbeforepager = Array( 'Visitor/Live/Paging/IndexBeforepager.tpl' );
  protected $template = 'Visitor/Live/Paging/Index.tpl';
  protected $channelModel;
  
  public function init() {
    
    $l                 = $this->bootstrap->getLocalization();
    $this->foreachelse = $l('live','live_foreachelse');
    $this->title       = $l('','sitewide_live');
    $this->controller->toSmarty['listclass'] = 'channellist live';
    parent::init();
    
  }
  
  protected function setupCount() {
    
    $this->channelModel = $this->bootstrap->getModel('channels');
    
    return $this->itemcount = $this->channelModel->getLiveCount(
      $this->controller->organization['id']
    );
    
  }
  
  protected function getItems( $start, $limit, $orderby ) {
    
    $items = $this->channelModel->getLiveArray(
      $this->controller->organization['id'],
      $start, $limit, $orderby
    );
    
    return $items;
    
  }
  
}
