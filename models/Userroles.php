<?php
namespace Model;

class Userroles extends \Springboard\Model {
  // s-el kezdodik mert nem deklaralhato felul amugy a parent classbol
  private static $sbootstrap;
  private static $sdb;
  private static $roleNameToID = array();

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

  private static function setupDependencies() {
    if ( !self::$sbootstrap )
      self::$sbootstrap = \Bootstrap::getInstance();

    if ( !self::$sdb )
      self::$sdb = self::$sbootstrap->getAdoDB();
  }

  public static function getPrivilegesForRoleID( $roleid ) {
    if ( !$roleid )
      return array();

    self::setupDependencies();

    $cache = self::$sbootstrap->getCache(
      'roles-' . $roleid,
      60 * 60 * 24 * 7,
      true
    );

    if ( $cache->expired() ) {
      $roleid = self::$sdb->qstr( $roleid );
      $data = self::$sdb->getAssoc("
        SELECT
         pr.name,
         '1' AS value
        FROM
          userroles_privileges AS urp,
          privileges AS pr
        WHERE
          urp.userroleid = $roleid AND
          pr.id = urp.privilegeid
        ORDER BY pr.name
      ");

      $cache->put( $data );
    } else
      $data = $cache->get();

    return $data;
  }

  public static function getRoleIDByName( $name ) {
    if ( isset( self::$roleNameToID[ $name ] ) )
      return self::$roleNameToID[ $name ];

    self::setupDependencies();
    $cache = self::$sbootstrap->getCache(
      'rolenametoid-' . $name,
      60 * 60 * 24 * 7,
      true
    );

    if ( $cache->expired() ) {
      $escName = self::$sdb->qstr( $name );
      $data = self::$sdb->getOne("
        SELECT ur.id
        FROM userroles AS ur
        WHERE name = $escName
        LIMIT 1
      ");

      $cache->put( $data );
    } else
      $data = $cache->get();

    return self::$roleNameToID[ $name ] = $data;
  }
}
