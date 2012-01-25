<?php

// THCOLSPANLAYOUT ES THLAYOUT CUSTOM MEZOHEADER-
// BEALLITASOKAT TESZTELNI!

class listing_tree extends listing {

  var $fields = Array();
  var $table;
  var $where;
  var $order = Array();
  var $deletesql = Array(); 
    // an array containing SQL statements like
    //   SELECT count(*) FROM product_categories WHERE parentid='<PARENTID>'
    //   SELECT count(*) FROM products WHERE categoryid='<PARENTID>'
    //
    // they'll be executed and their results will be summarized.
    // if results of count(*) is not 0, a delete button will appear

  var $sql;
  var $propagate = Array(); // #!!! tesztelendo
  var $maxlevel = 15;

  var $tablelayout      = "<table cellpadding=\"3\" cellspacing=\"0\" class=\"listing tree\" border=\"0\">\n%s\n</table>\n";
  var $tdfillerlayout   = "<td width=\"10\"><img src=\"images/error.gif\" /></td>\n";
  var $tdlayout         = "<td class=\"treemiddle\">%s</td>\n";
  var $tdcolspanlayout  = "<td class=\"treemiddle\" colspan=\"%s\">&nbsp;%s</td>\n";
  var $thlayout         = "<th>%s</th>";
  var $thcolspanlayout  = "<th colspan=\"%s\">%s</th>\n";
  //var $tbodylayout      = "</table><div style=\"display: none;\" id=\"%s\"><table>\n%s\n</table></div><table>\n";
  var $tbodylayout      = "<tbody nostyle=\"display: none;\" id=\"%s\">\n%s\n</tbody>\n";
  var $headerlayout     = "<tr>%s</tr>";
  var $graphic          = 1;
  var $td_node          = '<td width="14" background="images/tree_node.png">&nbsp;</td>';
  var $td_lastnode      = '<td width="14" background="images/tree_lastnode.png">&nbsp;</td>';
  var $td_continue      = '<td width="14" background="images/tree_continue.png">&nbsp;</td>';

  var $beforeheader     = "";
  var $afterheader      = "";
  var $trlayouts        = Array( "<tr>%s</tr>\n" );
  var $trseparators     = Array();
  var $trlayoutcount    = 0;
  var $trseparatorcount = 0;

  var $children         = Array();
  var $lastchildren     = Array();

  var $addfields        = Array();

  var $treeid;
  var $treestart;
  var $treeparentid;
  var $treestartinclusive = true;

  // private
  var $results    = Array(); 
  var $allrecords = 0;

  // -------------------------------------------------------------------------
  function listing_tree( &$db, &$config ) {

    include_once( LISTING_DIR . 'listingdb.php');
    include_once( LISTING_DIR . 'listingdb_adodb.php');

    foreach ( $config as $key => $value ) 
      $this->$key = $value;

    $this->db = &$db;

  }

  // -------------------------------------------------------------------------
  function gethtml() {

    // SET UP DATABASE DETAILS

    // fields to select by configuration
    $selectfields = Array();
    foreach ( $this->fields as $key => $value )
      if ( isset( $this->fields[ $key ]['field'] ) ) 
        $selectfields[] = 
          $this->fields[ $key ]['field'];

    // field surely needed for recursive tree building
    $this->addfields = $this->addfields + Array( $this->treeid );

    $this->dblayer = new listingdb_adodb( 
      $this->db, 
      $this->table, 
      array_unique( 
        array_merge( $selectfields, $this->propagate, $this->addfields ) 
      )
    );
    
    $this->dblayer->order   = $this->order;
    $this->dblayer->modify  = $this->modify;
    $this->dblayer->delete  = $this->delete;

    $header = '';

    // COMPILE HEADER

    $modifyfieldset = 0;
    $deletefieldset = 0;

    foreach ( $this->fields as $key => $field ) {
      if ( isset( $field['modify'] ) ) 
        $modifyfieldset = 1;
      if ( isset( $field['delete'] ) ) 
        $deletefieldset = 1;
      $lastkey = $key; // will be used for deciding about the separators
    }

    $header  = '';
    $fieldno = 0;
    foreach ( $this->fields as $key => $field ) {
      
      $th = '';

      if ( isset( $this->fields[ $key ]['displayname'] ) )
        $th .= $this->fields[ $key ][ 'displayname' ];

      if ( $fieldno == 0 ) {

        $thislayout = $this->thcolspanlayout;
        if ( isset( $this->fields[ $key ]['thcolspanlayout'] ) )
          $thislayout = $this->fields[ $key ]['thcolspanlayout'];

        $header .= sprintf(
          $this->thcolspanlayout, 
          ( $this->maxlevel ), //- ( count( $this->fields ) + $modifyfieldset + $deletefieldset + 1 ) ), 
          $th
        ) . "\n";

      }
      else {

        $thislayout = $this->thlayout;
        if ( isset( $this->fields[ $key ]['thlayout'] ) )
          $thislayout = $this->fields[ $key ]['thlayout'];

        $header .= sprintf( $thislayout, $th ) . "\n";

      }

      $fieldno++;
    }

    $header .=
      ( !$modifyfieldset && $this->modify ?
          sprintf( $this->thlayout, LISTING_BUTTON_MODIFY ) . "\n" : '' ) .
      ( !$deletefieldset && $this->delete ?
          sprintf( $this->thlayout, LISTING_BUTTON_DELETE ) . "\n" : '' )
    ;

    $fullheader = 
      $this->beforeheader . 
      sprintf( $this->headerlayout, $header ) . 
      $this->afterheader;

    return 
      '<a name="listing_of_items"></a>'.
      sprintf( 
        $this->tablelayout, 
        $fullheader . $this->buildTree( $this->treestart, 0 )
      )
    ;

  } 

  // -------------------------------------------------------------------------
  function buildTree( $parentid, $level ) {

    $sql_where = $this->where;

    if ( strpos( $this->where, '<WHERE>' ) !== false )
      $sql_where = str_replace( '<WHERE>', $sql_where, $this->where );
 
    if (
         $this->treestartinclusive &&
         ( $level == 0 ) &&
         ( $parentid != 0 )
       ) {

      $this->dblayer->where =
        ( strlen( $sql_where ) ? "(" . $sql_where . ") AND " : '' ) .
        " ( " . $this->treeid . " = '" . $this->treestart . "' ) "
      ;
    }
    else {
      $this->dblayer->where = $sql_where;
      $this->dblayer->searchfield     = $this->treeparent;
      $this->dblayer->searchcondition = 'EQ';
      $this->dblayer->searchvalue     = $parentid;
    }

    $this->dblayer->setfilter();
    $results = $this->dblayer->getresults();

    $out     = '';

    for ( $i = 0; $i < count( $results ); $i++ ) {

      // FIND IF CURRENT NODE HAS CHILDREN UNDER
      // IT IS IMPORTANT TO KNOW BECAUSE OF THE 
      // TREE IMAGES

      $this->lastchildren[ $level ] = $i == ( count( $results ) - 1 );

      $children = $this->buildTree( 
        $results[ $i ][ $this->treeid ], $level + 1 
      );

      $this->children[ $level ] = strlen( $children ) > 0;
      
      $fields  = $results[ $i ];
      $row     = '';

      // in every row, the first field fills up
      // 15 cells, then the other fields are simply put out

      $row         = '';
      $treegraphic = '';

      if ( $level ) {

        for ( $levels = 1; $levels < $level; $levels++ ) {

          if ( $this->lastchildren[ $levels ] )
            $treegraphic .= sprintf( $this->tdlayout, '' );
          else
            $treegraphic .= $this->td_continue;

        }

        if ( $i == ( count( $results ) - 1 ) ) 
          $treegraphic .= $this->td_lastnode;
        else 
          $treegraphic .= $this->td_node;

      }

      $fieldsno = 0;

      foreach ( $this->fields as $key => $field ) {
                                 
        $tdlayout = $this->tdlayout;
        if ( isset( $field['layout'] ) )
          $tdlayout = $field['layout'];
        $tdcolspanlayout = $this->tdcolspanlayout;
        if ( isset( $field['colspanlayout'] ) )
          $tdcolspanlayout = $field['colspanlayout'];

        if ( isset( $field['field'] ) ) {

          $value = $fields[ $field['field'] ];

          if ( 
               isset( $field['field'] ) && 
               isset( $field['lov'] ) && 
               isset( $field['lov'][ 
                 $results[ $i ][ $field['field'] ] 
               ] )
             ) {

            $value =
              $field['lov'][   $results[ $i ][   $field['field']   ]   ];

          }

          if ( isset( $field['phptrigger'] ) ) {

            if ( strpos( $field['phptrigger'], '<VALUE>' ) !== false )
              eval (
                '$value = ' .
                str_replace(
                  '<VALUE>',
                  addslashes( $results[ $i ][ $field['field'] ] ),
                  $field['phptrigger']
                ) . ';'
              );
            else
              eval( '$value = ' . $field['phptrigger'] . ';' );
          }

        }
        else
          $value = '';

        if ( $fieldsno == 0 ) {

          $link = $value;
          if ( strlen( $children ) ) 
            $link = $value;

          $row .= 
            $treegraphic . 
            sprintf(
              $tdcolspanlayout,
              ( $this->maxlevel - $level ), 
              $link
            );
        }
        else
          $row .= sprintf( $tdlayout, $value );

        $fieldsno++;

      } // foreach $this->fields

      // modify button 

      if ( $this->modify ) 
        $row .= "<td><input type=\"button\" onclick=\"location.href='" . 
           $this->url . '?target=modify&id=' . 
             $fields[ 'pager_modify_field' ] . 
           "';\" value=\"" . LISTING_BUTTON_MODIFY . "\"></td>";

      // delete button

      if ( count( $this->deletesql ) ) {

        $disable_delete = 0;
        
        foreach ( $this->deletesql as $deletesql ) {

          $deletesql       =
            str_replace('<PARENTID>', 
              $results[ $i ][ $this->treeid ], $deletesql 
            );

          $disable_delete += 
            $this->db->getOne( $deletesql );

        }

        if ( $disable_delete == 0 )
          $row .= "<td><input type=\"button\" onclick=\"if ( confirm( '" . LISTING_AREYOUSURE . "' ) ) location.href='" . 
            $this->url . '?target=delete&id=' . 
              $results[ $i ][ 'pager_delete_field' ] . "';\" value=\"".LISTING_BUTTON_DELETE."\"></td>";
        else
          $row .= "<td></td>";

      }

      // APPLY CURRENT ROWLAYOUT

      $out .= sprintf( $this->trlayouts[ $this->trlayoutcount ], $row );
      $this->trlayoutcount++;
      if ( $this->trlayoutcount > ( count( $this->trlayouts ) - 1 ) )
        $this->trlayoutcount = 0;

      // APPEND CURRENT SEPARATOR

      if ( count( $this->trseparators ) ) {
        $out .= $this->trseparators[ $this->trseparatorcount ];
        $this->trseparatorcount++;
        if ( $this->trseparatorcount > ( count( $this->trseparators ) - 1 ) )
          $this->trseparatorcount = 0;
      }

      if ( strlen( $children ) ) {
        $children = 
          sprintf( 
            $this->tbodylayout, 
            "childrenof" . $fields[ $this->treeid ], 
            $children 
          );
      }

      $out .= $children;

    } // for 

    return $out;

  }

}

?>