<?php

function smarty_modifier_filenameize( $filename ) {
  return \Springboard\Filesystem::filenameize( $filename );
}
