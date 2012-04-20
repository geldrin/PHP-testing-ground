<?php
namespace Visitor\Categories\Paging;

class Details extends \Visitor\Paging {
  protected $orderkey = 'timestamp_desc';
  protected $sort = Array(
    'timestamp_desc'       => 'r.timestamp DESC',
    'timestamp'            => 'r.timestamp',
    'title_desc'           => 'r.titleoriginal DESC',
    'title'                => 'r.titleoriginal',
    'views_desc'           => 'r.numberofviews DESC',
    'views'                => 'r.numberofviews',
    'viewsthisweek_desc'   => 'r.numberofviewsthisweek DESC',
    'viewsthisweek'        => 'r.numberofviewsthisweek',
    'viewsthismonth_desc'  => 'r.numberofviewsthismonth DESC',
    'viewsthismonth'       => 'r.numberofviewsthismonth',
    'comments_desc'        => 'r.numberofcomments DESC',
    'comments'             => 'r.numberofcomments',
    'rating_desc'          => 'r.rating DESC, r.numberofratings DESC',
    'rating'               => 'r.rating, r.numberofratings DESC',
    'ratingthisweek_desc'  => 'r.ratingthisweek DESC, r.numberofratings DESC',
    'ratingthisweek'       => 'r.ratingthisweek, r.numberofratings DESC',
    'ratingthismonth_desc' => 'r.ratingthismonth DESC, r.numberofratings DESC',
    'ratingthismonth'      => 'r.ratingthismonth, r.numberofratings DESC',
  );
  protected $insertbeforepager = Array( 'Visitor/Categories/Paging/DetailsBeforepager.tpl' );
  protected $template = 'Visitor/Categories/Paging/Details.tpl';
  protected $categoryids;
  protected $recordingsModel;
  
  public function init() {
    
    $l                 = $this->bootstrap->getLocalization();
    $this->foreachelse = $l('categories', 'categories_foreachelse');
    $this->title       = $l('categories', 'categories_title');
    $organization      = $this->controller->organization;
    $categoryModel     = $this->controller->modelIDCheck(
      'categories',
      $this->application->getNumericParameter('id')
    );
    
    if ( $categoryModel->row['organizationid'] != $organization['id'] )
      $this->controller->redirect('index');
    
    $this->categoryids = array_merge(
      array( $categoryModel->id ),
      $categoryModel->findChildrenIDs()
    );
    
    $this->controller->toSmarty['category']  = $categoryModel->row;
    $this->controller->toSmarty['listclass'] = 'recordinglist';
    parent::init();
    
  }
  
  protected function setupCount() {
    
    $this->recordingsModel = $this->bootstrap->getModel('recordings');
    $this->itemcount =
      $this->recordingsModel->getCategoryRecordingsCount(
        $this->categoryids
      );
    
  }
  
  protected function getItems( $start, $limit, $orderby ) {
    
    $items = $this->recordingsModel->getCategoryRecordings(
      $this->categoryids,
      $start,
      $limit,
      $orderby
    );
    
    return $items;
    
  }
  
  protected function getUrl() {
    return
      $this->controller->getUrlFromFragment( $this->module . '/' . $this->action ) .
      '/' . $this->application->getNumericParameter('id')
    ;
  }
  
}
