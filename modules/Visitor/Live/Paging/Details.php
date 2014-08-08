<?php
namespace Visitor\Live\Paging;
class Details extends \Visitor\Paging {
  protected $orderkey = 'createtime_desc';
  protected $sort = array(
    
    'createtime_desc' => 'id DESC',
    'createtime'      => 'id',
  );
  protected $insertbeforepager = Array( 'Visitor/Live/Paging/DetailsBeforepager.tpl' );
  protected $template = 'Visitor/Live/Paging/Details.tpl';
  protected $insertafterpager = Array( 'Visitor/Live/Paging/DetailsAfterpager.tpl' );
  protected $channelModel;
  protected $perpageselector = false;
  
  public function init() {
    
    include_once(
      $this->bootstrap->config['templatepath'] .
      'Plugins/modifier.indexphoto.php'
    );
    
    $l                  = $this->bootstrap->getLocalization();
    $user               = $this->bootstrap->getSession('user');
    $this->foreachelse  = '';
    $this->channelModel = $this->controller->modelIDCheck(
      'channels',
      $this->application->getNumericParameter('id')
    );
    $this->title        = sprintf(
      $l('live','details_title'),
      $this->channelModel->row['title']
    );
    $this->controller->toSmarty['opengraph']     = array(
      'image'       => smarty_modifier_indexphoto( $this->channelModel->row, 'live' ),
      'description' => $this->channelModel->row['description'],
      'title'       => $this->channelModel->row['title'],
      'subtitle'    => $this->channelModel->row['subtitle'],
    );

    if ( !$this->channelModel->row['isliveevent'] )
      $this->controller->redirect(
        'channels/details/' . $this->channelModel->id . ',' . \Springboard\Filesystem::filenameize( $this->channelModel->row['title'] )
      );
    
    $isadmin = $user['id'] and ( $user['isadmin'] or $user['isliveadmin'] or $user['isclientadmin'] );
    if ( !$isadmin and $this->channelModel->row['endtimestamp'] ) {
      
      $endtime = strtotime( $this->channelModel->row['endtimestamp'] );
      if ( strtotime('+3 days', $endtime ) < time() )
        $this->controller->redirect(
          'channels/details/' . $this->channelModel->id . ',' . \Springboard\Filesystem::filenameize( $this->channelModel->row['title'] )
        );
      
    }
    
    $this->channelModel->clearFilter();
    $rootid = $this->channelModel->id;
    if ( $this->channelModel->row['parentid'] )
      $rootid = $this->channelModel->findRootID( $this->channelModel->row['parentid'] );;

    $this->channelModel->addFilter('isliveevent', 1 );
    $channeltree = $this->channelModel->getSingleChannelTree( $rootid, null, 0, true );
    
    $this->controller->toSmarty['channeltree'] = $channeltree;
    $this->controller->toSmarty['listclass']   = 'recordinglist';
    $this->controller->toSmarty['feeds']       = $this->channelModel->getFeeds();
    $this->controller->toSmarty['channel']     = $this->channelModel->row;
    
    $this->controller->toSmarty['streamingactive'] =
      ( strtotime( $this->channelModel->row['starttimestamp'] ) <= time() ) and
      (
        !strlen( $this->channelModel->row['endtimestamp'] )
        or
        ( strtotime( $this->channelModel->row['endtimestamp'] ) >= time() )
      )
    ;
    
    parent::init();
    
  }
  
  protected function setupCount() {
    
    return $this->itemcount = 0;
    
  }
  
  protected function getItems( $start, $limit, $orderby ) {
    
    return array();
    
  }
  
}
