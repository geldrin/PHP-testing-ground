<?php
namespace Visitor\Categories\Paging;

class Admin extends \Visitor\Paging {
  protected $orderkey = 'creation_desc';
  protected $sort = array(
    'creation'      => 'id',
    'creation_desc' => 'id DESC',
  );
  protected $insertbeforepager = Array( 'Visitor/Live/Paging/TeachersBeforepager.tpl' );
  protected $template = 'Visitor/Live/Paging/Teachers.tpl';
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
    $this->title       = $l('live', 'teacher_title');
    $this->controller->toSmarty['listclass'] = 'treeadminlist';

    parent::init();

  }

  protected function setupCount() {
    return $this->itemcount = $this->feedModel->getTeacherCount();
  }

  protected function getItems( $start, $limit, $orderby ) {
    $items = $this->feedModel->getTeacherArray(
      $start, $limit, $orderby
    );
    return $items;
  }

}
