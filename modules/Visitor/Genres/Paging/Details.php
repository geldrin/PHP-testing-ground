<?php
namespace Visitor\Genres\Paging;

class Details extends \Springboard\Controller\Paging {
  protected $orderkey = 'creation_desc';
  protected $sort = array(
    'creation'      => 'id',
    'creation_desc' => 'id DESC',
  );
  protected $template = 'Visitor/Genres/Paging/Details.tpl';
  protected $genreModel;
  
  public function init() {
    $this->foreachelse = 'No genres found';
    $this->title = 'Genres';
  }
  
  protected function setupCount() {
    // TODO recording listazas
    $this->genreModel = $this->bootstrap->getModel('genres');
    $this->genreModel->addFilter('organizationid', $this->controller->organization['id'] );
    return $this->itemcount = $this->genreModel->getCount();
    
  }
  
  protected function getItems( $start, $limit, $orderby ) {
    return $this->genreModel->getArray( $start, $limit, false, $orderby );
  }
  
}
