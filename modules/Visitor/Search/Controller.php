<?php
namespace Visitor\Search;

class Controller extends \Visitor\Controller {
  public $permissions = array(
    'index'                => 'public',
    'all'                  => 'public',
  );
  
  public $paging = array(
    'all' => 'Visitor\\Search\\Paging\\All',
  );
  
  public function indexAction() {
    $this->redirect('search/all');
  }
  
}
