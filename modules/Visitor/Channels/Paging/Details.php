<?php
namespace Visitor\Channels\Paging;

class Details extends \Visitor\Paging {
  protected $orderkey = 'channels';
  protected $sort     = Array(
    'channels'             => 'channelweight, channelid, channelrecordingweight, recordedtimestamp',
    'timestamp_desc'       => 'recordedtimestamp DESC',
    'timestamp'            => 'recordedtimestamp',
    'title_desc'           => 'title DESC',
    'title'                => 'title',
    'views_desc'           => 'numberofviews DESC',
    'views'                => 'numberofviews',
    'rating_desc'          => 'rating DESC, numberofratings DESC',
    'rating'               => 'rating, numberofratings DESC',
  );
  protected $template = 'Visitor/recordinglistitem.tpl';
  protected $insertbeforepager = Array( 'Visitor/Channels/Paging/DetailsBeforepager.tpl' );
  protected $insertafterpager  = Array( 'Visitor/Channels/Paging/DetailsAfterpager.tpl' );
  protected $channelids;
  protected $recordingsModel;
  protected $channelModel;
  protected $user;
  protected $perpageselector = false;
  protected $pagestoshow = 3;

  public function init() {

    $l                  = $this->bootstrap->getLocalization();
    $this->user         = $this->bootstrap->getSession('user');
    $this->foreachelse  = $l('channels', 'listrecordings_foreachelse');
    $organization       = $this->controller->organization;
    $this->channelModel = $this->controller->modelIDCheck(
      'channels',
      $this->application->getNumericParameter('id')
    );

    // ha nem talaltunk akkor hagyjuk azt ami itt be van allitva alapbol
    // ergo default case -> default rendezes, do nothing
    switch( $organization['channelorder'] ) {
      case 'recordtimestamp_desc':
        $this->orderkey = 'timestamp_desc';
        break;
    }

    if (
         $this->channelModel->row['organizationid'] != $organization['id'] or
         $this->channelModel->row['isliveevent'] != '0' or
         $this->channelModel->row['isdeleted']
       )
      $this->controller->redirect('index');
    
    if ( $this->channelModel->isAccessible( $this->user, $this->controller->organization ) !== true )
      $this->controller->redirectToController('contents', 'nopermission');

    $this->channelids = array_merge(
      array( $this->channelModel->id ),
      $this->channelModel->findChildrenIDs()
    );

    $this->channelModel->clearFilter();
    $rootid = $this->channelModel->id;
    if ( $this->channelModel->row['parentid'] )
      $rootid = $this->channelModel->findRootID( $this->channelModel->row['parentid'] );

    $this->channelModel->addFilter('isliveevent', 0 );
    $channeltree = $this->channelModel->getSingleChannelTree( $rootid );

    $this->title                               = $this->channelModel->row['title'];
    $this->controller->toSmarty['listclass']   = 'recordinglist';
    $this->controller->toSmarty['havemultiplechannels'] = count( $this->channelids ) > 1;
    $this->controller->toSmarty['canaddrecording'] = $this->channelModel->isAccessible( $this->user, $organization, true );

    $this->controller->toSmarty['channel']     = $this->channelModel->row;
    $this->controller->toSmarty['channelroot'] = reset( $channeltree );
    $this->controller->toSmarty['channelparent'] = $this->channelModel->getParentFromChannelTree(
      $channeltree,
      $this->channelModel->id
    );
    $this->controller->toSmarty['channelchildren'] = $this->channelModel->getChildrenFromChannelTree(
      $channeltree,
      $this->channelModel->id
    );

    if ( $this->user['id'] ) {
      $userModel = $this->bootstrap->getModel('users');
      $userModel->id = $this->user['id'];
      $this->controller->toSmarty['subscribed'] =
        $userModel->isSubscribedToChannel( $this->channelModel->id )
      ;
    }

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
      '/' . $this->channelModel->id . ',' .
      \Springboard\Filesystem::filenameize( $this->channelModel->row['title'] )
    ;
  }
  
  protected function setupPager() {
    parent::setupPager();
  }
  
}
