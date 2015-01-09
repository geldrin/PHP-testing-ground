<?php
error_reporting(E_ALL);
ini_set('display_errors', true);

define('BASE_PATH',  realpath( dirname( __FILE__ ) . '/../../..' ) . '/' );
include_once( BASE_PATH . 'libraries/LDAP/LDAP.php');
include_once( BASE_PATH . 'libraries/LDAP/Search.php');
class Bootstrap {
  public $config = array(
    'ldap' => array(
      'server'   => 'ldap://172.18.1.7:389',
      'username' => '', // the RDN or DN
      'password' => '', // the associated password
      'options'  => array(
        LDAP_OPT_REFERRALS        => 0,
        LDAP_OPT_PROTOCOL_VERSION => 3,
      ),
    ),
  );
}

$bs  = new Bootstrap();
$ldp = new \LDAP\LDAP( $bs );

$base_tree = "DC=streamnet,DC=hu";
$filter = "(&(objectClass=user)(objectCategory=person)(userPrincipalName=psztanojev@STREAMNET.HU))";
$attr_filter = array("objectguid", "cn", "sn", "givenName", "ou", "distinguishedName", "memberOf", "sAMAccountName", "mail", "userPrincipalName");

$iter = $ldp->search( $base_tree, $filter, $attr_filter );
foreach( $iter as $guid => $value) {
  var_dump( $guid, $value );
  echo "\n\n\n\n";
}
