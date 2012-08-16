<?php
namespace Visitor\Recordings\Paging;

class Featured extends \Visitor\Paging {
  protected $orderkey = 'timestamp_desc';
  protected $sort     = Array(
    'timestamp_desc'       => 'r.timestamp DESC',
    'timestamp'            => 'r.timestamp',
    'views_desc'           => 'numberofviews DESC',
    'views'                => 'numberofviews',
    'viewsthisweek_desc'   => 'numberofviewsthisweek DESC',
    'viewsthisweek'        => 'numberofviewsthisweek',
    'viewsthismonth_desc'  => 'numberofviewsthismonth DESC',
    'viewsthismonth'       => 'numberofviewsthismonth',
    'rating_desc'          => 'rating DESC, numberofratings DESC',
    'rating'               => 'rating, numberofratings DESC',
    'ratingthisweek_desc'  => 'ratingthisweek DESC, numberofratings DESC',
    'ratingthisweek'       => 'ratingthisweek, numberofratings DESC',
    'ratingthismonth_desc' => 'ratingthismonth DESC, numberofratings DESC',
    'ratingthismonth'      => 'ratingthismonth, numberofratings DESC',
  );
  protected $template = 'Visitor/recordinglistitem.tpl';
  protected $insertbeforepager = Array( 'Visitor/Recordings/Paging/FeaturedBeforepager.tpl' );
  protected $recordingsModel;
  protected $filter = '';
  protected $type   = '';
  
  public function init() {
    
    $l                 = $this->bootstrap->getLocalization();
    $this->foreachelse = $l('recordings', 'foreachelse');
    $this->filter      = "r.organizationid = '" . $this->controller->organization['id'] . "'";
    
    $this->type = $this->application->getParameter('subaction', 'featured');
    switch( $this->type ) {
      case 'newest':
        $this->orderkey = 'timestamp_desc';
      break;
      
      case 'highestrated':
        $this->orderkey = 'rating';
      break;
      
      case 'mostviewed':
        $this->orderkey = 'views_desc';
      break;
      
      case 'featured':
      default:
        $this->type    = 'featured';
        $this->filter .= " AND r.isfeatured = '1'";
        break;
      
    }
    
    $this->title = $l('recordings', 'featured_' . $this->type );
    $this->controller->toSmarty['listclass'] = 'recordinglist';
    $this->controller->toSmarty['type']      = $this->type;
    $this->controller->toSmarty['module']    = 'featured';
    parent::init();
    
  }
  
  protected function setupCount() {
    
    $this->recordingsModel = $this->bootstrap->getModel('recordings');
    $this->itemcount =
      $this->recordingsModel->getRecordingsCount( $this->filter )
    ;
    
  }
  
  protected function getItems( $start, $limit, $orderby ) {
    
    $items = $this->recordingsModel->getRecordingsWithUsers( $start, $limit, $this->filter, $orderby );
    return $items;
    
  }
  
  protected function getUrl() {
    return
      $this->controller->getUrlFromFragment( $this->module . '/' . $this->action ) .
      '/' . $this->type
    ;
  }
  
}
