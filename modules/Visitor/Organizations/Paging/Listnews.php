<?php
namespace Visitor\Organizations\Paging;

class Listnews extends \Springboard\Controller\Paging {
  protected $orderkey = 'starts_desc';
  protected $sort = array(
    'starts'      => 'weight, starts',
    'starts_desc' => 'weight, starts DESC',
  );
  protected $insertbefore = Array( 'Visitor/Organizations/Paging/ListnewsBefore.tpl' );
  protected $template = 'Visitor/Organizations/Paging/Listnews.tpl';
  protected $newsModel;
  
  public function init() {
    
    $l                 = $this->bootstrap->getLocalization();
    $this->foreachelse = $l('organizations', 'listnews_foreachelse');
    $this->title       = $l('organizations', 'listnews_title');
    $this->controller->toSmarty['listclass'] = 'newslist';
    parent::init();
    
  }
  
  protected function setupCount() {
    
    $user = $this->bootstrap->getSession('user');
    $this->newsModel = $this->bootstrap->getModel('organizations_news');
    $this->newsModel->addFilter('organizationid', $this->controller->organization['id'] );
    
    if ( !$user['iseditor'] or $user['organizationid'] != $this->controller->organization['id'] ) {
      
      $this->newsModel->addFilter('disabled', 0 );
      $this->newsModel->addTextFilter('starts <= NOW() AND ends >= NOW()');
      
    } else
      $this->controller->toSmarty['canadminister'] = true;
    
    return $this->itemcount = $this->newsModel->getCount();
    
  }
  
  protected function getItems( $start, $limit, $orderby ) {
    return $this->newsModel->getArray( $start, $limit, false, $orderby );
  }
  
}
