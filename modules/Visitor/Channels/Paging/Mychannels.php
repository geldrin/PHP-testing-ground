<?php
namespace Visitor\Channels\Paging;

class Mychannels extends \Visitor\Paging {
  protected $orderkey = 'creation_desc';
  protected $sort = array(
    'creation'      => 'id',
    'creation_desc' => 'id DESC',
  );
  protected $insertbeforepager = Array( 'Visitor/Channels/Paging/MychannelsBeforepager.tpl' );
  protected $template = 'Visitor/Channels/Paging/Mychannels.tpl';

  public function init() {

    $l                 = $this->bootstrap->getLocalization();
    $this->foreachelse = $l('channels', 'foreachelse');
    $this->title       = $l('channels', 'mychannels_title');
    $this->controller->toSmarty['listclass'] = 'treeadminlist';
    parent::init();

  }

  protected function setupCount() {
    return 1;
  }

  protected function getItems( $start, $limit, $orderby ) {
    $channelModel = $this->bootstrap->getModel('channels');
    $user         = $this->bootstrap->getSession('user');
    $channelModel->addFilter('organizationid', $this->controller->organization['id'] );
    $channelModel->addFilter('parentid', '0', true, true, 'treearray');
    $channelModel->addFilter('isliveevent', 0 );
    if (
         !\Model\Userroles::userHasPrivilege(
           'channels_listallchannels',
           'or',
           'isadmin', 'iseditor', 'isclientadmin'
         )
       )
      $channelModel->addFilter('userid', $user['id'] );

    return $channelModel->getTreeArray();
  }

}
