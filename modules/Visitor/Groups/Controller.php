<?php
namespace Visitor\Groups;

class Controller extends \Visitor\Controller {
  public $permissions = array(
    'index'   => 'member',
    'details' => 'member',
    'create'  => 'member',
    'modify'  => 'member',
    'delete'  => 'member',
    'invite'  => 'member',
    'deleteuser'  => 'member',
  );
  
  public $forms = array(
    'create' => 'Visitor\\Groups\\Form\\Create',
    'modify' => 'Visitor\\Groups\\Form\\Modify',
    'invite' => 'Visitor\\Groups\\Form\\Invite',
  );
  
  public $paging = array(
    'index'   => 'Visitor\\Groups\\Paging\\Index',
    'details' => 'Visitor\\Groups\\Paging\\Details',
  );
  
  public function deleteAction() {
    
    $groupModel = $this->modelOrganizationAndIDCheck(
      'groups',
      $this->application->getNumericParameter('id')
    );
    $groupModel->deleteAndClearMembers();
    
    $this->redirect(
      $this->application->getParameter('forward', 'groups/index' )
    );
    
  }
  
  public function deleteuserAction() {
    
    $groupModel = $this->modelOrganizationAndIDCheck(
      'groups',
      $this->application->getNumericParameter('id')
    );
    $userid = $this->application->getNumericParameter('userid');
    $groupModel->deleteUser( $userid );
    $this->redirect(
      $this->application->getParameter(
        'forward',
        'groups/details/' . $groupModel->id . ',' .
        \Springboard\Filesystem::filenameize( $groupModel->row['name'] )
      )
    );
    
  }
  
}
