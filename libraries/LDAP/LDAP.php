<?php
namespace LDAP;

class LDAP {
  protected $conn;
  protected $bootstrap;
  protected $config = array(
    'server'   => '',
    'username' => '', // the RDN or DN
    'password' => '', // the associated password
    'options'  => array(),
  );

  public function __construct($bootstrap) {
    $this->bootstrap = $bootstrap;
    if (isset($bootstrap->config['ldap']) and !empty($bootstrap->config['ldap']))
      $this->config = array_merge($this->config, $bootstrap->config['ldap']);

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

  // binarisbol, ilyet:
  // 3f79048f-42cd-4c77-8426-835cd9f8a3ad
  public static function formatGUID( $binguid ) {
    $guid    = array();
    $hexguid = unpack("H*hex", $binary_guid); 
    $hex     = $hexguid["hex"];

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

}
