<?php
function smarty_modifier_userHasPrivilege() {
  $args = func_get_args();

  return call_user_func_array(
    '\Model\Userroles::userHasPrivilege',
    $args
  );
}
