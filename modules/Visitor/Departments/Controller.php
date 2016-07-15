<?php
namespace Visitor\Departments;

class Controller extends \Visitor\Controller {
  public $permissions = array(
    'index'      => 'public',
    'admin'      => 'clientadmin',
    'create'     => 'clientadmin',
    'modify'     => 'clientadmin',
    'delete'     => 'clientadmin',
    'users'      => 'clientadmin',
    'deleteuser' => 'clientadmin',
  );

  public $forms = array(
    'create' => 'Visitor\\Departments\\Form\\Create',
    'modify' => 'Visitor\\Departments\\Form\\Modify',
  );

  public $paging = array(
    'admin'          => 'Visitor\\Departments\\Paging\\Admin',
    'users'          => 'Visitor\\Departments\\Paging\\Users',
  );

  public function indexAction() {
    $this->redirectToController('contents', 'http404');
  }

  public function deleteAction() {

    $departmentModel = $this->modelOrganizationAndIDCheck(
      'departments',
      $this->application->getNumericParameter('id')
    );
    $departmentModel->delete( $departmentModel->id );

    $this->redirect(
      $this->application->getParameter('forward', 'departments/admin' )
    );

  }

  public function deleteuserAction() {

    $departmentModel = $this->modelOrganizationAndIDCheck(
      'departments',
      $this->application->getNumericParameter('id')
    );
    $userid = $this->application->getNumericParameter('userid');
    $departmentModel->deleteUser( $userid );
    $this->redirect(
      $this->application->getParameter(
        'forward',
        'departments/users/' . $departmentModel->id . ',' .
        \Springboard\Filesystem::filenameize( $departmentModel->row['name'] )
      )
    );

  }

}
