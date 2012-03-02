<?php

function smarty_modifier_stringempty( $string ) {
  return strlen( trim( $string ) );
}
