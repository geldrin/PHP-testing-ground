<?php

function smarty_modifier_filenameize( $filename ) {
  
  $filename = strtr(
    mb_convert_encoding( $filename, 'utf-8' ),
    Array(
      'á' => 'a', 'Á' => 'A',
      'é' => 'e', 'É' => 'E',
      'í' => 'i', 'Í' => 'I',
      'ó' => 'o', 'Ó' => 'O',
      'ö' => 'o', 'Ö' => 'O',
      'ő' => 'o', 'Ő' => 'O',
      'ú' => 'u', 'Ú' => 'U',
      'ü' => 'u', 'Ü' => 'U',
      'ű' => 'u', 'Ű' => 'U',
    )
  );
  
  $filename = preg_replace('/[^a-zA-Z0-9_\-\.]+/u', '_', trim( $filename ) );
  return $filename;
  
}
