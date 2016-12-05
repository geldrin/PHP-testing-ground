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

    $id                 = $this->application->getNumericParameter('id');
    $l                  = $this->bootstrap->getLocalization();
    $user               = $this->bootstrap->getSession('user');
    $this->foreachelse  = '';
    $this->channelModel = $this->bootstrap->getModel('channels');
    $this->channelModel->selectEventWithType(
      $id,
      $user,
      $this->controller->organization['id']
    );

    if ( !$this->channelModel->row and $id ) {
      $this->channelModel->select( $id );
      $url = '';
      if ( $this->channelModel->row )
        $url =
          'channels/details/' . $id . ',' .
          \Springboard\Filesystem::filenameize( $this->channelModel->row['title'] )
        ;

      $this->controller->redirect( $url );
    }

    $this->title = sprintf(
      $l('live','details_title'),
      $this->channelModel->row['title']
    );

    $this->bootstrap->includeTemplatePlugin('indexphoto');
    $this->controller->toSmarty['opengraph']     = array(
      'image'       => smarty_modifier_indexphoto( $this->channelModel->row, 'live' ),
      'description' => $this->channelModel->row['description'],
      'title'       => $this->channelModel->row['title'],
      'subtitle'    => $this->channelModel->row['subtitle'],
    );

    $this->controller->toSmarty['listclass']   = 'recordinglist livelist';
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

    return $this->itemcount = null;

  }

  protected function getItems( $start, $limit, $orderby ) {

    return $this->controller->toSmarty['feeds'];

  }

}
