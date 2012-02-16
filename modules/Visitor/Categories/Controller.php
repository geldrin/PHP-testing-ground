<?php
namespace Visitor\Categories;

class Controller extends \Visitor\Controller {
  public $permissions = array(
    'index'               => 'public',
    'details'             => 'public',
    'create'              => 'admin',
    'modify'              => 'admin',
    'delete'              => 'admin',
  );
  
  public $forms = array(
    'create' => 'Visitor\\Categories\\Form\\Create',
    'modify' => 'Visitor\\Categories\\Form\\Modify',
  );
  
  public $paging = array(
    'index'          => 'Visitor\\Categories\\Paging\\Index',
    'details'        => 'Visitor\\Categories\\Paging\\Details',
  );
  
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
