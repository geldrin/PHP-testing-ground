<?php
namespace Visitor\Genres;

class Controller extends \Visitor\Controller {
  public $permissions = array(
    'index'               => 'public',
    'details'             => 'public',
    'create'              => 'admin',
    'modify'              => 'admin',
    'delete'              => 'admin',
  );
  
  public $forms = array(
    'create' => 'Visitor\\Genres\\Form\\Create',
    'modify' => 'Visitor\\Genres\\Form\\Modify',
  );
  
  public $paging = array(
    'index'          => 'Visitor\\Genres\\Paging\\Index',
    'details'        => 'Visitor\\Genres\\Paging\\Details',
  );
  
  public function deleteAction() {
    
    $genreModel = $this->modelOrganizationAndIDCheck(
      'genres',
      $this->application->getNumericParameter('id')
    );
    $genreModel->delete( $genreModel->id );
    
    $this->redirect(
      $this->application->getParameter('forward', 'genres/index' )
    );
    
  }
  
}
