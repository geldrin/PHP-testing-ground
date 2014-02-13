<?php
namespace Visitor\Users\Paging;

class Admin extends \Visitor\Paging {
  protected $orderkey = 'email';
  protected $sort = array(
    'email'         => 'email',
    'email_desc'    => 'email DESC',
    'creation'      => 'id',
    'creation_desc' => 'id DESC',
  );
  protected $insertbeforepager = Array( 'Visitor/Users/Paging/AdminBeforepager.tpl' );
  protected $template = 'Visitor/Users/Paging/Admin.tpl';
  protected $perpage  = 10;
  protected $usersModel;
  protected $pagestoshow = 3;
  protected $searchterm;

  public function init() {
    
    $l                 = $this->bootstrap->getLocalization();
    $this->foreachelse = $l('users', 'foreachelse' );
    $this->title       = $l('users', 'admin_title');
    $this->controller->toSmarty['listclass'] = 'treeadminlist';
    $term = trim( $this->application->getParameter('term') );
    if ( mb_strlen( $term ) >= 2 ) {
      $this->searchterm = $term;
      $this->passparams['term'] = $term;
    }

  }
  
  protected function setupCount() {
    
    $this->usersModel = $this->bootstrap->getModel('users');

    if ( $this->searchterm ) {
      return $this->itemcount = $this->usersModel->getSearchCount(
        $this->searchterm,
        $this->controller->organization
      );
    }

    $this->usersModel->addFilter('organizationid', $this->controller->organization['id'] );
    $this->usersModel->addFilter('isadmin', 0 );
    return $this->itemcount = $this->usersModel->getCount();
    
  }
  
  protected function getItems( $start, $limit, $orderby ) {

    if ( $this->searchterm ) {
      return $this->usersModel->getSearchArray(
        $this->searchterm,
        $this->controller->organization,
        $start, $limit, 'relevancy, email'
      );
    }

    return $this->usersModel->getArray( $start, $limit, false, $orderby );
  }
  
}
