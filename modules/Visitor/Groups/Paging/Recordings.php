<?php
namespace Visitor\Groups\Paging;

class Recordings extends \Visitor\Paging {
  protected $orderkey = 'timestamp_desc';
  protected $sort     = Array(
    'timestamp_desc'       => 'recordedtimestamp DESC',
    'timestamp'            => 'recordedtimestamp',
    'title_desc'           => 'title DESC',
    'title'                => 'title',
    'views_desc'           => 'numberofviews DESC',
    'views'                => 'numberofviews',
    'viewsthisweek_desc'   => 'numberofviewsthisweek DESC',
    'viewsthisweek'        => 'numberofviewsthisweek',
    'viewsthismonth_desc'  => 'numberofviewsthismonth DESC',
    'viewsthismonth'       => 'numberofviewsthismonth',
    'comments_desc'        => 'numberofcomments DESC',
    'comments'             => 'numberofcomments',
    'rating_desc'          => 'rating DESC, numberofratings DESC',
    'rating'               => 'rating, numberofratings DESC',
    'ratingthisweek_desc'  => 'ratingthisweek DESC, numberofratings DESC',
    'ratingthisweek'       => 'ratingthisweek, numberofratings DESC',
    'ratingthismonth_desc' => 'ratingthismonth DESC, numberofratings DESC',
    'ratingthismonth'      => 'ratingthismonth, numberofratings DESC',
  );
  protected $template = 'Visitor/recordinglistitem.tpl';
  protected $insertbeforepager = Array( 'Visitor/Groups/Paging/RecordingsBeforepager.tpl' );
  //protected $insertafterpager  = Array( 'Visitor/Groups/Paging/RecordingsAfterpager.tpl' );
  protected $recordingsModel;
  protected $groupModel;
  protected $user;
  protected $perpageselector = false;
  protected $pagestoshow = 3;

  public function init() {

    $l                  = $this->bootstrap->getLocalization();
    $this->user         = $this->bootstrap->getSession('user');
    $this->foreachelse  = $l('groups', 'listrecordings_foreachelse');
    $this->groupModel   = $this->controller->modelOrganizationAndIDCheck(
      'groups',
      $this->application->getNumericParameter('id')
    );

    if ( $this->groupModel->isMember( $this->user ) !== true )
      $this->controller->redirectToController('contents', 'nopermission');

    $this->title                             = $this->groupModel->row['name'];
    $this->controller->toSmarty['listclass'] = 'recordinglist';
    $this->controller->toSmarty['group']     = $this->groupModel->row;
    parent::init();

  }

  protected function setupCount() {

    $this->recordingsModel = $this->bootstrap->getModel('recordings');
    $this->itemcount =
      $this->recordingsModel->getGroupRecordingsCount(
        $this->groupModel->id
      );

  }

  protected function getItems( $start, $limit, $orderby ) {

    $items = $this->recordingsModel->getGroupRecordings(
      $this->groupModel->id,
      $start,
      $limit,
      $orderby
    );

    $items = $this->recordingsModel->addPresentersToArray(
      $items,
      true,
      $this->controller->organization['id']
    );

    return $items;

  }

  protected function getUrl() {
    return
      $this->controller->getUrlFromFragment( $this->module . '/' . $this->action ) .
      '/' . $this->groupModel->id . ',' .
      \Springboard\Filesystem::filenameize( $this->groupModel->row['name'] )
    ;
  }

  protected function setupPager() {
    parent::setupPager();
  }

}
