<?php
namespace Visitor\Groups;

class Controller extends \Visitor\Controller {
  public $permissions = array(
    'index'      => 'member',
    'users'      => 'member',
    'create'     => 'member',
    'modify'     => 'member',
    'delete'     => 'member',
    'deleteuser' => 'member',
    'recordings' => 'member',
    'searchuser' => 'member',
  );
  
  public $forms = array(
    'create' => 'Visitor\\Groups\\Form\\Create',
    'modify' => 'Visitor\\Groups\\Form\\Modify',
  );
  
  public $paging = array(
    'index'      => 'Visitor\\Groups\\Paging\\Index',
    'users'      => 'Visitor\\Groups\\Paging\\Users',
    'recordings' => 'Visitor\\Groups\\Paging\\Recordings',
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
        'groups/users/' . $groupModel->id . ',' .
        \Springboard\Filesystem::filenameize( $groupModel->row['name'] )
      )
    );
    
  }
  
  public function searchuserAction() {
    
    $term   = $this->application->getParameter('term');
    $output = array(
    );
    
    if ( !$term )
      $this->jsonoutput( $output );
    
    $userModel = $this->bootstrap->getModel('users');
    $results   = $userModel->search(
      $term,
      $this->organization['id']
    );
    
    if ( empty( $results ) )
      $this->jsonoutput( $output );
    
    include_once( $this->bootstrap->config['templatepath'] . 'Plugins/modifier.nameformat.php' );
    foreach( $results as $result ) {
      
      $data = array(
        'value' => $result['id'],
        'label' => smarty_modifier_nameformat( $result ),
        'img'   => $this->bootstrap->staticuri,
      );
      
      if ( $result['avatarstatus'] == 'onstorage' )
        $data['img'] .= sprintf(
          'files/users/%s/avatar/%s.jpg',
          \Springboard\Filesystem::getTreeDir( $result['id'] ),
          $result['id']
        );
      else
        $data['img'] .= 'images/avatar_placeholder.png';
      
      $output[] = $data;
      
    }
    
    $this->jsonoutput( $output );
    
  }
  
}
