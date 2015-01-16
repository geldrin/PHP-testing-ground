<?php
namespace LDAP;

class LDAP {
  protected $conn;
  protected $bootstrap;
  protected $config = array(
    'server'   => '',
    'username' => '', // the RDN or DN
    'password' => '', // the associated password
    'options'  => array(
      LDAP_OPT_REFERRALS        => 0,
      LDAP_OPT_PROTOCOL_VERSION => 3,
    ),
  );

  public function __construct($bootstrap, $config) {
    $this->bootstrap = $bootstrap;
    if (!empty($config))
      $this->config = array_merge($this->config, $config);

    $this->init();
  }

  protected function init() {
    $this->conn = \ldap_connect( $this->config['server'] );
    if (!$this->conn)
      throw new \Exception("Could not connect to LDAP server");

    foreach($this->config['options'] as $option => $value ) {

      $success = \ldap_set_option( $this->conn, $option, $value );
      if (!$success)
        throw new \Exception("Could not set option $option to value $value");

    }

    if (!$this->config['username'])
      $this->config['username'] = null;

    if (!$this->config['password'])
      $this->config['password'] = null;

    if ( !\ldap_bind($this->conn, $this->config['username'], $this->config['password'] ) )
      throw new \Exception("Bind failed with user " . $this->config['username'] );

  }

  public function search( $basedn, $filter, $attributes = null, $attrsonly = null, $sizelimit = null, $timelimit = null, $deref = null ) {
    $result = \ldap_search(
      $this->conn,
      $basedn,
      $filter,
      $attributes,
      $attrsonly,
      $sizelimit,
      $timelimit,
      $deref
    );

    if ( !$result )
      return $result;

    return new \LDAP\Search( $this->conn, $result );
  }

  public function escape( $value, $isdn = false ) {
    $flags = $isdn? LDAP_ESCAPE_DN: LDAP_ESCAPE_FILTER;
    return \ldap_escape( $value, null, $flags );
  }

  public static function getTimestamp( $ldaptimestamp ) {
    $ts = preg_replace(
      "/(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2}).+/",
      "$1-$2-$3 $4:$5:$6",
      $ldaptimestamp
    );

    if (!$ts or $ts === $ldaptimestamp)
      return '';

    return $ts;
  }

  // binarisbol, ilyet:
  // 3f79048f-42cd-4c77-8426-835cd9f8a3ad
  public static function formatGUID( $binguid ) {
    $guid = array();
    $hex  = bin2hex( $binguid );

    $guid[] =
      substr($hex, -26, 2) .
      substr($hex, -28, 2) .
      substr($hex, -30, 2) .
      substr($hex, -32, 2)
    ;
    $guid[] = substr($hex, -22, 2) . substr($hex, -24, 2);
    $guid[] = substr($hex, -18, 2) . substr($hex, -20, 2);
    $guid[] = substr($hex, -16, 4);
    $guid[] = substr($hex, -12, 12);

    return implode('-', $guid);
  }

  public static function getArray( $value ) {
    $pieces = array();
    $count  = $value['count'];
    for ( $i = 0; $i < $count; $i++ )
      $pieces = $value[ $i ];

    return $pieces;
  }

  public static function implodePossibleArray( $glue, $value ) {
    if ( !is_array( $value ) )
      return $value;

    $pieces = self::getArray( $value );
    return implode( $glue, $pieces );
  }

}
