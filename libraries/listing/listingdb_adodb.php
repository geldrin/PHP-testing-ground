<?php

// mssql fix: 
// AS hack: http://bugs.php.net/bug.php?id=33060

class listingdb_adodb extends listingdb {

  var $table;
  var $fields = Array();

  var $modify;
  var $delete;
  var $order   = Array();
  var $where;
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
  function __construct( &$db, $source, $fields ) {
    $this->db     = &$db;
    $this->table  = $source;
    $this->fields = $fields;
  }

  // --------------------------------------------------------------------------
  function &getresults( $perpage = null, $start = null) {

    $asconversion = Array();

    $allfields = Array();

    foreach ( $this->fields as $key => $field ) {
      if ( !is_numeric( $key ) ) {
        $allfields[] = $field . ' as "' . $key . '"';
      }
      else {
        $parts = preg_split( "/\sAS\s/i", $field, 2 );

        if ( !preg_match( '/^\s*CAST.*$/i', $parts[0] ) && isset( $parts[1] ) ) {
          $key          = $parts[1];
          $asconversion[ $key ] = $field;
          $field        = $parts[0];
        }
        else
          $key = $field;

        $allfields[] = $field . ' as "' . $key . '"';
      }
    }

    $sql =  
      'SELECT ' . 
        implode( ', ', $allfields ) . ' ' .
        ( $this->modify ? ', ' . $this->modify . ' as pager_modify_field ' : '' ) .  
        ( $this->delete ? ', ' . $this->delete . ' as pager_delete_field ' : '' ) . 
      'FROM ' . $this->table . ' ' . 
      ( strlen( $this->where ) ? 'WHERE ' . $this->where : '' ) . ' ' .
      ( count( $this->order ) ? 'ORDER BY ' . implode(', ', $this->order ) : '' );

    if ( ( $perpage === null ) && ( $start === null ) ) 
      $rs = $this->db->execute( $sql );
    else
      $rs = $this->db->selectlimit( $sql, $perpage, $start );

    if ( is_object( $rs ) ) {

      $this->results = $rs->getArray();

      if ( count( $asconversion ) ) {
        
        foreach ( $this->results as $rowid => $result ) {

          foreach ( $result as $key => $field ) {

            if ( isset( $asconversion[ $key ] ) )
              $this->results[ $rowid ][ $asconversion[ $key ] ] =
                $this->results[ $rowid ][ $key ];

          }

        }

      }
    }
    else
      die( $this->db->errormsg() );
    
    return $this->results;    

  }        

  // --------------------------------------------------------------------------
  function countall() {

    return 
      $this->db->getOne( 
          'SELECT count(*) FROM '. $this->table . ' ' . 
          ( strlen( $this->where ) ? 'WHERE ' . $this->where : '' )
      ); 

  }

  // --------------------------------------------------------------------------
  function setfilter() {

    $filter = '';

    if ( 
         strlen( $this->searchfield ) &&
         isset( 
           $this->possiblesearchconditions[ $this->searchcondition ] 
         ) 
       ) {
      switch ( $this->searchcondition ) {
        case 'LIKE':    $filter = $this->searchfield . " LIKE '%" . $this->searchvalue . "%'"; break;
        case 'NOTLIKE': $filter = $this->searchfield . " NOT LIKE '%" . $this->searchvalue . "%'"; break;
        case 'EQ':
          if ( $this->searchvalue === null )
            $filter = $this->searchfield . " IS NULL "; 
          else
            $filter = $this->searchfield . " = " . $this->searchvalue(); 
          break;
        case 'NOTEQ':   $filter = $this->searchfield . " <> " . $this->searchvalue(); break;
        case 'MORE':    $filter = $this->searchfield . " > " . $this->searchvalue(); break;
        case 'LESS':    $filter = $this->searchfield . " < " . $this->searchvalue(); break;
        default: die('listing search condition "' . $this->searchcondition . '" is unsupported'); break;
      }
    }

    if ( strlen( $filter ) )
      if ( strlen( $this->where ) )
        $this->where = '(' . $this->where . ')' . ' AND ' . $filter;
      else
        $this->where = $filter;

  }

  function &getsearchconditions() {
    return $this->possiblesearchconditions;
  }

  function searchvalue() {

    if ( $this->searchvalue !== null )
      return "'" . $this->searchvalue . "'";
    else
      return 'NULL';
  
  }

}

?>