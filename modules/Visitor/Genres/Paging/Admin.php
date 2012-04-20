<?php
namespace Visitor\Genres\Paging;

class Admin extends \Visitor\Paging {
  protected $orderkey = 'creation_desc';
  protected $sort = array(
    'creation'      => 'id',
    'creation_desc' => 'id DESC',
  );
  protected $insertbefore = Array( 'Visitor/Genres/Paging/AdminBefore.tpl' );
  protected $template = 'Visitor/Genres/Paging/Admin.tpl';
  
  public function init() {
    
    $l                 = $this->bootstrap->getLocalization();
    $this->foreachelse = $l('genres', 'admin_foreachelse');
    $this->title       = $l('genres', 'admin_title');
    $this->controller->toSmarty['listclass'] = 'treeadminlist';
    parent::init();
    
  }
  
  protected function setupCount() {
    return 1;
  }
  
  protected function getItems( $start, $limit, $orderby ) {
    $genreModel = $this->bootstrap->getModel('genres');
    $genreModel->addFilter('organizationid', $this->controller->organization['id'] );
    $genreModel->addFilter('parentid', '0', true, true, 'treearray');
    return $genreModel->getTreeArray();
  }
  
}
