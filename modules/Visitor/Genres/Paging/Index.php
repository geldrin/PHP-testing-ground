<?php
namespace Visitor\Genres\Paging;

class Index extends \Springboard\Controller\Paging {
  protected $orderkey = 'creation_desc';
  protected $sort = array(
    'creation'      => 'id',
    'creation_desc' => 'id DESC',
  );
  protected $template = 'Visitor/Genres/Paging/Index.tpl';
  protected $genreModel;
  protected $perpage = 200;
  protected $perpageselector = false;
  public function init() {
    $this->foreachelse = 'No genres found';
    $this->title = 'Genres';
  }
  
  protected function setupCount() {
    $organization = $this->bootstrap->getOrganization();
    $this->genreModel = $this->bootstrap->getModel('genres');
    $this->genreModel->addFilter('organizationid', $organization->id );
    $this->genreModel->addFilter('parentid', '0', true, true, 'treearray');
    return $this->itemcount = $this->genreModel->getCount();
  }
  
  protected function getItems( $start, $limit, $orderby ) {
    return $this->genreModel->getTreeArray();
  }
  
}
