<?php
namespace Visitor\Categories\Paging;

class Admin extends \Springboard\Controller\Paging {
  protected $orderkey = 'creation_desc';
  protected $sort = array(
    'creation'      => 'id',
    'creation_desc' => 'id DESC',
  );
  protected $insertbefore = Array( 'Visitor/Categories/Paging/AdminBefore.tpl' );
  protected $template = 'Visitor/Categories/Paging/Admin.tpl';
  protected $toSmarty = Array(
    'listclass' => 'treeadminlist',
  );
  
  public function init() {
    $this->foreachelse = 'No categories found';
    $this->title = 'Categories';
  }
  
  protected function setupCount() {
    
    return $this->itemcount = 1;
    
  }
  
  protected function getItems( $start, $limit, $orderby ) {
    $organization = $this->bootstrap->getOrganization();
    $categoryModel = $this->bootstrap->getModel('categories');
    $items = $categoryModel->getCategoryTree( $organization->id, 0, 8 );
    return $items;
  }
  
}
