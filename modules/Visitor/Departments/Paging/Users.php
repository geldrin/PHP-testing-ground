<?php
namespace Visitor\Departments\Paging;

class Users extends \Visitor\Paging {
  protected $orderkey = 'creation_desc';
  protected $sort = array(
    'creation'      => 'id',
    'creation_desc' => 'id DESC',
  );
  protected $insertbeforepager = Array( 'Visitor/Departments/Paging/UsersBeforepager.tpl' );
  protected $template = 'Visitor/Departments/Paging/Users.tpl';
  protected $departmentModel;
  protected $usersModel;
  protected $searchterm;
  protected $perpage = 20;

  public function init() {
    $l                 = $this->bootstrap->getLocalization();
    $this->foreachelse = $l('departments', 'users_foreachelse');
    $this->title       = $l('departments', 'users_title');
    $this->departmentModel  = $this->controller->modelOrganizationAndIDCheck(
      'departments',
      $this->application->getNumericParameter('id')
    );
    
    $this->controller->toSmarty['department'] = $this->departmentModel->row;
    $this->controller->toSmarty['listclass'] = 'treeadminlist';
    $term = trim( $this->application->getParameter('term') );
    if ( mb_strlen( $term ) >= 2 ) {
      $this->searchterm = $term;
      $this->passparams['term'] = $term;
      $this->usersModel = $this->bootstrap->getModel('users');
    }

  }
  
  protected function getUrl() {
    return
      $this->controller->getUrlFromFragment( $this->module . '/' . $this->action ) .
      '/' . $this->departmentModel->id . ',' .
      \Springboard\Filesystem::filenameize( $this->departmentModel->row['name'] )
    ;
  }
  
  protected function setupCount() {

    if ( $this->searchterm ) {
      return $this->itemcount = $this->departmentModel->getSearchCount(
        $this->searchterm,
        $this->controller->organization,
        $this->usersModel
      );
    }

    return $this->itemcount = $this->departmentModel->getUserCount();
  }
  
  protected function getItems( $start, $limit, $orderby ) {

    if ( $this->searchterm ) {
      return $this->departmentModel->getSearchArray(
        $this->searchterm,
        $this->controller->organization,
        $this->usersModel,
        $start, $limit, 'relevancy, u.email'
      );
    }

    return $this->departmentModel->getUserArray( $start, $limit, $orderby );
  }
  
}
