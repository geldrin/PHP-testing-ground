<?php
namespace Visitor\Channels\Paging;

class Index extends \Visitor\Paging {
  protected $orderkey = 'creation_desc';
  protected $sort = array(
    'creation'      => 'id',
    'creation_desc' => 'id DESC',
  );
  protected $template          = 'Visitor/Channels/Paging/Index.tpl';
  protected $insertbeforepager = Array( 'Visitor/Channels/Paging/IndexBeforepager.tpl' );
  protected $channelModel;
  
  public function init() {
    
    $l                 = $this->bootstrap->getLocalization();
    $this->foreachelse = $l('channels', 'foreachelse');
    $this->title       = $l('channels', 'title');
    $this->controller->toSmarty['listclass'] = 'recordinglist';
    parent::init();
    
  }
  
  protected function setupCount() {
    $this->channelModel = $this->bootstrap->getModel('channels');
    $this->channelModel->addFilter('parentid', 0 );
    $this->channelModel->addFilter('ispublic', 1 );
    $this->channelModel->addFilter('organizationid', $this->controller->organization['id'] );
    return $this->itemcount = $this->channelModel->getCount();
    
  }
  
  protected function getItems( $start, $limit, $orderby ) {
    return $this->channelModel->getArray( $start, $limit, false, $orderby );
  }
  
}
