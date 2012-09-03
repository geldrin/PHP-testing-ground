<?php
namespace Visitor\Departments\Paging;

class Admin extends \Visitor\Paging {
  protected $orderkey = 'creation_desc';
  protected $sort = array(
    'creation'      => 'id',
    'creation_desc' => 'id DESC',
  );
  protected $insertbeforepager = Array( 'Visitor/Departments/Paging/AdminBeforepager.tpl' );
  protected $template = 'Visitor/Departments/Paging/Admin.tpl';
  
  public function init() {
    
    $l                 = $this->bootstrap->getLocalization();
    $this->foreachelse = $l('departments', 'admin_foreachelse');
    $this->title       = $l('departments', 'admin_title');
    $this->controller->toSmarty['listclass'] = 'treeadminlist';
    parent::init();
    
  }
  
  protected function setupCount() {
    return $this->itemcount = 1;
  }
  
  protected function getItems( $start, $limit, $orderby ) {
    $departmentModel = $this->bootstrap->getModel('departments');
    $items = $departmentModel->getDepartmentTree( $this->controller->organization['id'], 0, 8 );
    return $items;
  }
  
}
