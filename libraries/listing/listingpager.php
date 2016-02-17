<?php

class listingpager {
  
  var $all;
  var $start;
  var $perpage;
  var $script;
  var $pagestoshow = 10;

  var $pass = Array();
  var $pre = '';

  var $tableparameter;
  var $currentpageparameters;
  var $linkparameter;

  var $bookmark = '#listing_of_items';

  // --------------------------------------------------------------------------
  function __construct( 
    $script, $all = 0, $start = 0 , $perpage = 10, 
    $linkparameters        = '',
    $currentpageparameters = 'STYLE="padding: 5px; background-color: #606060; color: white;"',
    $tableparameters       = 'CELLPADDING=0 BORDER=0 CELLSPACING=4' ) {

    $this->script  = $script;
    $this->all     = $all;
    $this->start   = $start;
    $this->perpage = $perpage;
    $this->tableparameters       = $tableparameters;
    $this->linkparameters        = $linkparameters;
    $this->currentpageparameters = $currentpageparameters;

  }

  // --------------------------------------------------------------------------
  function gethtml() {

    $nrofpages      = ceil( $this->all / $this->perpage );
    $nrofclusters   = ceil( $nrofpages / $this->pagestoshow );
    $currentcluster =  
      floor (
        floor( $this->start / $this->perpage ) / $this->pagestoshow
      );

    $controls = Array();

    if ( strlen( $this->pre ) && ( $this->all > $this->perpage ) )  
      $controls[] = $this->pre;

    if ( $this->start > 0 ) 
      $controls[] = '<A '.$this->linkparameters.' HREF="' . $this->_geturl() . 'start=0' . $this->bookmark . '">|&lt;</A>';

    if ( ( $this->start - $this->perpage ) >= 0 ) 
      $controls[] = '<A '.$this->linkparameters.' HREF="' . $this->_geturl() . 'start=' . ( $this->start - $this->perpage ) . $this->bookmark . '">&lt;</A>';

    $endofcurrentcluster = $nrofpages;
    if ( 
         ( ( $currentcluster + 1 ) * $this->pagestoshow ) 
           < $endofcurrentcluster 
       ) 
      $endofcurrentcluster = ( $currentcluster + 1 ) * $this->pagestoshow;

    $prevclusterlastpage = 
      ( $currentcluster * $this->pagestoshow - 1 ) * $this->perpage;

    if ( $currentcluster > 0 ) 
      $controls[] = '<A '.$this->linkparameters.' HREF="' . $this->_geturl() . 'start=' . $prevclusterlastpage . $this->bookmark . '">...</A>';

    for ( 
      $i = $currentcluster * $this->pagestoshow; 
      $i < $endofcurrentcluster; 
      $i++ ) {

      if ( $this->start == $i * $this->perpage ) 
        $controls[] = 
          '<SPAN '.$this->currentpageparameters .'>' . 
            ( $i + 1 ) . 
          '</SPAN>';
      else
        $controls[] = 
          '<A '.$this->linkparameters.' HREF="' . $this->_geturl() . 'start=' . ( $i * $this->perpage ) . $this->bookmark . '">' . ( $i + 1 ) . '</A>';

    }

    if ( $currentcluster < ( $nrofclusters - 1 ) ) 
      $controls[] = '<A '.$this->linkparameters.' HREF="' . 
        $this->_geturl() . 
        'start=' . ( ( ( $currentcluster + 1 ) * $this->pagestoshow ) * $this->perpage ). 
        $this->bookmark . 
        '">...</A>';

    if ( ( $this->start + $this->perpage ) < $this->all ) {
      $controls[] = '<A '.$this->linkparameters.' HREF="' . $this->_geturl() . 'start=' . ( $this->start + $this->perpage ) . $this->bookmark .  '">&gt;</A>';
      $controls[] = '<A '.$this->linkparameters.' HREF="' . $this->_geturl() . 'start=' . ( ( $nrofpages - 1 ) * $this->perpage ). $this->bookmark . '">&gt;|</A>';
    }

    return 
      '<TABLE ' . $this->tableparameters . '>'.
      '<TR><TD>' . implode( '</TD><TD>', $controls ) . '</TD></TR>'.
      '</TABLE>';
    
  }

  // --------------------------------------------------------------------------
  function pass( $variable, $value ) {

    if ( is_array( $value ) ) 
      foreach ( $value as $valueitem ) 
        $this->pass[] = $variable . '[]=' . rawurlencode( $valueitem );
    else
      $this->pass[] = $variable . '=' . rawurlencode( $value );

  }

  // --------------------------------------------------------------------------
  function _geturl() {

    return
      $this->script . '?' . $this->getparameters();
  }

  // --------------------------------------------------------------------------
  function getparameters() {

    $parameters = implode('&', $this->_getpassvars() );
    if ( strlen ( $parameters ) ) 
      $parameters .= '&';

    return $parameters;

  }

  // --------------------------------------------------------------------------
  function _getpassvars() {

    $vars = Array();
    foreach ( $this->pass as $value ) 
      $vars[] = $value;

    return $vars;

  }

}

?>