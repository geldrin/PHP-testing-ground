<?php
namespace Visitor\Categories;

class Controller extends \Visitor\Controller {
  public $permissions = array(
    'index'               => 'public',
    'details'             => 'public',
    'create'              => 'member',
    'modify'              => 'member',
    'delete'              => 'member',
  );
  
  public $forms = array(
    'create' => 'Visitor\\Channels\\Form\\Create',
    'modify' => 'Visitor\\Channels\\Form\\Modify',
  );
  
  public $paging = array(
    'index'          => 'Visitor\\Channels\\Paging\\Index',
    'details'        => 'Visitor\\Channels\\Paging\\Details',
  );
  
  public function deleteAction() {
    // TODO
  }
  
}
