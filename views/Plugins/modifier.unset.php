<?php

function smarty_modifier_unset( &$data ) {
  if ( !is_array( $data ) )
    throw new Exception("Array argument required!");

  $args = func_get_args();
  $len = count( $args );

  for ( $i = 1; $i < $len; $i++ ) {
    $arg = $args[ $i ];

    // osszetett tombnek az al-tombjet akarjuk unsettelni?
    // pontokkal vannak elvalasztva a szintek, ergo asd.bsd.csd mint argumentum
    // unsetteli a $data['asd']['bsd']['csd'] -t
    // ez sajnos azt is jelenti hogy ha amugy . van az index-ben akkor az ciki
    $pos = strpos( $arg, '.' );
    if ( $pos === false ) {
      unset( $data[ $arg ] );
      continue;
    }

    // az elso szintnek a tomb indexet
    $firstLevel = substr( $arg, 0, $pos );
    if ( strlen( $arg ) === $pos + 1 )
      throw new Exception("Dot must never end the index to be unset");

    // levagjuk az elso szintet es a maradekot tovabb adjuk, igy rekurzivan
    // szepen eljutunk a legbelsobb indexig
    $arg = substr( $arg, $pos + 1 );
    if ( isset( $data[ $firstLevel ] ) )
      smarty_modifier_unset( $data[ $firstLevel ], $arg );
  }

  return $data;
}
