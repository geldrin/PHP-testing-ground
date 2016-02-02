<?php
namespace Visitor\Recordings\Paging;

class Featured extends \Visitor\Paging {
  protected $orderkey = 'timestamp_desc';
  protected $sort     = Array(
    'timestamp_desc'       => 'timestamp DESC',
    'timestamp'            => 'timestamp',
    'views_desc'           => 'numberofviews DESC',
    'views'                => 'numberofviews',
    'viewsthisweek_desc'   => 'numberofviewsthisweek DESC',
    'viewsthisweek'        => 'numberofviewsthisweek',
    'viewsthismonth_desc'  => 'numberofviewsthismonth DESC',
    'viewsthismonth'       => 'numberofviewsthismonth',
    'rating_desc'          => 'rating DESC, numberofratings DESC',
    'rating'               => 'rating, numberofratings DESC',
    'ratingthisweek_desc'  => 'ratingthisweek DESC, numberofratings DESC',
    'ratingthisweek'       => 'ratingthisweek, numberofratings DESC',
    'ratingthismonth_desc' => 'ratingthismonth DESC, numberofratings DESC',
    'ratingthismonth'      => 'ratingthismonth, numberofratings DESC',
    'hiddennull'           => null,
  );
  protected $ignoreSortKeys = array(
    'hiddennull' => true,
  );
  protected $template = 'Visitor/recordinglistitem.tpl';
  protected $insertbeforepager = Array( 'Visitor/Recordings/Paging/FeaturedBeforepager.tpl' );
  protected $recordingsModel;
  protected $filter = '';
  protected $type   = '';
  protected $user;

  private static $recModel;
  private static $types = array(
    'featured' => array(
      'filter' => "r.isfeatured = '1' AND (r.featureduntil IS NULL OR r.featureduntil >= NOW())",
      'order'  => 'timestamp DESC',
    ),
    'highestrated' => array(
      'order' => 'rating',
    ),
    'mostviewed' => array(
      'order'  => 'numberofviews DESC',
    ),
    'newest' => array(
      'order'  => 'timestamp DESC',
    ),
    'best' => array(
      'order'  => 'combinedratingpermonth',
    ),
  );

  private static function getFilterForType( $organizationid, $type ) {
    $filter = "r.organizationid = '$organizationid'";
    if ( isset( self::$types[ $type ] ) and isset( self::$types[ $type ]['filter'] ) )
      $filter .= " AND " . self::$types[ $type ]['filter'];

    if ( $type === 'best' )
      $filter .= "r.timestamp >= DATE_SUB(NOW(), INTERVAL " .
        $this->bootstrap->config['combinedratingcutoffdays'] .
      " DAYS)";

    return $filter;
  }

  private static function getOrderForType( $type ) {
    if ( isset( self::$types[ $type ] ) )
      return self::$types[ $type ]['order'];

    return '';
  }

  public static function getRecCount( $organizationid, $user, $type ) {
    if ( !self::$recModel )
      self::$recModel = \Bootstrap::getInstance()->getModel('recordings');

    switch( $type ) {
      case 'subscriptions':
        $count = self::$recModel->getCountFromChannelSubscriptions(
          $user, $organizationid
        );
        break;

      default:
        $filter = self::getFilterForType( $organizationid, $type );
        $count = self::$recModel->getRecordingsCount(
          $filter, $user, $organizationid
        );
        break;
    }

    return $count;
  }

  public static function getRecItems( $organizationid, $user, $type, $start, $limit, $order = null ) {
    if ( !self::$recModel )
      self::$recModel = \Bootstrap::getInstance()->getModel('recordings');

    if ( $order === null )
      $order = self::getOrderForType( $type );

    switch ( $type ) {
      case 'subscriptions':
        $items = self::$recModel->getArrayFromChannelSubscriptions(
          $start, $limit, $order,
          $user, $organizationid
        );
        break;

      default:
        $filter = self::getFilterForType( $organizationid, $type );
        $items = self::$recModel->getRecordingsWithUsers(
          $start, $limit, $filter, $order,
          $user, $organizationid
        );
        break;
    }

    self::$recModel->addPresentersToArray(
      $items,
      true,
      $organizationid
    );

    return $items;
  }

  public function init() {
    
    $l                 = $this->bootstrap->getLocalization();
    $this->foreachelse = $l('recordings', 'foreachelse');
    $this->filter      = "r.organizationid = '" . $this->controller->organization['id'] . "'";
    $this->user        = $this->bootstrap->getSession('user');
    
    $this->type = $this->application->getParameter('subaction', 'featured');
    switch( $this->type ) {
      case 'newest':
        $this->orderkey = 'timestamp_desc';
      break;
      
      case 'highestrated':
        $this->orderkey = 'rating';
        $this->ignoreSortKeys = array(
          'timestamp_desc'       => true,
          'timestamp'            => true,
          'rating_desc'          => true,
          'rating'               => true,
          'ratingthisweek_desc'  => true,
          'ratingthisweek'       => true,
          'ratingthismonth_desc' => true,
          'ratingthismonth'      => true,
          'hiddennull'           => true,
        );
      break;
      
      case 'mostviewed':
        $this->orderkey = 'views_desc';
        $this->ignoreSortKeys = array(
          'timestamp_desc'      => true,
          'timestamp'           => true,
          'views_desc'          => true,
          'views'               => true,
          'viewsthisweek_desc'  => true,
          'viewsthisweek'       => true,
          'viewsthismonth_desc' => true,
          'viewsthismonth'      => true,
          'hiddennull'          => true,
        );
      break;
      
      case 'subscriptions':
        $this->ignoreSortKeys = array();
        foreach( array_keys( $this->sort ) as $key )
          $this->ignoreSortKeys[ $key ] = true;

        break;

      case 'best':
        $this->orderkey = 'hiddennull';
        $this->ignoreSortKeys = array();
        foreach( array_keys( $this->sort ) as $key )
          $this->ignoreSortKeys[ $key ] = true;

        break;

      case 'featured':
      default:
        $this->type    = 'featured';
        $this->filter .= " AND r.isfeatured = '1'";
        break;
      
    }
    
    $this->title = $l('recordings', 'featured_' . $this->type );
    $this->controller->toSmarty['listclass'] = 'recordinglist';
    $this->controller->toSmarty['type']      = $this->type;
    $this->controller->toSmarty['module']    = 'featured';
    parent::init();
    
  }
  
  protected function setupCount() {
    return $this->itemcount = self::getRecCount(
      $this->controller->organization['id'],
      $this->user,
      $this->type
    );
  }
  
  protected function getItems( $start, $limit, $orderby ) {
    return self::getRecItems(
      $this->controller->organization['id'],
      $this->user,
      $this->type,
      $start, $limit, $orderby
    );
  }
  
  protected function getUrl() {
    return
      $this->controller->getUrlFromFragment( $this->module . '/' . $this->action ) .
      '/' . $this->type
    ;
  }
}
