<?php

// pager 2.61
// - fixed: 'next'/'last' _geturl -> getPagerLink()
// - centering pager: current page is centered if possible

// pager 2.6
// - $this->getPagerLink function
// - streamlined url generator
// - modified to fit <ul><li><a href="#">1</a></li></ul> formats
// - using xfn microformat rel= attribute in links: start, prev, next, end

// pager 2.51
// - lowercase fix

// pager 2.5
// - added getvars
// - firstlinkpars, prevlinkpars, nextlinkpars, lastlinkpars
// - tags in lowercase

// pager 2.4
// - including nofirst, nolast, etc
// - configurable container
// - includes linkformat and noparameters

class pager {
  
  var $all;
  var $start;
  var $perpage;
  var $script;
  var $pagestoshow = 10;

  var $pass = Array();
  var $pre       = '';
  var $numberpre = '';

  var $container = 
      '<table align="center" class="pager" cellpadding="0" border="0" cellspacing="0"><tr><td>%s</td></tr></table>';

  var $itemlayout        = '%s';
  var $itemlayoutcurrent = '<span %1$s>%2$s</span>'; // first %s: $currentpageparameters
  var $controlimploder = '</td><td>';
  var $currentpageparameters;
  var $linkparameter;

  var $singlepage   = true;

  var $nofirst    = "|&lt;";
  var $noprevious = '&lt;';
  var $first      = "|&lt;";
  var $previous   = '&lt;';

  var $cluster    = '...';
  var $next       = '&gt;';
  var $last       = '&gt;|';

  var $nonext     = '&gt;';
  var $nolast     = '&gt;|';

  var $divider    = ' | ';

  // display perpage select or not
  var $perpageselector = true;

  // options of the perpage select: eg. Array( 10, 20, 30, 50 )
  var $perpageoptions = Array();
  var $perpageformmethod      = 'post';
  var $perpagecontainer       =
    '<form action="%1$s" method="%3$s">
      %2$s
    </form>'
  ;
  var $perpageselectcontainer = '%s items/page';
  var $perpageselect =
    '<select name="perpage" onchange="this.form.submit();">%s</select>';

  var $prevlinkparameters;
  var $nextlinkparameters;


  var $bookmark   = '#listing_of_items';
  var $noparameters = 0;
  var $linkformat = '%sstart=%s';

  // --------------------------------------------------------------------------
  function pager( 
    $script, $all = 0, $start = 0 , $perpage = 10, 
    $linkparameters        = '',
    $currentpageparameters = 'class="currentpage"',
    $firstlinkparameters = null, 
    $prevlinkparameters = null,
    $nextlinkparameters = null,
    $lastlinkparameters = null
  ) {

    $this->script  = $script;
    $this->all     = $all;
    $this->start   = $start;
    $this->perpage = $perpage;
    $this->currentpageparameters = $currentpageparameters;
    
    $this->linkparameters        = $linkparameters;

    $this->firstlinkparameters   = $firstlinkparameters;
    $this->prevlinkparameters    = $prevlinkparameters;
    $this->nextlinkparameters    = $nextlinkparameters;
    $this->lastlinkparameters    = $lastlinkparameters;

    if ( !$this->firstlinkparameters )
      $this->firstlinkparameters = $linkparameters;

    if ( !$this->prevlinkparameters )
      $this->prevlinkparameters = $linkparameters;

    if ( !$this->nextlinkparameters )
      $this->nextlinkparameters = $linkparameters;

    if ( !$this->lastlinkparameters )
      $this->lastlinkparameters = $linkparameters;

  }

  // --------------------------------------------------------------------------
  function getVars() {

    return $this->gethtml( 1 );

  }

  // --------------------------------------------------------------------------
  function getPerPageForm() {

    if ( !$this->perpageselector )
      return '';

    $options = '';
    foreach ( $this->perpageoptions as $option ) {
      $selected = $this->perpage == $option ? 'selected="selected" ' : '';
      $option   = htmlspecialchars( $option, ENT_QUOTES, 'UTF-8', true );
      $options .=
        '<option ' . $selected  . 'value="' . $option . '">' . $option . '</option>';
    }

    $hiddenvars      = '';
    $hiddenvarformat = '<input type="hidden" name="%s" value="%s"/>';
    foreach( $this->pass as $name => $value ) {
      $name = htmlspecialchars( $name, ENT_QUOTES, 'UTF-8', true );

      if ( is_array( $value ) ) {

        foreach( $value as $k => $v ) {

          if ( $v !== null and !is_scalar( $v ) )
            throw new Exception("Non-scalar value found for variable $name [ $k ]");

          $k           = htmlspecialchars( $k, ENT_QUOTES, 'UTF-8', true );
          $v           = htmlspecialchars( $v, ENT_QUOTES, 'UTF-8', true );
          $newname     = $name . '[' . $k . ']';
          $hiddenvars .= sprintf( $hiddenvarformat, $newname, $v );
        }

      } elseif ( $value === null or is_scalar( $value ) ) {
        $value       = htmlspecialchars( $value, ENT_QUOTES, 'UTF-8', true );
        $hiddenvars .= sprintf( $hiddenvarformat, $name, $value );
      } else
        throw new Exception("Non-array and non-scalar type for variable to pass");
    }

    return $this->itemLayout(
      sprintf(
        $this->perpagecontainer,
        $this->getPagerLink( 0, true ),
        sprintf( $this->perpageselectcontainer,
          $hiddenvars . sprintf( $this->perpageselect, $options )
        ),
        $this->perpageformmethod
      )
    );

  }

  // --------------------------------------------------------------------------
  function gethtml( $vars = 0 ) {

    $nrofpages = ceil( $this->all / $this->perpage );

    if ( ( $nrofpages == 1 ) && !$this->singlepage )
      return '';

    // cluster window variables

    $thispage         = $this->start / $this->perpage;
    $tens_point       = $thispage % 10;

    $clusterstart = $thispage - $tens_point;

    if (
         ( ( $this->all / $this->perpage ) > ( $clusterstart + $this->pagestoshow ) ) &&
         ( $thispage > ( $this->pagestoshow / 2 ) )
       ) {
      $clusterstart = ceil( $thispage - ( $this->pagestoshow / 2 ) );
    }

    $clusterstop  = $clusterstart + $this->pagestoshow;

    if ( $clusterstop > $nrofpages ) {
      $clusterstart = $nrofpages - $this->pagestoshow;
      $clusterstop  = $nrofpages;
    }

    if ( $clusterstart < 0 )
      $clusterstart = 0;

    // compiling controls

    $controls = Array();

    if ( strlen( $this->pre ) )  
      $controls['pre'] = $this->itemLayout( $this->pre );

    if ( strlen( $this->first) ) {
      if ( $this->start > 0 )
        $controls['first'] = $this->itemLayout(
          '<a rel="start" '.$this->firstlinkparameters.' id="pgr0" href="' . $this->getPagerLink( 0 ) . '">' . $this->first . '</a>'
        );
      else
        $controls['first'] = $this->itemLayout( $this->nofirst );
    }

    if ( ( $this->start - $this->perpage ) >= 0 ) 
      $controls['prev'] = $this->itemLayout(
        '<a rel="prev" ' . $this->prevlinkparameters . ' id="pgr' . ( $this->start - $this->perpage ) . '" href="' . $this->getPagerLink( $this->start - $this->perpage ) . '">' . $this->previous . '</a>', false, 'prev'
      );
    else
      $controls['prev'] = $this->itemLayout( $this->noprevious );

    $controls['pager'] = Array();

    // previous cluster window
    if ( $clusterstart > 0 ) {

      $prevcluster = round( $thispage ) - $this->pagestoshow;
      if ( $prevcluster < 0 )
        $prevcluster = 0;

      $controls['pager'][] = $this->itemLayout(
        '<a '.$this->linkparameters.' id="pgr' . $prevcluster . '" href="' . $this->getPagerLink( $prevcluster ) . '">' . $this->cluster . '</a>'
      );
    }

    if ( strlen( $this->numberpre ) ) 
      $controls['pager'][] = $this->itemLayout( $this->numberpre );

    for (
      $i = $clusterstart;
      $i < $clusterstop;
      $i++ ) {

      if ( $this->start == $i * $this->perpage )
        $controls['pager'][] = $this->itemLayout( $i + 1, true );
      else
        $controls['pager'][] = $this->itemLayout(
          '<a '.$this->linkparameters.' id="pgr' . ( $i * $this->perpage ) . '" href="' . $this->getPagerLink( $i * $this->perpage ) . '">' . ( $i + 1 ) . '</a>'
        );

      if ( strlen( $this->divider ) && ( $i < ( $clusterstop * $this->pagestoshow - 1 ) ) ) 
        $controls['pager'][] = $this->divider;

    }

    if ( $clusterstop < $nrofpages ) {
 
      $nextcluster = $thispage + $this->pagestoshow;
      if ( $nextcluster >= $nrofpages )
        $nextcluster = $nrofpages - round( $this->pagestoshow / 2 );

      // still not the last cluster
      $controls['pager'][] = $this->itemLayout(
        '<a '.$this->linkparameters.' id="pgr' . ( $nextcluster * $this->perpage ) . '" href="' . 
        $this->getPagerLink(
          $nextcluster * $this->perpage
        ) .
        '">' . $this->cluster . '</a>'
      );
    }

    if ( $this->perpageselector )
      $controls['perpage'] = $this->getPerPageForm();

    if ( ( $this->start + $this->perpage ) < $this->all ) {
      $controls['next'] = $this->itemLayout(
        '<a rel="next" ' . $this->nextlinkparameters . ' id="pgr' . ( $this->start + $this->perpage ). '" href="' . $this->getPagerLink( $this->start + $this->perpage ) . '">' . $this->next . '</a>'
      );
      if ( strlen( $this->last ) )
        $controls['last'] = $this->itemLayout(
          '<a rel="end" ' . $this->lastlinkparameters . ' id="pgr' . (  ( $nrofpages - 1 ) * $this->perpage  ). '" href="' . $this->getPagerLink( ( $nrofpages - 1 ) * $this->perpage ) . '">' . $this->last . '</a>'
        );
    }
    else {
      $controls['next'] = $this->itemLayout( $this->nonext );
      if ( strlen( $this->last ) )
        $controls['last'] = $this->itemLayout( $this->nolast );
    }

    $controls['pager'] = implode( $this->controlimploder, $controls['pager'] );

    if ( $vars )
      return $controls;
    else
      return sprintf( 
        $this->container, 
        implode( $this->controlimploder, $controls ) 
      );

  }

  // --------------------------------------------------------------------------
  function itemLayout( $text, $currentpage = false ) {

    if ( $currentpage )
      return sprintf(
        $this->itemlayoutcurrent, $this->currentpageparameters, $text
      );
    else
      if ( strlen( trim( $text ) ) )
        return sprintf( $this->itemlayout, $text );

    return '';

  }

  // --------------------------------------------------------------------------
  function pass( $variable, $value ) {
    if ( !is_array( $value ) and $value !== null and !is_scalar( $value ) )
      throw new Exception("Non-array and non-scalar type as value is unsupported");

    $this->pass[ $variable ] = $value;
  }

  // --------------------------------------------------------------------------
  function _geturl() {

    $parameters = http_build_query( $this->pass );
    $parameters = htmlspecialchars( $parameters, ENT_QUOTES, 'UTF-8', true );

    if ( !strlen( $parameters ) || $this->noparameters )
      return $this->script;
    else {
      if ( strpos( $this->script, '?' ) )
        return $this->script . '&amp;' . $parameters;
      else
        return $this->script . '?' . $parameters;
    }

  }

  // --------------------------------------------------------------------------
  function getPagerLink( $start, $skipperpage = false ) {

    $url        = $this->_geturl();
    $linkformat = $this->linkformat;

    if ( strpos( $url, '?' ) !== false )
      $linkformat = str_replace( '?start=', '&amp;start=', $linkformat );

    $link = sprintf( $linkformat, $url, $start );

    if ( !$skipperpage and $this->perpageselector ) {
      if ( strpos( $link, '?' ) !== false )
        $link .= '&amp;perpage=' . $this->perpage;
      else
        $link .= '?perpage=' . $this->perpage;
    }

    return $link . $this->bookmark;

  }

}

?>