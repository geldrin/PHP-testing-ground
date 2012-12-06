<?php

function hms2secs($timestamp) {
  
  $timestamp = explode(':', $timestamp );
  
  if ( count( $timestamp ) != 3 )
    return 0;
  
  $time  = 0;
  $time += $timestamp[0] * 60 * 60;
  $time += $timestamp[1] * 60;
  $time += $timestamp[2];
  
  return $time;
}

function secs2hms($i_secs) {

	$secs = abs($i_secs);
	
	$m = (int)($secs / 60);
	$s = $secs % 60;
	$h = (int)($m / 60);
	$m = $m % 60;

	$hms = sprintf("%02d", $h) . ":" . sprintf("%02d", $m) . ":" . sprintf("%02d", $s);
	return $hms;
}

?>