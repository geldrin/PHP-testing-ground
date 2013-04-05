<?php
namespace Visitor\Genres;

class Controller extends \Visitor\Controller {
  public $permissions = array(
    'create'  => 'clientadmin',
    'modify'  => 'clientadmin',
    'delete'  => 'clientadmin',
    'admin'   => 'clientadmin',
  );
  
  public $forms = array(
    'create' => 'Visitor\\Genres\\Form\\Create',
    'modify' => 'Visitor\\Genres\\Form\\Modify',
  );
  
  public $paging = array(
    'admin'   => 'Visitor\\Genres\\Paging\\Admin',
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
