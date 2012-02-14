<?php
namespace Visitor\Channels;

class Controller extends \Visitor\Controller {
  public $permissions = array(
    'index'               => 'public',
    'details'             => 'public',
    'mychannels'          => 'member',
    'create'              => 'member',
    'modify'              => 'member',
    'delete'              => 'member',
    'mychannels'          => 'member',
    'addrecording'        => 'member',
    'deleterecording'     => 'member',
    'listfavorites'       => 'member',
    'addtofavorites'      => 'member',
    'deletefromfavorites' => 'member',
  );
  
  public $forms = array(
    'create' => 'Visitor\\Channels\\Form\\Create',
    'modify' => 'Visitor\\Channels\\Form\\Modify',
  );
  
  public $paging = array(
    'index'          => 'Visitor\\Channels\\Paging\\Index',
    'details'        => 'Visitor\\Channels\\Paging\\Details',
    'mychannels'     => 'Visitor\\Channels\\Paging\\Mychannels',
    'listfavorites'  => 'Visitor\\Channels\\Paging\\Listfavorites',
  );
  
  public function deleteAction() {
    // TODO
  }
}
