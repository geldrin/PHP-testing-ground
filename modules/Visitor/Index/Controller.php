<?php
namespace Visitor\Index;

class Controller extends \Visitor\Controller {
  public $permissions = array(
    'index'  => 'public',
  );

  private $maxRecordings = 4;
  private $blocksToTypes = array(
    'legujabb'           => 'newest',
    'legnezettebb'       => 'mostviewed',
    'legjobb'            => 'best',
    'csatornafelvetelek' => 'subscriptions',
  );

  public function indexAction() {
    $this->recordingsModel = $this->bootstrap->getModel('recordings');
    $user = $this->bootstrap->getSession('user');

    $this->toSmarty['defaultimage'] =
      $this->bootstrap->staticuri . 'images/header_logo.png'
    ;
    $this->toSmarty['welcome'] = true;

    $l = $this->bootstrap->getLocalization();
    $labels = array();
    $blocks = array();
    foreach( $this->organization['blockorder'] as $block => $value ) {
      if ( $block != 'eloadas' and $block != 'kiemelt' )
        $labels[ $block ] = $l('index', 'block_' . $block );

      if ( isset( $this->blocksToTypes[ $block ] ) ) {
        $type = $this->blocksToTypes[ $block ];
        $blocks[ $block ] = \Visitor\Recordings\Paging\Featured::getRecItems(
          $this->organization['id'],
          $user,
          $type,
          0,
          $this->maxRecordings
        );
      } else {
        $method = 'getBlock' . ucfirst( $block );
        if ( method_exists( $this, $method ) )
          $blocks[ $block ] = $this->$method( $user );
      }
    }

    $this->toSmarty['blocksToTypes'] = $this->blocksToTypes;
    $this->toSmarty['labels'] = $labels;
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
    return $this->recordingsModel->getRandomRecordings(
      4,
      $this->organization['id'],
      $user
    );
  }

  private function getBlockEloadas( $user ) {
    $channelModel = $this->bootstrap->getModel('channels');
    return $channelModel->getFeatured(
      $this->organization['id'],
      \Springboard\Language::get()
    );
  }
}
