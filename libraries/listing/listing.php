<?php

class listing {

  var $db;
  var $type    = 'flat';

  var $resultset;

  var $fields = Array();

  var $url;

  var $table;
  var $where;
  var $order;

  var $direction = 'asc';

  var $searchfield = '';
  var $searchvalue = '';
  var $sql;

  var $parameters       = 'cellpadding="2" cellspacing="0" border=1';
  var $headerparameters = 'style="background-color: #f0f0f0;"';
  var $pagerparameters  = 'style="background-color: #d0d0d0;"';

  var $modify;
  var $delete;

  // private
  var $results    = Array(); 
  var $allrecords = 0;

  var $listingobject;

  // -------------------------------------------------------------------------
  function __construct( &$db, &$config, $url = false ) {

    if ( !$url ) 
      $this->url = $_SERVER['PHP_SELF'];
    else
      $this->url = $url;

    if ( !defined( 'LISTING_DIR' ) )  
      define('LISTING_DIR', dirname( __FILE__ ) . DIRECTORY_SEPARATOR );

    if ( isset( $config['type'] ) ) 
      $this->type = $config['type'];

    switch ( $this->type ) {

      case 'tree': 
        include_once( LISTING_DIR . 'listing-tree.php');
        $this->listingobject = new listing_tree( $db, $config );
        break;

      default:
        include_once( LISTING_DIR . 'listing-flat.php');
        $this->listingobject = new listing_flat( $db, $config );
        break;
    }
        
    $this->listingobject->url = $url;

  }

  // -------------------------------------------------------------------------
  function gethtml( ) {

    return $this->listingobject->gethtml();

  } 

}

?>