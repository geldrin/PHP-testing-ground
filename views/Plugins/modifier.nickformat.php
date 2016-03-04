<?php
include_once('modifier.nameformat.php');

function smarty_modifier_nickformat( $name, $org = null ) {
  if ( empty( $name ) )
    return '';

  static $organization;
  if ( !$organization and !$org ) {
    $smarty       = \Bootstrap::getInstance()->getSmarty();
    $organization = $smarty->get_template_vars('organization');
  } elseif ( !$organization and $org )
    $organization = $org;

  switch( $organization['displaynametype'] ) {
    case 'shownickname':
      return getNickname( $name, $organization );
      break;
    case 'showfullname':
      // ha nincs nev akkor nickname fallback
      if ( strlen( trim( $name['namefirst'] ) ) == 0 )
        return getNickname( $name, $organization );

      return smarty_modifier_nameformat( $name );
      break;
    default:
      throw new \Exception(
        "Organization #" . $organization['id'] .
        " with unknown displaynametype: " .
        $organization['displaynametype']
      );
      break;
  }
}

function getNickname( $name, $organization ) {
  if ( $organization['isnicknamehidden'] ) {
    if ( strlen( trim( $name['namefirst'] ) ) == 0 )
      return mb_strtolower( $name['namelast'] . '.' . $name['namefirst'] );

    return smarty_modifier_nameformat( $name );
  }

  return $name['nickname'];
}
