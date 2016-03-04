<?php

function smarty_modifier_recordinglength( $data, $prefix = '' ) {
  return max( $data[ $prefix . 'masterlength'], $data[ $prefix . 'contentmasterlength'] );
}
