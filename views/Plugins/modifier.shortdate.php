<?php
function smarty_modifier_shortdate( $format, $startdate, $enddate = null ) {
  
  if ( !$startdate )
    return '';
  
  $smarty = \Bootstrap::getInstance()->getSmarty();
  include_once( SMARTY_DIR . 'plugins/modifier.date_format.php' );
  
  // elfogadja a 0 napos (ismeretlen datumu) esemenyeket is,
  // pl 2010-05-00 kezdo es zarodatumnal is
  
  $startdaymissing = false;
  $startformat     = $format;
  $enddaymissing   = false;
  $endformat       = $format;

  if ( preg_match('/^[0-9]{4}-[0-9]{2}-00/', $startdate ) ) {
    $startdate       = str_replace('-00 ', '-01 ', $startdate );
    $startdaymissing = true;
    $startformat     = trim( str_replace( '%e.', '', $endformat ) );
    $startformat     = trim( str_replace( '%e', '', $endformat ) );
    $startformat     = str_replace( '  ', ' ', $endformat );
  }

  if ( preg_match('/^[0-9]{4}-[0-9]{2}-00/', $enddate ) ) {
    $enddate       = str_replace('-00 ', '-01 ', $enddate );
    $enddaymissing = true;
    $endformat     = trim( str_replace( '%e.', '', $endformat ) );
    $endformat     = trim( str_replace( '%e', '', $endformat ) );
    $endformat     = str_replace( '  ', ' ', $endformat );
  }

  $startdate = smarty_modifier_date_format( strtotime( $startdate ), $startformat );
  $out = $startdate;

  if ( $enddate ) { 

    $enddate   = smarty_modifier_date_format( strtotime( $enddate ), $endformat );
    
    if ( $startdate != $enddate ) {
    
      $start = explode(' ', $startdate );
      $end   = explode(' ', $enddate );
      
      for( $i = 0; $i < count( $start ) - 1; $i++ ) {
         
        if ( $start[ $i ] == $end[ $i ] )
          unset( $end[ $i ] );
        else
          break; // bail on the first non-match, probably different year
         
      }
      
      $enddate = implode(' ', $end );
      if ( count( $end ) == 1 )
        $out = $startdate . '-' . $enddate;
      else
        $out = $startdate . ' - ' . $enddate;
      
      foreach( $start as $key => $value )
        if ( !strlen( $value ) )
          unset( $start[ $key ] );
      
      if ( ( count( $start ) <= 3 ) && !$enddaymissing )
        $out .= '.';

    }

  }

  if ( strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' )
    $out = mb_convert_encoding( $out, 'UTF-8', 'iso-8859-2' );

  if ( ( $out == $startdate ) && !$startdaymissing )
    $out .= '.';

  return $out;

}
