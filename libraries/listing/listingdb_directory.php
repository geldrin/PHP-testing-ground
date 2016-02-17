<?php

class listingdb_directory extends listingdb {

  var $directory;

  var $modify;
  var $delete;
  var $order   = Array();
  var $filter;
  var $results = Array();

  var $searchfield = '';
  var $searchcondition = '';
  var $searchvalue = '';

  var $possiblesearchconditions = 
    Array( 
      'LIKE'    => LISTING_SEARCH_LIKE     ,
      'NOTLIKE' => LISTING_SEARCH_NOT_LIKE ,
      'EQ'      => LISTING_SEARCH_EQ       ,
      'NOTEQ'   => LISTING_SEARCH_NOT_EQ   ,
      'LESS'    => LISTING_SEARCH_LESS     ,
      'MORE'    => LISTING_SEARCH_MORE     
    );
    
  // --------------------------------------------------------------------------
  function __construct( $config ) {

    foreach ( $config as $key => $value ) 
      $this->$key = $value;

  }

  // --------------------------------------------------------------------------
  function &getresults( $perpage = null, $start = null) {

    $dir = opendir( $this->directory );
    $row = 0;

    while ( $file = readdir( $dir ) ) {

      if ( 
           !preg_match( '/^[\.]+$/', $file ) && 
           !is_dir( $this->directory . '/' . $file ) 
         ) {
        
        $row = Array( 
              'filename' => $file, 
              'pager_modify_field' => $file,
              'pager_delete_field' => $file,
              'filesize' => filesize( $this->directory . '/' . $file ),
              'mtime' => date( "Y-m-d H:i:s", filemtime( $this->directory . '/' . $file ) ),
              'ROWID' => $row                                            
        );

        $include_row = 1;

        if ( strlen( $this->filter ) )
          eval( '$include_row = ' . $this->filter . ';' );

        if ( $include_row )
          $this->results[] = $row;
      
        $row++;
    
      }

    }

    $this->allrecords = count( $this->results );

    // SORTING RESULTS

    if ( 
         isset( $this->order ) && 
         is_array( $this->order ) && 
         count( $this->order ) 
       ) {

      // first array item will contain the first order field,
      // the second should contain DESC if present
      $firstorder = explode(' ', trim( $this->order[ 0 ] ) );
      if ( !isset( $firstorder[ 1 ] ) ) 
        $firstorder[ 1 ] = 'ASC';
      else
        $firstorder[ 1 ] = 'DESC';

      // multidimensional array sort

      for ( $i = 0; $i < count( $this->results ) - 1; $i++ ) {

        for ( $j = $i + 1; $j < count( $this->results ); $j++ ) {

          $swap = 0;
          switch ( $firstorder[ 1 ] ) {
            case 'ASC':
              if ( 
                   isset( $this->results[ $i ][ $firstorder[0] ] ) &&
                   isset( $this->results[ $j ][ $firstorder[0] ] )
                 )
                $swap = 
                  $this->results[ $i ][ $firstorder[0] ] > 
                  $this->results[ $j ][ $firstorder[0] ] ;
              else
                if ( !isset( $this->results[ $j ][ $firstorder[0] ] ) )
                  $swap = 1;
              break;

            case 'DESC':
              if ( 
                   isset( $this->results[ $i ][ $firstorder[0] ] ) &&
                   isset( $this->results[ $j ][ $firstorder[0] ] )
                 )
                $swap = 
                  $this->results[ $i ][ $firstorder[0] ] <
                  $this->results[ $j ][ $firstorder[0] ] ;
              else
                if ( !isset( $this->results[ $i ][ $firstorder[0] ] ) )
                  $swap = 1;
              break;
              break;
          }

          if ( $swap ) {
            $tmp = $this->results[ $i ];
            $this->results[ $i ] = $this->results[ $j ];
            $this->results[ $j ] = $tmp;
          }

        }

      }
      
    }

    // LIMITING RESULTSET

    if ( 
         ( $perpage !== null ) &&
         ( $start !== null )
       ) {
      
      $rowcount = 0;
      $filtered = Array();
      
      foreach ( $this->results as $key => $value ) {

        if ( 
             ( $rowcount >= $start ) &&
             ( $rowcount < ( $start + $perpage ) ) 
           )
          $filtered[] = $value;

        $rowcount++;
      
      }

      $this->results = $filtered;

    }

    return $this->results;    

  }        

  // --------------------------------------------------------------------------
  function countall() {

    return $this->allrecords;

  }

  // --------------------------------------------------------------------------
  function setfilter() {

    $filter = '';

    if ( 
         isset( 
           $this->possiblesearchconditions[ $this->searchcondition ] 
         ) 
       ) {
      switch ( $this->searchcondition ) {
        case 'LIKE':    $filter = 'strlen( "'.$this->searchvalue.'" ) && ( strpos( $row["'.$this->searchfield.'"], "' . $this->searchvalue . '" ) !== false )'; break;
        case 'NOTLIKE': $filter = 'strlen( "'.$this->searchvalue.'" ) && ( strpos( $row["'.$this->searchfield.'"], "' . $this->searchvalue . '" ) === false )'; break;
        case 'EQ':      $filter = '$row["'.$this->searchfield.'"] == "' . $this->searchvalue . '"'; break;
        case 'NOTEQ':   $filter = '$row["'.$this->searchfield.'"] != "' . $this->searchvalue . '"'; break;
        case 'MORE':    $filter = '$row["'.$this->searchfield.'"] > "' . $this->searchvalue . '"'; break;
        case 'LESS':    $filter = '$row["'.$this->searchfield.'"] < "' . $this->searchvalue . '"'; break;
        default: die('listing search condition "' . $this->searchcondition . '" is unsupported'); break;
      }
    }

    if ( strlen( $filter ) )
      $this->filter = $filter;

  }

  function &getsearchconditions() {
    return $this->possiblesearchconditions;
  }

}

?>