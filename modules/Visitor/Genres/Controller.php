<?php
namespace Visitor\Genres;

class Controller extends \Visitor\Controller {
  public $permissions = array(
    'details' => 'public',
    'create'  => 'admin',
    'modify'  => 'admin',
    'delete'  => 'admin',
    'admin'   => 'admin',
  );
  
  public $forms = array(
    'create' => 'Visitor\\Genres\\Form\\Create',
    'modify' => 'Visitor\\Genres\\Form\\Modify',
  );
  
  public $paging = array(
    'admin'   => 'Visitor\\Genres\\Paging\\Admin',
    'details' => 'Visitor\\Genres\\Paging\\Details',
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
