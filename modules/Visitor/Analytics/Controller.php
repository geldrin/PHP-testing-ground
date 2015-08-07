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

  private $maxresults = 10;

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
      $this->bootstrap->includeTemplatePlugin('nickformat');
      $this->bootstrap->includeTemplatePlugin('mb_truncate');
      $this->bootstrap->includeTemplatePlugin('indexphoto');
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
    $ret  = array();
    $term = $this->application->getParameter('q');
    $page = $this->application->getNumericParameter('page');
    if ( mb_strlen( trim( $term ) ) < 2 )
       $this->jsonOutput( $ret );

    $user      = $this->bootstrap->getSession('user');
    $cursor    = $this->getCursor( $page );
    $feedModel = $this->bootstrap->getModel('livefeeds');
    $results   = $feedModel->searchStatistics(
      $user,
      $term,
      $this->organization['id'],
      $cursor['start'],
      $cursor['end']
    );

    if ( !empty( $results ) ) {
      $this->bootstrap->includeTemplatePlugin('shortdate');
      $this->bootstrap->includeTemplatePlugin('mb_truncate');
      $this->bootstrap->includeTemplatePlugin('indexphoto');
    }

    $ret['results'] = array();
    foreach( $results as $result ) {

      if ( $result['starttimestamp'] )
        $interval = smarty_modifier_shortdate(
          '%Y. %B %e',
          $result['starttimestamp'],
          $result['endtimestamp']
        );
      else
        $interval = '';

      $name = $result['channeltitle'] . ' (' . $result['name'] . ')';
      $ret['results'][] = array(
        'id'          => $result['id'],
        'name'        => $name,
        'text'        => smarty_modifier_mb_truncate( $name, 20 ),
        'interval'    => $interval,
        'channeltype' => $result['channeltype'],
        'imgsrc'      => smarty_modifier_indexphoto( $result, 'live' ),
      );
    }

    $this->jsonOutput( $ret );
  }

  public function searchgroupsAction() {
    $ret = array();
    $term = $this->application->getParameter('q');
    $page = $this->application->getNumericParameter('page');
    if ( mb_strlen( trim( $term ) ) < 2 )
       $this->jsonOutput( $ret );

    $user       = $this->bootstrap->getSession('user');
    $cursor     = $this->getCursor( $page );
    $groupModel = $this->bootstrap->getModel('groups');
    $results    = $groupModel->searchStatistics(
      $user,
      $term,
      $this->organization['id'],
      $cursor['start'],
      $cursor['end']
    );

    if ( !empty( $results ) ) {
      $this->bootstrap->includeTemplatePlugin('mb_truncate');
      $this->bootstrap->includeTemplatePlugin('numberformat');
    }

    $ret['results'] = array();
    foreach( $results as $result ) {
      $ret['results'][] = array(
        'id'        => $result['id'],
        'name'      => $result['name'],
        'text'      => smarty_modifier_mb_truncate( $result['name'], 20 ),
        'usercount' => smarty_modifier_numberformat( $result['usercount'] ),
      );
    }

    $this->jsonOutput( $ret );
  }

  public function searchusersAction() {
    $ret = array();
    $term = $this->application->getParameter('q');
    $page = $this->application->getNumericParameter('page');
    if ( mb_strlen( trim( $term ) ) < 2 )
       $this->jsonOutput( $ret );

    $user      = $this->bootstrap->getSession('user');
    $cursor    = $this->getCursor( $page );
    $userModel = $this->bootstrap->getModel('users');
    $results   = $userModel->searchStatistics(
      $user,
      $term,
      $this->organization,
      $cursor['start'],
      $cursor['end']
    );

    if ( !empty( $results ) ) {
      $this->bootstrap->includeTemplatePlugin('mb_truncate');
      $this->bootstrap->includeTemplatePlugin('nickformat');
      $this->bootstrap->includeTemplatePlugin('avatarphoto');
    }

    $ret['results'] = array();
    foreach( $results as $result ) {
      $name = smarty_modifier_nickformat( $result );
      $ret['results'][] = array(
        'id'     => $result['id'],
        'imgsrc' => smarty_modifier_avatarphoto( $result ),
        'name'   => $name,
        'text'   => smarty_modifier_mb_truncate( $name, 20 ),
      );
    }

    $this->jsonOutput( $ret );
  }
}
