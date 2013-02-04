<?php
namespace Visitor\Categories\Paging;

class Details extends \Visitor\Paging {
  protected $orderkey = 'timestamp_desc';
  protected $sort     = Array(
    'timestamp_desc'       => 'timestamp DESC',
    'timestamp'            => 'timestamp',
    'title_desc'           => 'titleoriginal DESC',
    'title'                => 'titleoriginal',
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
  protected $insertbeforepager = Array( 'Visitor/Categories/Paging/DetailsBeforepager.tpl' );
  protected $template = 'Visitor/recordinglistitem.tpl';
  protected $categoryids;
  protected $recordingsModel;
  protected $categoryModel;
  protected $user;
  
  public function init() {
    
    $l                   = $this->bootstrap->getLocalization();
    $this->user          = $this->bootstrap->getSession('user');
    $this->foreachelse   = $l('categories', 'categories_foreachelse');
    $this->title         = $l('categories', 'categories_title');
    $organization        = $this->controller->organization;
    $this->categoryModel = $this->controller->modelIDCheck(
      'categories',
      $this->application->getNumericParameter('id')
    );
    
    if ( $this->categoryModel->row['organizationid'] != $organization['id'] )
      $this->controller->redirect('index');
    
    $this->categoryids = array_merge(
      array( $this->categoryModel->id ),
      $this->categoryModel->findChildrenIDs()
    );
    
    $this->controller->toSmarty['category']  = $this->categoryModel->row;
    $this->controller->toSmarty['listclass'] = 'recordinglist';
    parent::init();
    
  }
  
  protected function setupCount() {
    
    $this->recordingsModel = $this->bootstrap->getModel('recordings');
    $this->itemcount =
      $this->recordingsModel->getCategoryRecordingsCount(
        $this->user,
        $this->categoryids
      );
    
  }
  
  protected function getItems( $start, $limit, $orderby ) {
    
    $items = $this->recordingsModel->getCategoryRecordings(
      $this->user,
      $this->categoryids,
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
      '/' . $this->application->getNumericParameter('id') . ',' .
      \Springboard\Filesystem::filenameize( $this->categoryModel->row['name'] )
    ;
  }
  
}
