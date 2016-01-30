<?php
namespace Visitor\Index;

class Controller extends \Visitor\Controller {
  public $permissions = array(
    'index'  => 'public',
  );

  private $maxRecordings = 4;
  private $recordingTypes = array(
    'mostviewed' => array(
      'order'  => 'numberofviews DESC',
    ),
    'newest' => array(
      'order'  => 'timestamp DESC',
    ),
  );

  public function indexAction() {
    $this->recordingsModel = $this->bootstrap->getModel('recordings');
    $user = $this->bootstrap->getSession('user');

    $this->toSmarty['defaultimage'] =
      $this->bootstrap->staticuri . 'images/header_logo.png'
    ;
    $this->toSmarty['welcome'] = true;

    $blocks = array();
    foreach( $this->organization['blockorder'] as $block => $value ) {
      $method = 'getBlock' . ucfirst( $block );
      $blocks[ $block ] = $this->$method( $user );
    }

    $this->toSmarty['blocks'] = $blocks;
    $this->smartyoutput('Visitor/Index/index.tpl');
  }

  private function getRecordings( $type, $user ) {
    $filter = "r.organizationid = '" . $this->organization['id'] . "'";
    if ( isset( $this->recordingTypes[ $type ]['filter'] ) )
      $filter .= $this->recordingTypes[ $type ]['filter'];

    $ret = $this->recordingsModel->getRecordingsWithUsers(
      0,
      $this->maxRecordings,
      $filter,
      $this->recordingTypes[ $type ]['order'],
      $user,
      $this->organization['id']
    );

    $this->recordingsModel->addPresentersToArray(
      $ret,
      true,
      $this->organization['id']
    );

    return $ret;
  }

  private function getBlockKiemelt( $user ) {
    $ret = $this->recordingsModel->getRandomRecordings(
      $this->maxRecordings,
      $this->organization['id'],
      $user
    );

    $this->recordingsModel->addPresentersToArray(
      $ret,
      true,
      $this->organization['id']
    );

    return $ret;
  }

  private function getBlockLegujabb( $user ) {
    return $this->getRecordings( 'newest', $user );
  }

  private function getBlockLegnezettebb( $user ) {
    return $this->getRecordings( 'mostviewed', $user );
  }

  private function getBlockEloadas( $user ) {
    $livefeedModel = $this->bootstrap->getModel('livefeeds');
    return $livefeedModel->getFeatured(
      $this->organization['id'],
      \Springboard\Language::get()
    );
  }
}
