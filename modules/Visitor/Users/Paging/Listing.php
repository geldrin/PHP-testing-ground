<?php
namespace Visitor\Users\Paging;

class Listing extends \Springboard\Controller\Paging {
  protected $orderkey = 'creation_desc';
  protected $sort = array(
    'creation'      => 'id',
    'creation_desc' => 'id DESC',
  );
  protected $template = 'Visitor/Users/Paging/Listing.tpl';
  protected $usersModel;
  
  public function init() {
    $this->foreachelse = 'No users found';
    $this->title = 'Users';
  }
  
  protected function setupCount() {
    
    $organization = $this->bootstrap->getOrganization();
    $this->usersModel = $this->bootstrap->getModel('users');
    $this->usersModel->addFilter('organizationid', $organization->id );
    return $this->itemcount = $this->usersModel->getCount();
    
  }
  
  protected function getItems( $start, $limit, $orderby ) {
    return $this->usersModel->getArray( $start, $limit, false, $orderby );
  }
  
}
