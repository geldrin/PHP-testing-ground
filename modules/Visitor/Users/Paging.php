<?php
namespace Visitor\Users;
class Paging extends \Springboard\Controller\Paging {
  protected $orderkey = 'creation_desc';
  protected $sort = array(
    'creation'      => 'id',
    'creation_desc' => 'id DESC',
  );
  protected $template = 'Visitor/Users/paging/Users.tpl';
  
  public function init() {
    $this->foreachelse = 'No users found';
    $this->title = 'Users';
  }
  
  protected function setupCount() {
    
    $model = $this->bootstrap->getModel('users');
    return $this->itemcount = $model->getCount();
    
  }
  
  protected function getItems( $start, $limit, $orderby ) {
    $model = $this->bootstrap->getModel('users');
    return $model->getArray( $start, $limit, false, $orderby );
  }
  
}
