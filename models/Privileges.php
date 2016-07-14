<?php
namespace Model;

class Privileges extends \Springboard\Model {
  public function setRoles( $roleids ) {
    $this->ensureID();
    $privilegeid = $this->id;
    $this->db->execute("
      DELETE FROM userroles_privileges
      WHERE privilegeid = '$privilegeid'
    ");

    if ( empty( $roleids ) )
      return;

    $oldtable = $this->table;
    $this->table = 'userroles_privileges';

    foreach( $roleids as $roleid ) {
      $roleid = intval( $roleid, 10 );
      if ( !$roleid )
        continue;

      $this->insertBatchCollect( array(
          'userroleid'  => $roleid,
          'privilegeid' => $privilegeid,
        )
      );
    }

    $this->flushBatchCollect();
    $this->table = $oldtable;
  }
}
