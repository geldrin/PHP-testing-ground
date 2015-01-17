<?php
namespace LDAP;

class Search implements \Iterator {
  protected $conn;
  protected $searchresult;
  protected $resultidentifier;

  protected $item;
  protected $pos;

  public function __construct( $conn, $searchresult ) {
    $this->conn = $conn;
    // the result from ldap_search
    $this->searchresult = $searchresult;
  }

  public function rewind() {
    $this->pos = 0;
    $this->resultidentifier = \ldap_first_entry( $this->conn, $this->searchresult );
  }

  public function valid() {
    return (bool) $this->resultidentifier;
  }

  public function current() {
    return \ldap_get_attributes( $this->conn, $this->resultidentifier );
  }

  public function key() {
    $guid = \ldap_get_values_len( $this->conn, $this->resultidentifier, 'objectguid' );
    if (empty($guid))
      return $this->pos;

    $binguid = reset( $guid );
    if ( !$binguid )
      return $this->pos;

    return \LDAP\LDAP::formatGUID( $binguid );
  }

  public function next() {
    $this->resultidentifier = \ldap_next_entry( $this->conn, $this->resultidentifier );
    $this->pos += 1;
  }
}
