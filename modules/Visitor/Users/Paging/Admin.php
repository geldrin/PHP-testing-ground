<?php
namespace Visitor\Users\Paging;

class Admin extends \Visitor\Paging {
  protected $orderkey = 'nickname';
  protected $sort = array(
    'nickname'      => 'nickname',
    'nickname_desc' => 'nickname DESC',
    'creation'      => 'id',
    'creation_desc' => 'id DESC',
  );
  protected $insertbeforepager = Array( 'Visitor/Users/Paging/AdminBeforepager.tpl' );
  protected $template = 'Visitor/Users/Paging/Admin.tpl';
  protected $perpage  = 10;
  protected $usersModel;
  
  public function init() {
    
    $l                 = $this->bootstrap->getLocalization();
    $this->foreachelse = $l('users', 'foreachelse' );
    $this->title       = $l('users', 'admin_title');
    $this->controller->toSmarty['listclass'] = 'treeadminlist';
    
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
