<?php
namespace Visitor\Users\Paging;

class Admin extends \Springboard\Controller\Paging {
  protected $orderkey = 'creation_desc';
  protected $sort = array(
    'creation'      => 'id',
    'creation_desc' => 'id DESC',
  );
  protected $insertbefore = Array( 'Visitor/Users/Paging/AdminBefore.tpl' );
  protected $template = 'Visitor/Users/Paging/Admin.tpl';
  protected $toSmarty = Array(
    'listclass' => 'treeadminlist',
  );
  protected $usersModel;
  
  public function init() {
    $this->foreachelse = 'No users found';
    $this->title = 'Users';
  }
  
  protected function setupCount() {
    
    $this->usersModel = $this->bootstrap->getModel('users');
    $this->usersModel->addFilter('organizationid', $this->controller->organization['id'] );
    return $this->itemcount = $this->usersModel->getCount();
    
  }
  
  protected function getItems( $start, $limit, $orderby ) {
    return $this->usersModel->getArray( $start, $limit, false, $orderby );
  }
  
}
