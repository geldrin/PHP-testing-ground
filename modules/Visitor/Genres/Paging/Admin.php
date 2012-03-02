<?php
namespace Visitor\Genres\Paging;

class Admin extends \Springboard\Controller\Paging {
  protected $orderkey = 'creation_desc';
  protected $sort = array(
    'creation'      => 'id',
    'creation_desc' => 'id DESC',
  );
  protected $insertbefore = Array( 'Visitor/Genres/Paging/AdminBefore.tpl' );
  protected $template = 'Visitor/Genres/Paging/Admin.tpl';
  protected $toSmarty = Array(
    'listclass' => 'treeadminlist',
  );
  
  public function init() {
    $this->foreachelse = 'No genres found';
    $this->title = 'Genres';
  }
  
  protected function setupCount() {
    return 1;
  }
  
  protected function getItems( $start, $limit, $orderby ) {
    $organization = $this->bootstrap->getOrganization();
    $genreModel = $this->bootstrap->getModel('genres');
    $genreModel->addFilter('organizationid', $organization->id );
    $genreModel->addFilter('parentid', '0', true, true, 'treearray');
    return $genreModel->getTreeArray();
  }
  
}
