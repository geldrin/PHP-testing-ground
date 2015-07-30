<?php
namespace Visitor\Analytics;

class Controller extends \Visitor\Controller {
  public $permissions = array(
    'accreditedrecordings' => 'clientadmin',
    'statistics'           => 'clientadmin',
    'searchrecordings'     => 'clientadmin',
    'searchlive'           => 'clientadmin',
    'searchgroups'         => 'clientadmin',
    'searchusers'          => 'clientadmin',
  );

  public $forms = array(
    'accreditedrecordings' => 'Visitor\\Analytics\\Form\\Accreditedrecordings',
    'statistics'           => 'Visitor\\Analytics\\Form\\Statistics',
  );

  public $paging = array(
  );

  private $maxresults = 20;

  private function getCursor( $page ) {
    $start = $page * $this->maxresults;
    $end   = $start + $this->maxresults;
    return array(
      'start' => $start,
      'end'   => $end,
    );
  }

  public function searchrecordingsAction() {
    $ret = array();
    $term = $this->application->getParameter('q');
    $page = $this->application->getNumericParameter('page');
    if ( mb_strlen( trim( $term ) ) < 2 )
       $this->jsonOutput( $ret );

    $user     = $this->bootstrap->getSession('user');
    $cursor   = $this->getCursor( $page );
    $recModel = $this->bootstrap->getModel('recordings');
    $data     = $recModel->searchStatistics(
      $user,
      $term,
      $this->organization['id'],
      $cursor['start'],
      $cursor['end']
    );

    if ( !empty( $data ) ) {
      include_once( $this->bootstrap->config['templatepath'] . 'Plugins/modifier.nickformat.php' );
      include_once( $this->bootstrap->config['templatepath'] . 'Plugins/modifier.mb_truncate.php' );
      include_once( $this->bootstrap->config['templatepath'] . 'Plugins/modifier.indexphoto.php' );
    }

    $ret['results'] = array();
    foreach( $data as $key => $value ) {
      $ret['results'][] = array(
        'id'                => $value['id'],
        'title'             => $value['title'],
        'subtitle'          => $value['subtitle'],
        'timestamp'         => $value['timestamp'],
        'recordedtimestamp' => $value['recordedtimestamp'],
        'imgsrc'            => smarty_modifier_indexphoto( $value, 'player' ),
        'user'              => smarty_modifier_nickformat( $value ),
        'text'              => smarty_modifier_mb_truncate(
          $value['title'] . ( strlen( trim( $value['subtitle'] ) )? ' (' . $value['subtitle'] . ')': '' ),
          20
        ),
      );
    }

    $this->jsonOutput( $ret );
  }

  public function searchliveAction() {
    $ret = array();
    $term = $this->application->getParameter('q');
    $page = $this->application->getNumericParameter('page');
    if ( mb_strlen( trim( $term ) ) < 2 )
       $this->jsonOutput( $ret );

    $user   = $this->bootstrap->getSession('user');
    $cursor = $this->getCursor( $page );


    $ret['results'] = array();
    $this->jsonOutput( $ret );
  }

  public function searchgroupsAction() {
    $ret = array();
    $term = $this->application->getParameter('q');
    $page = $this->application->getNumericParameter('page');
    if ( mb_strlen( trim( $term ) ) < 2 )
       $this->jsonOutput( $ret );

    $user   = $this->bootstrap->getSession('user');
    $cursor = $this->getCursor( $page );

    $this->jsonOutput( $ret );
  }

  public function searchusersAction() {
    $ret = array();
    $term = $this->application->getParameter('q');
    $page = $this->application->getNumericParameter('page');
    if ( mb_strlen( trim( $term ) ) < 2 )
       $this->jsonOutput( $ret );

    $user   = $this->bootstrap->getSession('user');
    $cursor = $this->getCursor( $page );

    $ret['results'] = array();
    $this->jsonOutput( $ret );
  }
}
