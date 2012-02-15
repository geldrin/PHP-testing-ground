<?php
namespace Visitor\Categories;

class Controller extends \Visitor\Controller {
  public $permissions = array(
    'index'               => 'admin',
    'details'             => 'admin',
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
    
    $categoryModel = $this->modelOrganizationAndIDCheck('categories');
    $categoryModel->delete( $categoryModel->id );
    
    $this->redirect('categories/index', array(
        'forward' => $this->application->getParameter('forward'),
      )
    );
    
  }
  
}
