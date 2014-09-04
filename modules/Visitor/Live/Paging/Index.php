<?php
namespace Visitor\Live\Paging;
class Index extends \Visitor\Paging {
  protected $orderkey = 'starttime_desc';
  protected $sort = array(
    'starttime_desc'  => 'starttimestamp DESC',
    'starttime'       => 'starttimestamp',
    'createtime_desc' => 'id DESC',
    'createtime'      => 'id',
  );
  protected $insertbeforepager = Array( 'Visitor/Live/Paging/IndexBeforepager.tpl' );
  protected $template = 'Visitor/Live/Paging/Index.tpl';
  protected $channelModel;
  protected $filters = array(
    'showall' => false,
  );

  public function init() {

    $l                 = $this->bootstrap->getLocalization();
    $this->foreachelse = $l('live','live_foreachelse');
    $this->title       = $l('','sitewide_live');
    $this->controller->toSmarty['listclass'] = 'recordinglist';

    $this->handleSearch();
    parent::init();

  }
  
  protected function setupCount() {

    $this->channelModel = $this->bootstrap->getModel('channels');
    return $this->itemcount = $this->channelModel->getLiveCount(
      $this->filters
    );

  }

  protected function getItems( $start, $limit, $orderby ) {

    $items = $this->channelModel->getLiveArray(
      $this->filters,
      $start, $limit, $orderby
    );

    if ( empty( $this->passparams ) and empty( $items ) )
      $this->controller->toSmarty['nosearch'] = true;

    return $items;

  }

  protected function handleSearch() {

    $this->filters['organizationid'] = $this->controller->organization['id'];
    $user = $this->bootstrap->getSession('user');
    if (
         $user['id'] and
         ( $user['isadmin'] or $user['isclientadmin'] or $user['isliveadmin'] )
       ) {
      $this->controller->toSmarty['showsearch'] = true;
    } else
      return;

    $showall = $this->application->getParameter('showall');
    if ( $showall and in_array( $showall, array('0', '1') ) ) {
      $this->filters['showall'] = (bool)$showall;
      $this->passparams['showall'] = $showall;
    }

    $term = $this->application->getParameter('term');
    if ( $term and mb_strlen( trim( $term ) ) >= 2 ) {
      $this->passparams['term'] = trim( $term );
      $this->filters['term'] = $this->passparams['term'];
    }

  }

}
