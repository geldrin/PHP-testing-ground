<?php
function smarty_modifier_userHasAccess( $item, $organizationfield = 'organizationid', $userfield = 'userid', $extrauserfield = null ) {
  
  $bootstrap    = \Bootstrap::getInstance();
  $user         = $bootstrap->getSession('user');
  $organization = $bootstrap->getSmarty()->get_template_vars('organization');
  
  if ( !isset( $user['id'] ) )
    return false;
  
  if (
     $user['iseditor'] and $user['organizationid'] == $organization['id'] and
     @$item[ $organizationfield ] == $user['organizationid']
    )
    return true;
  
  if ( @$item[ $userfield ] == $user['id'] )
    return true;
  
  if ( $extrauserfield and @$item[ $extrauserfield ] == $user['id'] )
    return true;
  
  return false;
  
}
