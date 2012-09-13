<?php
namespace Visitor\Channels\Paging;

class Details extends \Visitor\Paging {
  protected $orderkey = 'timestamp_desc';
  protected $sort     = Array(
    'timestamp_desc'       => 'recordedtimestamp DESC',
    'timestamp'            => 'recordedtimestamp',
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
  protected $template = 'Visitor/recordinglistitem.tpl';
  protected $insertbeforepager = Array( 'Visitor/Channels/Paging/DetailsBeforepager.tpl' );
  //protected $insertafterpager  = Array( 'Visitor/Channels/Paging/DetailsAfterpager.tpl' );
  protected $channelids;
  protected $recordingsModel;
  protected $channelModel;
  protected $user;
  
  
  public function init() {
    
    $l                  = $this->bootstrap->getLocalization();
    $this->user         = $this->bootstrap->getSession('user');
    $this->foreachelse  = $l('channels', 'listrecordings_foreachelse');
    $organization       = $this->controller->organization;
    $this->channelModel = $this->controller->modelIDCheck(
      'channels',
      $this->application->getNumericParameter('id')
    );
    
    if ( $this->channelModel->row['organizationid'] != $organization['id'] )
      $this->controller->redirect('index');
    
    $this->channelids = array_merge(
      array( $this->channelModel->id ),
      $this->channelModel->findChildrenIDs()
    );
    
    $this->title                             = $this->channelModel->row['title'];
    $this->controller->toSmarty['listclass'] = 'recordinglist';
    $this->controller->toSmarty['channel']   = $this->channelModel->row;
    parent::init();
    
  }
  
  protected function setupCount() {
    
    $this->recordingsModel = $this->bootstrap->getModel('recordings');
    $this->itemcount =
      $this->recordingsModel->getChannelRecordingsCount(
        $this->user,
        $this->channelids
      );
    
  }
  
  protected function getItems( $start, $limit, $orderby ) {
    
    $items = $this->recordingsModel->getChannelRecordings(
      $this->user,
      $this->channelids,
      $start,
      $limit,
      $orderby
    );
    
    return $items;
    
  }
  
  protected function getUrl() {
    return
      $this->controller->getUrlFromFragment( $this->module . '/' . $this->action ) .
      '/' . $this->channelModel->id . ',' .
      \Springboard\Filesystem::filenameize( $this->channelModel->row['title'] )
    ;
  }
  
}
