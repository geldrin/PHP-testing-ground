<?php
namespace Visitor\Categories\Paging;

class Details extends \Visitor\Paging {
  protected $orderkey = 'timestamp_desc';
  protected $sort     = Array(
    'timestamp_desc'       => 'timestamp DESC',
    'timestamp'            => 'timestamp',
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
  protected $insertbeforepager = Array( 'Visitor/Categories/Paging/DetailsBeforepager.tpl' );
  protected $template = 'Visitor/recordinglistitem.tpl';
  protected $category;
  protected $categoryids;
  protected $recordingsModel;
  protected $user;
  
  public function init() {
    
    $l                   = $this->bootstrap->getLocalization();
    $this->user          = $this->bootstrap->getSession('user');
    $this->foreachelse   = $l('categories', 'categories_foreachelse');
    $this->title         = $l('categories', 'categories_title');
    $organization        = $this->controller->organization;

    $categoryModel = $this->bootstrap->getModel('categories');
    $categories    = $categoryModel->cachedGetCategoryTree(
      $organization['id']
    );

    $this->category = $categoryModel->searchCategoryTree(
      $organization['id'],
      $this->application->getNumericParameter('id')
    );

    // not possible
    if ( $this->category['organizationid'] != $organization['id'] )
      $this->controller->redirect('index');

    $this->categoryids = $categoryModel->getChildrenIDsFromCategoryTree(
      $this->category
    );

    $breadcrumb = $categoryModel->getCategoryTreeBreadcrumb(
      $organization['id'],
      $this->category['id']
    );

    $this->controller->toSmarty['breadcrumb'] = $breadcrumb;
    $this->controller->toSmarty['category']   = $this->category;
    $this->controller->toSmarty['categories'] = $this->category['children'];
    $this->controller->toSmarty['listclass']  = 'recordinglist';
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
      \Springboard\Filesystem::filenameize( $this->category['name'] )
    ;
  }
  
}
