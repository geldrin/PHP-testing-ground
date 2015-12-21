<?php
namespace Visitor\Index;

class Controller extends \Visitor\Controller {
  public $permissions = array(
    'index'  => 'public',
  );

  private $maxRecordings = 4;
  private $recordingTypes = array(
    'featured' => array(
      'order'  => 'timestamp DESC',
      'filter' => " AND r.isfeatured = '1'",
    ),
    'mostviewed' => array(
      'order'  => 'numberofviews DESC',
    ),
    'highestrated' => array(
      'order'  => 'rating, numberofratings DESC',
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
    $this->toSmarty['welcome']      = true;
    $this->toSmarty['recordings']   = $this->recordingsModel->getRandomRecordings(
      $this->maxRecordings,
      $this->organization['id'],
      $user
    );

    $this->recordingsModel->addPresentersToArray(
      $this->toSmarty['recordings'],
      true,
      $this->organization['id']
    );

    $keys = array_keys( $this->recordingTypes );
    foreach( $keys as $key )
      $this->getRecordings( $key, $user );

    $this->smartyoutput('Visitor/Index/index.tpl');
  }

  private function getRecordings( $type, $user ) {
    $filter = "r.organizationid = '" . $this->organization['id'] . "'";
    if ( isset( $this->recordingTypes[ $type ]['filter'] ) )
      $filter .= $this->recordingTypes[ $type ]['filter'];

    $this->toSmarty[ $type ] = $this->recordingsModel->getRecordingsWithUsers(
      0,
      $this->maxRecordings,
      $filter,
      $this->recordingTypes[ $type ]['order'],
      $user,
      $this->organization['id']
    );

    $this->recordingsModel->addPresentersToArray(
      $this->toSmarty[ $type ],
      true,
      $this->organization['id']
    );
  }

}
