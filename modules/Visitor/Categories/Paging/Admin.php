<?php
namespace Visitor\Categories\Paging;

class Admin extends \Visitor\Paging {
  protected $orderkey = 'creation_desc';
  protected $sort = array(
    'creation'      => 'id',
    'creation_desc' => 'id DESC',
  );
  protected $insertbeforepager = Array( 'Visitor/Categories/Paging/AdminBeforepager.tpl' );
  protected $template = 'Visitor/Categories/Paging/Admin.tpl';

  public function init() {

    $l                 = $this->bootstrap->getLocalization();
    $this->foreachelse = $l('categories', 'admin_foreachelse');
    $this->title       = $l('categories', 'admin_title');
    $this->controller->toSmarty['listclass'] = 'treeadminlist';
    parent::init();

  }

  protected function setupCount() {
    return $this->itemcount = null;
  }

  protected function getItems( $start, $limit, $orderby ) {
    $categoryModel = $this->bootstrap->getModel('categories');
    $items = $categoryModel->getCategoryTree( $this->controller->organization['id'], 0, 8 );
    return $items;
  }

}
