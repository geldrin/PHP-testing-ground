<?php
namespace Visitor\Channels\Paging;

class Details extends \Visitor\Paging {
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
    
    $l                 = $this->bootstrap->getLocalization();
    $this->foreachelse = $l('channels', 'details_foreachelse');
    $this->title       = $l('channels', 'details_title');
    parent::init();
    
  }
  
  protected function setupCount() {
    // TODO channels recordings listazas
    $this->channelModel = $this->bootstrap->getModel('channels');
    $this->channelModel->addFilter('parentid', 0 );
    $this->channelModel->addFilter('organizationid', $this->controller->organization['id'] );
    return $this->itemcount = $this->channelModel->getCount();
    
  }
  
  protected function getItems( $start, $limit, $orderby ) {
    return $this->channelModel->getArray( $start, $limit, false, $orderby );
  }
  
}
