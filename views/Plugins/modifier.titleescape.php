<?php

function smarty_modifier_titleescape( $title ) {
  
  $title = str_replace( '&amp;', '&', $title );
  return trim( $title );
  
}
