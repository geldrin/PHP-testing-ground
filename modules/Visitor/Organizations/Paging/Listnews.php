<?php
namespace Visitor\Organizations\Paging;

class Listnews extends \Visitor\Paging {
  protected $orderkey = 'starts_desc';
  protected $sort = array(
    'starts'      => 'weight, starts',
    'starts_desc' => 'weight, starts DESC',
  );
  protected $insertbeforepager = Array( 'Visitor/Organizations/Paging/ListnewsBeforepager.tpl' );
  protected $template = 'Visitor/Organizations/Paging/Listnews.tpl';
  protected $newsModel;
  protected $user;
  
  public function init() {
    
    $l                 = $this->bootstrap->getLocalization();
    $this->foreachelse = $l('organizations', 'listnews_foreachelse');
    $this->title       = $l('organizations', 'listnews_title');
    $this->controller->toSmarty['listclass'] = 'newslist';
    parent::init();
    
  }
  
  protected function setupCount() {
    
    $this->user      = $this->bootstrap->getSession('user');
    $this->newsModel = $this->bootstrap->getModel('organizations_news');
    
    if (
         (
           $this->user['iseditor'] or
           $this->user['isnewseditor'] or
           $this->user['isclientadmin'] or
           $this->user['isadmin']
         ) and
         $this->user['organizationid'] == $this->controller->organization['id']
       )
      $this->controller->toSmarty['canadminister'] = true;
    
    return $this->itemcount = $this->newsModel->getNewsCount(
      $this->controller->organization['id'],
      $this->user
    );
    
  }
  
  protected function getItems( $start, $limit, $orderby ) {
    return $this->newsModel->getNewsArray(
      $start, $limit, $orderby, $this->controller->organization['id'], $this->user
    );
  }
  
}
