<?php
namespace Visitor\Live\Paging;

class Teacherinvites extends \Visitor\Paging {
  protected $orderkey = 'creation_desc';
  protected $sort = array(
    'creation'      => 'id',
    'creation_desc' => 'id DESC',
  );
  protected $insertbeforepager = Array( 'Visitor/Live/Paging/TeacherinvitesBeforepager.tpl' );
  protected $template = 'Visitor/Live/Paging/Teacherinvites.tpl';
  protected $maintemplate = 'Visitor/Paging/tablelayout.tpl';
  protected $feedModel;

  public function init() {
    if ( !$this->controller->organization['islivepinenabled'] )
      $this->controller->redirect('');

    $this->feedModel = $this->controller->modelOrganizationAndUserIDCheck(
      'livefeeds',
      $this->application->getNumericParameter('id')
    );

    $l                 = $this->bootstrap->getLocalization();
    $this->foreachelse = $l('live', 'teacher_foreachelse');
    $this->title       = $l('live', 'teacher_invites');
    $this->controller->toSmarty['listclass'] = 'invitelist';
    $this->controller->toSmarty['feed'] = $this->feedModel->row;

    parent::init();

  }

  protected function setupCount() {
    return $this->itemcount = $this->feedModel->getInviteCount();
  }

  protected function getItems( $start, $limit, $orderby ) {
    $items = $this->feedModel->getInviteArray(
      $start, $limit, $orderby
    );
    return $items;
  }

}
