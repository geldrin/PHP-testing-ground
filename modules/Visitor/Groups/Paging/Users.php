<?php
namespace Visitor\Groups\Paging;

class Users extends \Visitor\Paging {
  protected $orderkey = 'creation_desc';
  protected $sort = array(
    'creation'      => 'id',
    'creation_desc' => 'id DESC',
  );
  protected $insertbeforepager = Array( 'Visitor/Groups/Paging/UsersBeforepager.tpl' );
  protected $template = 'Visitor/Groups/Paging/Users.tpl';
  protected $groupModel;
  protected $usersModel;
  protected $searchterm;
  protected $perpage = 20;

  public function init() {
    $l                 = $this->bootstrap->getLocalization();
    $this->foreachelse = $l('groups', 'users_foreachelse');
    $this->title       = $l('groups', 'users_title');
    $this->groupModel  = $this->controller->modelOrganizationAndIDCheck(
      'groups',
      $this->application->getNumericParameter('id')
    );

    $this->controller->toSmarty['group'] = $this->groupModel->row;
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
      '/' . $this->groupModel->id . ',' .
      \Springboard\Filesystem::filenameize( $this->groupModel->row['name'] )
    ;
  }

  protected function setupCount() {

    if ( $this->searchterm ) {
      return $this->itemcount = $this->groupModel->getSearchCount(
        $this->searchterm,
        $this->controller->organization,
        $this->usersModel
      );
    }

    return $this->itemcount = $this->groupModel->getUserCount();
  }

  protected function getItems( $start, $limit, $orderby ) {

    if ( $this->searchterm ) {
      return $this->groupModel->getSearchArray(
        $this->searchterm,
        $this->controller->organization,
        $this->usersModel,
        $start, $limit, 'relevancy, u.email'
      );
    }

    return $this->groupModel->getUserArray( $start, $limit, $orderby );
  }

}
