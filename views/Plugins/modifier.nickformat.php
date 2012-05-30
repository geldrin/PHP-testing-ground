<?php
include_once('modifier.nameformat.php');

function smarty_modifier_nickformat( $name ) {

  if ( empty( $name ) )
    return '';
  
  $smarty       = \Bootstrap::getInstance()->getSmarty();
  $organization = $smarty->get_template_vars('organization');
  
  if ( !@$organization['fullnames'] ) // TODO delete @
    return $name['nickname'];
  else
    return smarty_modifier_nameformat( $name );
  
}
