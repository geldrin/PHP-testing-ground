<?php

class listing_flat extends listing {

  var $start      = 0;
  var $perpage    = 50;
  var $pagerpages = 5;

  var $addfields = Array();

  var $table;
  var $fields;
  var $delete;
  var $deletelink;
  var $modify;
  var $where         = '';
  var $order         = Array();
  var $pagerdisabled = 0;
  var $orderdisabled = 0;
  var $searchdisabled = 0;
  var $propagate        = Array(); // array of _variables_ to propagate through urls (buttons)
  var $propagate_direct = Array(); // array of 'key=value' pairs to propagate (both for order links and buttons)
  var $beforeheader  = '';
  var $afterheader   = '';
  var $deletesql = Array(); 
    // an array containing SQL statements like
    //   SELECT count(*) FROM product_categories WHERE parentid='<PARENTID>'
    //   SELECT count(*) FROM products WHERE categoryid='<PARENTID>'
    //
    // they'll be executed and their results will be summarized.
    // if results of count(*) is not 0, a delete button will appear


  var $pagerlayout   = '<table class="pagertable" align="center" cellspacing="0" cellpadding="0" border="0"><tr><td class="pagercell" valign="center">%s</td><td class="searchcell" valign="center">%s</td></tr></table>';
  var $pagerparlink    = '';
  var $pagerparcurrent = 'class="currentpage"';
  var $pagerpartable   = 'cellpadding="0" border="0" cellspacing="4"';
  var $thlayout      = "<th>%s</th>\n";
  var $tdlayout      = "<td>%s</td>\n";
  var $tdsumlayout   = '<td><b>%s</b></td>';

  var $tablelayout   = 
    "\n\n<table width=\"100%%\" border=\"1\" cellpadding=\"4\" cellspacing=\"0\">%s</table>\n\n";
    // layout of the listing table itself

  var $trlayouts     = Array( "\n<tr>\n%s</tr>\n" ); // one table row
  var $trseparators  = Array(); // separators between rows, cycled through
  var $afterlist     = Array(); // closing row of the table, having the same
                                // amount of rows as the $trseparators!

  // search
  var $searchfieldlayout      = '<select name="field">%s</select> ';
  var $searchconditionlayout  = '<select name="condition">%s</select> ';
  var $searchcontentlayout    = '<input type=text name="value" value="%s"> ';
  var $searchsubmitlayout     = '<input type="submit" value="%s">';
  var $searchclearsavedlayout = ' <input type="button" onclick="location.href=\'%s?action=clearsession\'" value="%s">';

  var $searchcondition;
  var $searchfield;
  var $searchvalue;

  var $sumonchange = Array();  // an array of arrays containing 'triggerfield' => Array( 'sumfield1', 'sumfield2' )
  var $neversum = Array();     // an array containing expressions in form of Array( 'field' => 'exp' )

  // private
  var $currentorderindex = 0;
  var $results           = Array();
  var $allrecords        = 0;
  var $datasource;

  // -------------------------------------------------------------------------
  function listing_flat( &$db, &$config ) {

    foreach ( $config as $key => $value )
      $this->$key = $value;

    $this->db = &$db;

    $selectfields = Array();
    foreach ( $this->fields as $key => $value )
      if ( isset( $this->fields[ $key ]['field'] ) ) 
        $selectfields[] = 
          $this->fields[ $key ]['field'];

    $selectfields = array_merge( $selectfields, $this->propagate );

    if ( !isset( $config['type'] ) ) 
      $type = 'adodb';
    else
      $type = $config['type'];

    switch ( $type ) {

      case 'directory': 
        $this->datasource = new listingdb_directory(
          Array( 'directory' => $config['directory'] )
        );
        break;

      case 'csv': 
        $this->datasource = new listingdb_csv(
          Array( 'file' => $config['file'] )
        );
        break;

      case 'adodb':
        $this->datasource = new listingdb_adodb( 
          $this->db, 
          $this->table, 
          array_unique( 
            array_merge( $selectfields, $this->addfields )
          )
        );
        break;
    }

    // currently no propagation of values
    // implemented for order links
    if ( count( $this->propagate ) )
      $this->pagerdisabled = 1;

    if ( $this->pagerdisabled ) {
      $this->start   = 0;
      $this->perpage = 99999999;
    }

  }

  // -------------------------------------------------------------------------
  function gethtml( ) {

    $this->_getresults();

    include_once( LISTING_DIR . 'listingpager.php' );

    // pager is set up so we can at least use it to
    // get back the parameters to pass for the ordering links

    $pager = new listingpager( 
      $this->url,
      $this->allrecords,
      $this->start,
      $this->perpage,
      $this->pagerparlink,
      $this->pagerparcurrent,
      $this->pagerpartable
    );

    $pager->pagestoshow = $this->pagerpages;

    foreach ( $this->propagate_direct as $key => $value ) 
      $pager->pass( $key, $value );

    // we might need search parameters to pass
    // for the order field
    // the default order is to be passed in the end of this function

    if ( strlen( $this->searchfield ) ) {
      $pager->pass( 'action',    'search' );
      $pager->pass( 'field',     $this->searchfield );
      $pager->pass( 'value',     $this->searchvalue );
      $pager->pass( 'condition', $this->searchcondition );
    }
    
    $i = 0;

    $out  = '';
    $out .= $this->beforeheader;
    $out .= "<tr>\n";

    $firstorder = Array( '', '' );

    if ( count( $this->order ) ) {
      // first array item will contain the first order field,
      // the second should contain DESC if present
      $firstorder = explode(' ', trim( $this->order[ 0 ] ) );
      if ( !isset( $firstorder[ 1 ] ) ) 
        $firstorder[ 1 ] = '';
    }

    $modifyfieldset = 0;
    $deletefieldset = 0;
    foreach ( $this->fields as $key => $field ) {
      if ( isset( $field['modify'] ) ) 
        $modifyfieldset = 1;
      if ( isset( $field['delete'] ) ) 
        $deletefieldset = 1;
      $lastkey = $key; // will be used for deciding about the separators
    }

    foreach ( $this->fields as $key => $field ) {
      $orderpass  = isset( $field['field'] ) ? $field['field'] : '';
      $orderimage = '';
      $th         = '';

      if (                                         
           isset( $field['field'] ) &&
           ( $firstorder[ 0 ] == $field['field'] )
         ) {
         
        switch ( strtolower( $firstorder[ 1 ] ) ) {
          case 'desc':
            $orderpass   = $field['field']; 
            $orderimage .= ' <img src="images/order_desc.gif" />'; 
            break;
          default:
            $orderpass   = $field['field'] . ' DESC';     
            $orderimage .= ' <img src="images/order_asc.gif" />'; 
            break;
        }

      }

      if ( 
           isset( $this->fields[ $key ]['displayname'] ) &&
           isset( $this->fields[ $key ]['field'] ) &&
           !isset( $this->fields[ $key ]['orderdisabled'] ) &&
           !$this->orderdisabled
//           && !isset( $this->fields[ $key ]['phptrigger'] ) 
         )
        $th .= 
           "<a href='" . $this->url . 
           "?" . 
             $pager->getparameters() . 
             "order[]=" . rawurlencode( $orderpass ) . "#listing_of_items'>" .
           $this->fields[ $key ][ 'displayname' ] .
           "</a>";
      else
        if ( isset( $this->fields[ $key ]['displayname'] ) )
          $th .= $this->fields[ $key ][ 'displayname' ];

      $th  .= $orderimage;
      $thislayout = $this->thlayout;
      if ( isset( $this->fields[ $key ]['thlayout'] ) )
        $thislayout = $this->fields[ $key ]['thlayout'];
      $out .= sprintf( $thislayout, $th );
      $i++;
    }

    $out .= 
      ( !$modifyfieldset && $this->modify ? sprintf( $this->thlayout, '' ) : '' ) .
      ( !$deletefieldset && $this->delete ? sprintf( $this->thlayout, 
        '<a id="CMSdeletemultiple" href="#">' . LISTING_DELETEMULTIPLE . '</a>' .
        '<div id="CMSdeletemultiplecontrols" style="display: none">' .
          '<input onclick="return confirm( \'' . LISTING_AREYOUSURE . '\' );" type="submit" class="delete" value="' . LISTING_BUTTON_DELETE . '" />' .
          '<input id="CMSdeletemultiplecancel" type="button" class="delete" value="' . LISTING_BUTTON_CANCEL . '" />' .
          '<input id="CMSdeletemultipleAll"  type="checkbox" checked="checked" title="' . LISTING_DELETEMULTIPLE_ALL . '" />' .
          '<input id="CMSdeletemultipleNone" type="checkbox" title="' . LISTING_DELETEMULTIPLE_NONE . '" />' .
        '</div>'
      ) : '' ) .
      "</tr>\n\n";

    $out .= $this->afterheader;

    $trseparatorcount = 0;
    $trlayoutcount    = 0;
    
    $sum              = Array();
    $sumchanges       = Array();
    
    if ( count( $this->sumonchange ) ) {
      foreach ( $this->sumonchange as $field => $sums ) {
        $sum[ $field ]        = Array();
        $sumchanges[ $field ] = false;
        foreach ( $sums as $fieldtosum ) 
          $sum[ $field ][ $fieldtosum ] = 0;
      }
    }

    for ( $i = 0; $i < count( $this->results ); $i++ ) {

      $propagate = '';

      foreach ( $this->propagate_direct as $propkey => $propvalue )
        $propagate .= '&' . $propkey . '=' . rawurlencode( $propvalue );

      foreach ( $this->propagate as $field ) {
        $propagate .= 
          '&' . $field . '=' . 
          rawurlencode( $this->results[ $i ][ $field ] )
        ;
      }

      $j  = 0;
      $tr = '';
      
      foreach ( $this->fields as $key => $field ) {

        // we include it here, since because of the
        // phptriggers, the results array might change
        // as we go through the fields, and fields
        // of lower index might provide values for
        // later computed fields
        $fields = $this->results[ $i ];
        $fields['ROWCOUNT'] = $this->start + $i + 1;

        if ( !isset( $field['layout'] ) )
          $field['layout'] = $this->tdlayout;

        if ( 
             $modifyfieldset && 
             isset( $field['modify'] )
           ) {
          eval( '$value=' . $field["modify"] . ';' );
          if ( $value )
            $tr .= 
              sprintf(
                $field['layout'],
                "<input class=\"modify\" type=\"button\" onclick=\"location.href='" .
                $this->url . 
                  '?action=modify&id=' . rawurlencode( $this->results[ $i ][ 'pager_modify_field' ] ) . 
                  $propagate .
                "';\" value=\"" . LISTING_BUTTON_MODIFY . "\">"
              );
            else
              $tr .= sprintf( $field['layout'], '' );
          continue;
        }

        if ( 
             $deletefieldset && 
             isset( $field['delete'] )
           ) {

          eval( '$value=' . $field["delete"] . ';' );
          
          if ( $value ) {

            $url = 
              $this->url . 
              '?action=delete&id=' . rawurlencode( $this->results[ $i ][ 'pager_delete_field' ] );

            if ( $this->deletelink )
              eval( '$url = ' . $this->deletelink . ';' );

            $tr .= 
              sprintf(
              $field['layout'],
              "<nobr>" . 
              "<input class=\"delete\" type=\"button\" onclick=\"if ( confirm( '" . LISTING_AREYOUSURE . "' ) ) location.href='" . 
              $url . $propagate .
                "';\" value=\"".LISTING_BUTTON_DELETE."\">" .
              "<input class=\"deletecheckbox\" type=\"checkbox\" name=\"ids[]\" value=\"" . htmlspecialchars( $this->results[ $i ][ 'pager_delete_field' ] ) . "\">" .
              "</nobr>"
              ) ;

          }
          else
            $tr .= sprintf( $field['layout'], '' );
          continue;
        }

        $fieldvalue = '';

        if ( 
             isset( $field['field'] ) && 
             isset( $field['lov'] ) && 
             isset( $field['lov'][ 
               $this->results[ $i ][ $field['field'] ] 
             ] )
           ) {

          $this->results[ $i ][ $field['field'] ] =
            $field['lov'][   $this->results[ $i ][   $field['field']   ]   ];

        }

        if ( isset( $field['phptrigger'] ) ) {
         
          if ( strpos( $field['phptrigger'], '<VALUE>' ) !== false )
            eval(
              '$fieldvalue = ' .
              str_replace(
                '<VALUE>',
                addslashes( $this->results[ $i ][ $field['field'] ] ),
                $field['phptrigger']
              ) . ';'
            );
          else
            eval( '$fieldvalue = ' . $field['phptrigger'] . ';' );
          
          if (
               (
                   !isset( $field['field'] )
                 ||
                 (
                   !isset( $this->results[ $i ][ $field['field'] ] ) ||
                   !strlen( $this->results[ $i ][ $field['field'] ] )
                 )
               )
               &&
                 strlen( $fieldvalue )
             ){
            $this->results[ $i ][ $key ] = $fieldvalue;
          }
            
        }
        else
          if ( 
               isset( $field['field'] ) &&
               isset( $this->results[ $i ][ $field['field'] ] )
             )
            $fieldvalue = $this->results[ $i ][ $field['field'] ];
        
        $tr .= sprintf( $field['layout'], $fieldvalue );

        $j++;
      }

      if ( count( $this->sumonchange ) )
        $out .= $this->_onchangesum( $sumchanges, $sum, $i, $trlayoutcount );

      if ( !$modifyfieldset && $this->modify ) {
          $tr .= 
            sprintf(
              $this->tdlayout,
              "<center><input class=\"modify\" type=\"button\" onclick=\"location.href='" .
              $this->url . 
                '?action=modify&id=' . rawurlencode( $this->results[ $i ][ 'pager_modify_field' ] ) . 
                $propagate .
              "';\" value=\"" . LISTING_BUTTON_MODIFY . "\"></center>"
            );
      }

      if ( !$deletefieldset && $this->delete ) {
          $tr .= 
            sprintf(
              $this->tdlayout,
              "<nobr>". 
                "<input class=\"delete\" type=\"button\" onclick=\"if ( confirm( '" . LISTING_AREYOUSURE . "' ) ) location.href='" . 
                $this->url . 
                  '?action=delete&id=' . rawurlencode( $this->results[ $i ][ 'pager_delete_field' ] ) . 
                  $propagate .
                "';\" value=\"" . LISTING_BUTTON_DELETE . "\">" . 
                "<input class=\"deletecheckbox\" type=\"checkbox\" name=\"ids[]\" value=\"" . htmlspecialchars( $this->results[ $i ][ 'pager_delete_field' ] ) . "\">" .
              "</nobr>"
            );
      }

      $out .= sprintf( $this->trlayouts[ $trlayoutcount ], $tr );

      if ( ( $trlayoutcount + 1 ) >= count( $this->trlayouts ) ) 
        $trlayoutcount = 0;
      else      
        $trlayoutcount++;

      if ( count( $this->trseparators ) && ( $key != $lastkey ) ) {
        $out .= $this->trseparators[ $trseparatorcount ];
        if ( ( $trseparatorcount + 1 ) >= count( $this->trseparators ) ) 
          $trseparatorcount = 0;
        else
          $trseparatorcount++;
      }

    }

    if ( count( $this->sumonchange ) && $i )
      $out .= $this->_onchangesum( $sumchanges, $sum, $i, $trlayoutcount );

    if ( count( $this->afterlist ) ) {
      if ( isset( $this->afterlist[ $trseparatorcount ] ) )
        $out .= $this->afterlist[ $trseparatorcount ];
      else
        echo 'the afterlist must be an Array holding the same number of rows as the trseparators[] array';
    }

    // COMPILE HTML OUTPUT WITH PAGER AND SEARCH FORM

    $pagerhtml = '';

    if ( !$this->pagerdisabled ) {

      foreach ( $this->order as $anorder )
        $pager->pass( 'order[]', $anorder );

      $pagerhtml = 
        sprintf(
          $this->pagerlayout,
          $pager->gethtml(),
          $this->_getsearchform()
        );
    }

    $out = 
      '<form action="' . $this->url . '" method="post">' .
      '<input type="hidden" name="action" value="deletemultiple" />' .
      $out .
      '</form>'
    ;

    $html = 

      '<a name="listing_of_items"></a>'. "\n" .
      '<table id="frame" border="0" cellpadding="0" cellspacing="0">' . "\n" .
      ( strlen( $pagerhtml ) ? '<tr><td class="pager">'. $pagerhtml . '</td></tr>'. "\n" : '' ) .
      '<tr><td class="listing">' . sprintf( $this->tablelayout, $out ) . '</td></tr>' . "\n" . 
      ( strlen( $pagerhtml ) ? '<tr><td class="pager">'. $pagerhtml . '</td></tr>'. "\n" : '' ) .
      '</table>' . "\n";

    return $html;

  } 

  // --------------------------------------------------------------------------
  function _onchangesum( &$sumchanges, &$sum, $i, $trlayoutcount ) {

    $summarytr = '';

    $nosum  = Array();
  
    if ( isset( $this->results[ $i ] ) ) {
      $fields = $this->results[ $i ];

      foreach ( $this->neversum as $field => $expression ) {
        $value = false;
        eval( '$value = ' . $this->neversum[ $field ] . ';' );
        if ( $value ) 
          $nosum[ $field ] = 1;
      }
    }

    foreach ( $sumchanges as $field => $value ) {

      // if this is the first row in the table
      if ( $value === false ) {
        $sumchanges[ $field ] = $this->results[ $i ][ $field ];
      }
      else
        if ( 
             !isset( $this->results[ $i ] ) ||
             ( $this->results[ $i ][ $field ] !== $value )
           ) {

          $summarytds = '';

          // az osszes listabeli oszlopbol kivalasztjuk
          // azokat, amelyek a 'sumonchange' tombben
          // szerepelnek, majd ezekre kiiratjuk az osszegzest
          foreach ( $this->fields as $key => $configfield ) {
            
            if ( !isset( $configfield['sumlayout'] ) ) {
              $configfield['sumlayout'] = $this->tdsumlayout;
            }
            
            $fieldvalue = '';
            if ( isset( $configfield['field'] ) ) {
            	
              // az aktualis result-sor ertekeit atvesszuk
              if ( $configfield['field'] == $field )
                $fieldvalue = $value;

              // de ha az osszegzesben is szerepel az aktualis 
              // mezo, akkor onnan vesszuk az osszeget, mert akkor
              // ez egy gyujtogetett osszegmezo, es ezert vagyunk itt
              if ( isset( $sum[ $field ][ $configfield['field'] ] ) )
                $fieldvalue = $sum[ $field ][ $configfield['field'] ];
            }
            $summarytds .= sprintf( $configfield['sumlayout'], $fieldvalue ) . "\n";
          }

//          $summarytr .= sprintf(
//            $this->trlayouts[ $trlayoutcount ], $summarytds
//          ) . "\n";
          if ( isset( $this->sumrowlayouts[ $field ] ) ) {
            $summarytr .= sprintf(
              $this->sumrowlayouts[ $field ], $summarytds
            ) . "\n";
          }
          else
            $summarytr .= sprintf(
              $this->trlayouts[ $trlayoutcount ], $summarytds
            ) . "\n";

          // a mezore vonatkozo szummak nullazasa
          // es a nullazott szummak neveinek kigyujtese

          $cleanothers = Array();
          
          foreach ( $this->sumonchange[ $field ] as $fieldtosum ) {
            $sum[ $field ][ $fieldtosum ] = 0;
            $cleanothers[] = $fieldtosum;
          }
          
          reset( $sum );
          $doclean = 1;
          while ( $current = each( $sum ) ) {
            if ( $doclean ) {
              foreach ( $this->sumonchange[ $current['key'] ] as $fieldtosum )
                $sum[ $current['key'] ][ $fieldtosum ] = 0;
            }
            if ( $current['key'] == $field ) 
              $doclean = 0;
            
          }

          if ( isset( $this->results[ $i ] ) )
            $sumchanges[ $field ] = $this->results[ $i ][ $field ];

        }
    }

    // maga az osszeadas
    if ( isset( $this->results[ $i ] ) )
      foreach ( $this->sumonchange as $field => $sums ) {
        foreach ( $sums as $fieldtosum )
          if ( !isset( $nosum[ $fieldtosum ] ) ) 
            $sum[ $field ][ $fieldtosum ] += $this->results[ $i ][ $fieldtosum ] ;
      }

    return $summarytr;

  }

  // -------------------------------------------------------------------------
  function _getresults() {

    $this->datasource->order   = $this->order;
    $this->datasource->modify  = $this->modify;
    $this->datasource->delete  = $this->delete;
    $this->datasource->where   = $this->where;

    // KERESES

    $this->datasource->searchvalue = $this->searchvalue;
    
    if ( strlen( $this->searchfield ) ) {
     
      $found = false;
      foreach ( $this->fields as $key => $field ) {
        if ( 
             isset( $field['field'] ) &&
             ( $field['field'] == $this->searchfield ) &&
             isset( $field['lov'] ) 
           ) 
          $found = $key;
      }
      
      if ( $found !== false ) {

        // find current search value's LOV key in the LOV definition
        // depending on search condition
        switch ( $this->searchcondition ) {
          case 'LIKE':
            $lovkey = false;
            foreach ( $this->fields[ $found ]['lov'] as $searchkey => $searchvalue ) {
              if ( strpos( strtolower( $searchvalue ), strtolower( $this->searchvalue ) ) !== false )
                $lovkey = $searchkey;
            }
            break;
          case 'NOTLIKE':
            $lovkey = false;
            foreach ( $this->fields[ $found ]['lov'] as $searchkey => $searchvalue ) {
              if ( strpos( strtolower( $searchvalue ), strtolower( $this->searchvalue ) ) !== false )
                $lovkey = $searchkey;
            }
            break;
          default: 
            $lovkey = false;
            foreach ( $this->fields[ $found ]['lov'] as $searchkey => $searchvalue ) {
              if ( strtolower( $searchvalue ) == strtolower( $this->searchvalue ) )
                $lovkey = $searchkey;
            }
            break;
        }

        if ( $lovkey !== false ) 
          $this->datasource->searchvalue = $lovkey;
      
      }
    
    }

    $this->datasource->searchfield     = $this->searchfield;
    $this->datasource->searchcondition = $this->searchcondition;
    
    $this->datasource->setfilter();
    $this->results =
      $this->datasource->getresults( 
        $this->perpage, $this->start 
      );
    $this->allrecords = $this->datasource->countall();

  }

  // -------------------------------------------------------------------------
  function _getsearchform() {

    if ( $this->searchdisabled )
      return '';

    $options = '<option value=""></option>';

    foreach ( $this->fields as $key => $field ) {

      if ( isset( $field['field'] ) 
//           && !isset( $field['phptrigger'] ) 
           ) {
        $options .= 
          '<option ' . 
            ( isset( $field['field'] ) &&
              $this->searchfield == $field['field'] ? 'selected="selected" ' : '' ).  
            'value="' . $field['field'] . '">' . 
            $field[ 'displayname' ] . 
          '</option>';
        ;
      }
      
    }

    $searchconditions = $this->datasource->getsearchconditions();

    $conditionoptions = '<option value=""></option>';
    foreach ( $searchconditions as $key => $value )
      $conditionoptions .= '<option value="' . htmlspecialchars( $key ). '">' . $value . "</option>\n";

    return 
      "<form method=post action='" . $this->url . "#listing_of_items'>" . 
      "<input type=hidden name='action' value='search'>" .
      LISTING_SEARCH . ': ' .
      sprintf( LISTING_SEARCH_VALUES,
        sprintf( $this->searchfieldlayout, $options ),
        sprintf( $this->searchconditionlayout, 
          str_replace( 
            'value="'. $this->searchcondition . '"',
            'selected="selected" value="'. $this->searchcondition . '"',
            $conditionoptions
          )
        ) 
      ) .
      sprintf( $this->searchcontentlayout, stripslashes( $this->searchvalue ) ) .
      sprintf( $this->searchsubmitlayout,     LISTING_BUTTON_SEARCH ) .
      sprintf( $this->searchclearsavedlayout, $this->url, LISTING_BUTTON_CLEARSAVEDSEARCH ) .
      "</form>"
    ;

  }
   
}

?>