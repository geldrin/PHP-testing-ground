<?php
namespace Visitor\Search;

class Controller extends \Visitor\Controller {
  public $permissions = array(
    'index'                => 'public',
    'all'                  => 'public',
    'advanced'             => 'public',
  );
  
  public $paging = array(
    'all'      => 'Visitor\\Search\\Paging\\All',
    'advanced' => 'Visitor\\Search\\Paging\\Advanced',
  );
  
  public function indexAction() {
    $this->redirect('search/all');
  }
  
}
