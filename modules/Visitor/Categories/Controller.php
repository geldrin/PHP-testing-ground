<?php
namespace Visitor\Categories;

class Controller extends \Visitor\Controller {
  public $permissions = array(
    'index'   => 'public',
    'details' => 'public',
    'admin'   => 'clientadmin',
    'create'  => 'clientadmin',
    'modify'  => 'clientadmin',
    'delete'  => 'clientadmin',
  );

  public $forms = array(
    'create' => 'Visitor\\Categories\\Form\\Create',
    'modify' => 'Visitor\\Categories\\Form\\Modify',
  );

  public $paging = array(
    'admin'          => 'Visitor\\Categories\\Paging\\Admin',
    'details'        => 'Visitor\\Categories\\Paging\\Details',
  );

  public $apisignature = array(
    'index' => array(
    ),
  );

  public function indexAction() {

    $categoryModel = $this->bootstrap->getModel('categories');
    $categories    = $categoryModel->cachedGetCategoryTree(
      $this->organization['id']
    );

    if ( $this->application->getParameter('module') == 'api' )
      return $categories;

    $this->toSmarty['categories'] = $categories;
    $this->smartyoutput('Visitor/Categories/Index.tpl');

  }

  public function deleteAction() {

    $categoryModel = $this->modelOrganizationAndIDCheck(
      'categories',
      $this->application->getNumericParameter('id')
    );
    $categoryModel->delete( $categoryModel->id );

    $this->redirect(
      $this->application->getParameter('forward', 'categories/index' )
    );

  }

}
