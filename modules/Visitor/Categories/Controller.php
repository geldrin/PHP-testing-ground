<?php
namespace Visitor\Categories;

class Controller extends \Visitor\Controller {
  public $permissions = array(
    'index'   => 'public',
    'details' => 'public',
    'admin'   => 'admin',
    'create'  => 'admin',
    'modify'  => 'admin',
    'delete'  => 'admin',
  );
  
  public $forms = array(
    'create' => 'Visitor\\Categories\\Form\\Create',
    'modify' => 'Visitor\\Categories\\Form\\Modify',
  );
  
  public $paging = array(
    'admin'          => 'Visitor\\Categories\\Paging\\Admin',
    'details'        => 'Visitor\\Categories\\Paging\\Details',
  );
  
  public function indexAction() {
    
    $smarty        = $this->bootstrap->getSmarty();
    $organization  = $this->bootstrap->getOrganization();
    $categoryModel = $this->bootstrap->getModel('categories');
    
    $smarty->assign('categories', $categoryModel->cachedGetCategoryTree( $organization->id ) );
    $this->output( $smarty->fetch('Visitor/Categories/Index.tpl') );
    
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
