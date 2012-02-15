<?php
namespace Visitor\Categories\Paging;

class Details extends \Springboard\Controller\Paging {
  protected $orderkey = 'creation_desc';
  protected $sort = array(
    'creation'      => 'id',
    'creation_desc' => 'id DESC',
  );
  protected $template = 'Visitor/Categories/Paging/Details.tpl';
  protected $categoryModel;
  
  public function init() {
    $this->foreachelse = 'No categories found';
    $this->title = 'Categories';
  }
  
  protected function setupCount() {
    // TODO recording listazas
    $organization = $this->bootstrap->getOrganization();
    $this->categoryModel = $this->bootstrap->getModel('categories');
    $this->categoryModel->addFilter('organizationid', $organization->id );
    return $this->itemcount = $this->categoryModel->getCount();
    
  }
  
  protected function getItems( $start, $limit, $orderby ) {
    return $this->categoryModel->getArray( $start, $limit, false, $orderby );
  }
  
}
