<?php
namespace Model;

class Userroles extends \Springboard\Model {
  public function setPrivileges( $privilegeids ) {
    $this->ensureID();
    $roleid = $this->id;
    $this->db->execute("
      DELETE FROM userroles_privileges
      WHERE userroleid = '$roleid'
    ");

    if ( empty( $privilegeids ) )
      return;

    $oldtable = $this->table;
    $this->table = 'userroles_privileges';

    foreach( $privilegeids as $privilegeid ) {
      $privilegeid = intval( $privilegeid, 10 );
      if ( !$privilegeid )
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
