<?php
namespace Visitor\Users\Paging;

class Admin extends \Visitor\Paging {
  public $toSmarty = Array(
    'listclass' => 'treeadminlist',
  );
  
  protected $orderkey = 'creation_desc';
  protected $sort = array(
    'creation'      => 'id',
    'creation_desc' => 'id DESC',
  );
  protected $insertbeforepager = Array( 'Visitor/Users/Paging/AdminBeforepager.tpl' );
  protected $template = 'Visitor/Users/Paging/Admin.tpl';
  
  protected $usersModel;
  
  public function init() {
    $l                 = $this->bootstrap->getLocalization();
    $this->foreachelse = $l('users', 'foreachelse' );
    $this->title       = $l('users', 'admin_title');
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
