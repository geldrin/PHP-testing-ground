<?php
namespace Visitor\Recordings\Paging;
class Myrecordings extends \Visitor\Paging {
  protected $orderkey = 'timestamp_desc';
  protected $sort = array(
    'timestamp_desc'         => 'timestamp DESC',
    'timestamp'              => 'timestamp',
    'recordedtimestamp_desc' => 'recordedtimestamp DESC',
    'recordedtimestamp'      => 'recordedtimestamp',
  );
  protected $insertbeforepager = Array( 'Visitor/Recordings/Paging/MyrecordingsBeforepager.tpl' );
  protected $template = 'Visitor/Recordings/Paging/Myrecordings.tpl';
  protected $recordingsModel;
  
  public function init() {
    
    $l                 = $this->bootstrap->getLocalization();
    $this->foreachelse = $l('', 'foreachelse');
    $this->title       = $l('recordings', 'myrecordings_title');
    $this->controller->toSmarty['listclass'] = 'recordinglist';
    parent::init();
    
  }
  
  protected function setupCount() {
    
    $this->recordingsModel = $this->bootstrap->getModel('recordings');
    $user  = $this->bootstrap->getSession('user');
    
    if ( $user['iseditor'] )
      $this->recordingsModel->addTextFilter("
        organizationid = '" . $user['organizationid'] . "' OR
        (
          userid = '" . $user['id'] . "' AND
          organizationid = '" . $user['organizationid'] . "'
        )
      ");
    else
      $this->recordingsModel->addFilter('userid', $user['id'] );
    
    $this->recordingsModel->addTextFilter("status <> 'markedfordeletion'");
    
    $search = $this->handleSearch();
    
    if ( $search['where'] )
      $this->recordingsModel->addTextFilter( $search['where'] );
    
    if ( $search['order'] )
      $this->order = $search['order'] . ', ' . $this->order;
    
    return $this->itemcount = $this->recordingsModel->getCount();
    
  }
  
  protected function getItems( $start, $limit, $orderby ) {
    
    $items = $this->recordingsModel->getArray( $start, $limit, false, $orderby );
    
    if ( empty( $this->passparams ) and empty( $items ) )
      $this->controller->toSmarty['nosearch'] = true;
    
    foreach( $items as $key => $item ) {
      
      if ( $item['isintrooutro'] )
        continue;
      
      $this->recordingsModel->id  = $item['id'];
      $this->recordingsModel->row = $item;
      $items[ $key ]['canuploadcontentvideo'] =
        $this->recordingsModel->canUploadContentVideo()
      ;
      $items[ $key ]['subtitlefiles'] = $this->recordingsModel->getSubtitleLanguages();
      
    }
    
    return $items;
    
  }
  
  protected function handleSearch() {
    
    $where  = array();
    $status = $this->application->getParameter('status');
    $order  = '';
    
    if ( $status and in_array( $status, array('converting', 'converted', 'failed') ) ) {
      
      if ( $status == 'converting' )
        $statuses = array(
          'uploaded',
          'copyingtoconverter',
          'copyingfromfrontend',
          'reconvert',
          'copiedfromfrontend',
          'converting',
          'converting1thumbnails',
          'converting2audio',
          'converting3video',
          'copyingtostorage',
        );
      elseif ( $status == 'converted' )
        $statuses = array('onstorage');
      elseif ( $status == 'failed' )
        $statuses = array(
          'failed',
          'failedcopyingfromfrontend',
          'failedcopyingtostorage',
          'failedconverting',
          'failedconverting2audio',
          'failedconverting3video',
          'invalidinput',
          'failedcopyingtostorage',
        );
      
      $where[] = "status IN('" . implode("', '", $statuses ) . "')";
      $this->passparams['status'] = $status;
      
    }
    
    $publishstatus = $this->application->getParameter('publishstatus');
    
    if ( $publishstatus and in_array( $publishstatus, array('published', 'nonpublished') ) ) {
      
      $ispublished = $publishstatus == 'published'? '1': '0';
      $where[] = "ispublished = '" . $ispublished . "'";
      $this->passparams['publishstatus'] = $publishstatus;
      
    }
    
    $publicstatus = $this->application->getParameter('publicstatus');
    
    if ( $publicstatus and in_array( $publicstatus, array('public', 'private') ) ) {
      
      if ( $publicstatus == 'public' )
        $where[] = "accesstype = 'public'";
      else
        $where[] = "accesstype <> 'public'";
      
      $this->passparams['publicstatus'] = $publicstatus;
      
    }
    
    $myrecordingsq = $this->application->getParameter('myrecordingsq');
    
    if ( $myrecordingsq and strlen( trim( $myrecordingsq ) ) >= 2 ) {
      
      $this->passparams['myrecordingsq'] = trim( $myrecordingsq );
      $db              = $this->bootstrap->getAdoDB();
      $searchterm      = str_replace( ' ', '%', $this->passparams['myrecordingsq'] );
      $searchterm      = $db->qstr( '%' . $searchterm . '%' );
      $order           = "
        (
           title    LIKE $searchterm OR
           subtitle LIKE $searchterm
        ) DESC
      ";
      
      $where[] = "primarymetadatacache LIKE $searchterm";
      
    }
    
    $introoutro = $this->application->getParameter('isintrooutro');
    
    if ( $introoutro and in_array( $introoutro, array('yes', 'no') ) ) {
      
      if ( $introoutro == 'yes' )
        $where[] = "isintrooutro = '1'";
      else
        $where[] = "isintrooutro = '0'";
      
      $this->passparams['isintrooutro'] = $introoutro;
      
    }
    
    if ( empty( $where ) )
      $where = '';
    else
      $where = ' ( ' . implode(' ) AND ( ', $where ) . ' ) ';
    
    return array(
      'order' => $order,
      'where' => $where,
    );
    
  }
  
}
