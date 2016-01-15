<?php
include_once('modifier.nameformat.php');

function smarty_modifier_nickformat( $name ) {
  if ( empty( $name ) )
    return '';

  static $organization;
  if ( !$organization ) {
    $smarty       = \Bootstrap::getInstance()->getSmarty();
    $organization = $smarty->get_template_vars('organization');
  }

  switch( $organization['displaynametype'] ) {
    case 'shownickname':
      return $name['nickname'];
      break;
    case 'showfullname':
      // ha nincs nev akkor nickname fallback
      if ( strlen( trim( $name['namefirst'] ) ) == 0 )
        return $name['nickname'];

      return smarty_modifier_nameformat( $name );
      break;
    case 'hidenickname':
      return mb_strtolower( $name['namelast'] . '.' . $name['namefirst'] );
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
