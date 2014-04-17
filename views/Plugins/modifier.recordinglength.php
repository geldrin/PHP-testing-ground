<?php

function smarty_modifier_recordinglength( $data ) {
  return max( $data['masterlength'], $data['contentmasterlength'] );
}
