<?php
namespace Visitor\Channels\Paging;

class Details extends \Springboard\Controller\Paging {
  protected $orderkey = 'recordedtime';
  protected $sort            = Array(
    //'recordedtime'      => 'c.weight, recordedtimestamp',
    'recordedtime'      => 'id',
    'recordedtime_desc' => 'c.weight, recordedtimestamp DESC',
    'createtime_desc'   => 'id DESC',
    'createtime'        => 'id',
    'name_desc'         => 'name DESC',
    'name'              => 'name',
    'weighted'          => 'c.weight, cr.weight', // nincsen felulet sima channeleket rendezni, igy nem ez a default
    'weighted_desc'     => 'c.weight, cr.weight DESC',
  );
  protected $template = 'Visitor/Channels/Paging/Details.tpl';
  //protected $insertbeforepager = Array( 'Visitor/Channels/Paging/DetailsBeforepager.tpl' );
  //protected $insertafterpager  = Array( 'Visitor/Channels/Paging/DetailsAfterpager.tpl' );
  protected $toSmarty = Array(
    'listclass' => 'recordinglist',
  );
  
  protected $channelModel;
  
  public function init() {
    $this->foreachelse = 'No channels found';
    $this->title = 'Channels';
  }
  
  protected function setupCount() {
    // TODO channels recordings listazas
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
