<?php
namespace Visitor\Search\Paging;
class All extends \Visitor\Paging {
  protected $orderkey = 'relevancy_desc';
  protected $sort = array(
    'relevancy_desc'         => 'relevancy DESC',
    'recordedtimestamp_desc' => 'recordedtimestamp DESC',
    'recordedtimestamp'      => 'recordedtimestamp',
  );
  
  protected $insertbeforepager = Array( 'Visitor/Search/Paging/AllBeforepager.tpl' );
  protected $template = 'Visitor/Search/Paging/All.tpl';
  protected $recordingsModel;
  protected $user;
  
  public function init() {
    
    $l                 = $this->bootstrap->getLocalization();
    $this->user        = $this->bootstrap->getSession('user');
    $this->foreachelse = $l('', 'foreachelse');
    $this->title       = $l('search', 'all_title');
    $this->controller->toSmarty['listclass'] = 'recordinglist';
    parent::init();
    
    $this->searchterm = $this->application->getParameter('q');
    
    if ( mb_strlen( $this->searchterm ) < 3 )
      $this->searchterm = null;
    
    $this->controller->toSmarty['searchterm'] = $this->searchterm;
    
    if ( !$this->searchterm )
     $this->foreachelse = $l('search', 'search_minimum_3chars');
    
  }
  
  protected function setupPager() {
    parent::setupPager();
    $this->pager->pass('q', $this->searchterm );
  }
  
  protected function setupCount() {
    
    if ( !$this->searchterm )
      return $this->itemcount = 0;
    
    $this->recordingsModel = $this->bootstrap->getModel('recordings');
    
    return $this->itemcount = $this->recordingsModel->getSearchAllCount(
      $this->user,
      $this->controller->organization['id'],
      $this->searchterm
    );
    
  }
  
  protected function getItems( $start, $limit, $orderby ) {
    
    if ( !$this->searchterm )
      return array();
    
    $items = $this->recordingsModel->getSearchAllArray(
      $this->user,
      $this->controller->organization['id'],
      $this->searchterm,
      $start, $limit, $orderby
    );
    
    $items = $this->recordingsModel->addPresentersToArray(
      $items,
      true,
      $this->controller->organization['id']
    );
    
    return $items;
    
  }
  
}
