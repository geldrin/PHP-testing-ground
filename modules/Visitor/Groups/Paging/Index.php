<?php
namespace Visitor\Groups\Paging;

class Index extends \Visitor\Paging {
  protected $orderkey = 'name';
  protected $sort = array(
    'name'      => 'g.name',
    'name_desc' => 'g.name DESC',
  );
  protected $insertbeforepager = Array( 'Visitor/Groups/Paging/IndexBeforepager.tpl' );
  protected $template = 'Visitor/Groups/Paging/Index.tpl';
  protected $groupModel;
  protected $user;
  protected $perpage = 15;
  
  public function init() {
    
    $l                 = $this->bootstrap->getLocalization();
    $this->foreachelse = $l('groups', 'index_foreachelse');
    $this->title       = $l('groups', 'index_title');
    $this->user        = $this->bootstrap->getSession('user');
    $this->controller->toSmarty['listclass'] = 'treeadminlist';
    parent::init();
    
  }
  
  protected function setupCount() {
    $this->groupModel = $this->bootstrap->getModel('groups');
    return $this->itemcount = $this->groupModel->getGroupCount(
      $this->user,
      $this->controller->organization['id']
    );
  }
  
  protected function getItems( $start, $limit, $orderby ) {
    return $this->groupModel->getGroupArray(
      $start, $limit, $orderby,
      $this->user, $this->controller->organization['id']
    );
  }
  
}
