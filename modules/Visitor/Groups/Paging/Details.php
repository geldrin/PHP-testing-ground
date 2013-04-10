<?php
namespace Visitor\Groups\Paging;

class Details extends \Visitor\Paging {
  protected $orderkey = 'creation_desc';
  protected $sort = array(
    'creation'      => 'id',
    'creation_desc' => 'id DESC',
  );
  protected $insertbeforepager = Array( 'Visitor/Groups/Paging/DetailsBeforepager.tpl' );
  protected $template = 'Visitor/Groups/Paging/Details.tpl';
  protected $groupModel;
  
  public function init() {
    $l                 = $this->bootstrap->getLocalization();
    $this->foreachelse = $l('groups', 'details_foreachelse');
    $this->title       = $l('groups', 'details_title');
    $this->groupModel  = $this->controller->modelOrganizationAndIDCheck(
      'groups',
      $this->application->getNumericParameter('id')
    );
    
    $this->controller->toSmarty['group'] = $this->groupModel->row;
    $this->controller->toSmarty['listclass'] = 'treeadminlist';
    
  }
  
  protected function setupCount() {
    return $this->itemcount = $this->groupModel->getUserCount();
  }
  
  protected function getItems( $start, $limit, $orderby ) {
    return $this->groupModel->getUserArray( $start, $limit, $orderby );
  }
  
}
