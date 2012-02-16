<?php
namespace Visitor\Channels\Paging;

class Index extends \Springboard\Controller\Paging {
  protected $orderkey = 'creation_desc';
  protected $sort = array(
    'creation'      => 'id',
    'creation_desc' => 'id DESC',
  );
  protected $template = 'Visitor/Channels/Paging/Index.tpl';
  protected $channelModel;
  
  public function init() {
    $this->foreachelse = 'No channels found';
    $this->title = 'Channels';
  }
  
  protected function setupCount() {
    // TODO channels listazas
    $organization = $this->bootstrap->getOrganization();
    $this->channelModel = $this->bootstrap->getModel('channels');
    $this->channelModel->addFilter('parentid', 0 );
    $this->channelModel->addFilter('organizationid', $organization->id );
    return $this->itemcount = $this->channelModel->getCount();
    
  }
  
  protected function getItems( $start, $limit, $orderby ) {
    return $this->channelModel->getArray( $start, $limit, false, $orderby );
  }
  
}
