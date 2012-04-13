<?php
namespace Visitor\Search\Paging;
class All extends \Springboard\Controller\Paging {
  protected $orderkey = 'relevancy';
  protected $sort = array(
    'relevancy'              => 'relevancy DESC',
    'recordedtimestamp_desc' => 'recordedtimestamp DESC',
    'recordedtimestamp'      => 'recordedtimestamp',
  );
  
  protected $insertbeforepager = Array( 'Visitor/Search/Paging/AllBeforepager.tpl' );
  protected $template = 'Visitor/Search/Paging/All.tpl';
  protected $recordingsModel;
  
  public function init() {
    
    $l                 = $this->bootstrap->getLocalization();
    $this->foreachelse = $l('', 'foreachelse');
    $this->title       = $l('search', 'all_title');
    $this->controller->toSmarty['listclass'] = 'recordinglist';
    parent::init();
    
    $this->searchterm =
      mb_strlen( $this->application->getParameter('q') ) >= 3
        ? $this->application->getParameter('q')
        : null
    ;
    
  }
  
  protected function setupCount() {
    
    $this->recordingsModel = $this->bootstrap->getModel('recordings');
    
    return $this->itemcount = $this->recordingsModel->getSearchAllCount(
      $this->controller->organization['id'],
      $this->searchterm
    );
    
  }
  
  protected function getItems( $start, $limit, $orderby ) {
    
    $items = $this->recordingsModel->getSearchAllArray(
      $this->controller->organization['id'],
      $this->searchterm,
      $start, $limit, $orderby
    );
    
    return $items;
    
  }
  
}
