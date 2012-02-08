<?php
namespace Visitor\Recordings;

class Controller extends \Springboard\Controller\Visitor {
  public $permissions = array(
    'index'  => 'public',
    'upload' => 'member',
    'myrecordings' => 'member',
    'modifybasics'         => 'member',
    'modifyclassification' => 'member',
  );
  
  public $forms = array(
    'upload' => 'Visitor\\Recordings\\Form\\Upload',
    'modifybasics' => 'Visitor\\Recordings\\Form\\Modifybasics',
    'modifyclassification' => 'Visitor\\Recordings\\Form\\Modifyclassification',
  );
  
  public $paging = array(
    'myrecordings' => 'Visitor\\Recordings\\Paging\\Myrecordings',
  );
  
  // TODO override acl handling, swfuploadnal megfelelo uzenetet kuldeni
  /*
  
    if ( $this->swfupload and !$user->id )
      $this->controller->swfuploadMessage( array(
          'error' => 'membersonly',
          'url'   => sprintf( tools::$membersonly_login_url, rawurlencode( @$_SERVER['REQUEST_URI'] ) ),
        )
      );
    
  */
  public function indexAction() {
    $this->redirect('recordings/myrecordings');
  }
  
}
