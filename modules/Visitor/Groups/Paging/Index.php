<?php
namespace Visitor\Groups\Paging;

class Index extends \Visitor\Paging {
  protected $orderkey = 'creation_desc';
  protected $sort = array(
    'creation'      => 'id',
    'creation_desc' => 'id DESC',
  );
  protected $insertbeforepager = Array( 'Visitor/Groups/Paging/IndexBeforepager.tpl' );
  protected $template = 'Visitor/Groups/Paging/Index.tpl';
  protected $groupModel;
  protected $user;
  
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
    return $this->groupModel->getGroupCount( $this->user );
  }
  
  protected function getItems( $start, $limit, $orderby ) {
    return $this->groupModel->getGroupArray( $start, $limit, $orderby, $this->user );
  }
  
}
