<?php

function smarty_modifier_dateformat( $timestamp ) {
  return date('r', strtotime( $timestamp ) );
}
