<?php
namespace Visitor\Recordings;

class Controller extends \Springboard\Controller\Visitor {
  public $permissions = array(
    'index'  => 'public',
    'upload' => 'member',
    'modifybasics' => 'member',
  );
  
  public $forms = array(
    'upload' => 'Visitor\\Recordings\\Form\\Upload',
    'modifybasics' => 'Visitor\\Recordings\\Form\\Modifybasics',
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
    
    $smarty = $this->bootstrap->getSmarty();
    $this->output( $smarty->fetch('Visitor/Index/index.tpl') );
    
  }
  
}
