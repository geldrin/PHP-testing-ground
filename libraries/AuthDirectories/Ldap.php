<?php
namespace AuthDirectories;

class Ldap extends \AuthDirectories\Base {

  public function handle( $remoteuser ) {

    $isadmin = false;
    $filter  =
      '(&(objectClass=user)(objectCategory=person)(userPrincipalName=' .
        \LDAP\LDAP::escape( $remoteuser ) .
      '))'
    ;
    $ldap    = $this->bootstrap->getLDAP( array(
        'server'   => $this->directory['server'],
        'username' => $this->directory['user'],
        'password' => $this->directory['password'],
      )
    );
    $ret     = array(
      'user'   => array(),
      'groups' => array(),
    );

    $results = $ldap->search(
      $this->directory['ldapusertreedn'],
      $filter,
      array(
        "objectguid", "dn",
        "commonName", "sn", "givenName", "mail",
        "memberOf", "sAMAccountName", "userPrincipalName"
      )
    );

    foreach( $results as $result ) {
      $groups = isset( $result['memberOf'] )
        ? $ldap::getArray( $result['memberOf'] )
        : array()
      ;

      // ha nincs ldapgroupaccess akkor engedjuk
      if (
           empty( $groups ) or
           (
             $this->directory['ldapgroupaccess'] and
             !in_array( $this->directory['ldapgroupaccess'], $groups )
           )
         )
        continue;

      if (
           $this->directory['ldapgroupadmin'] and
           in_array( $this->directory['ldapgroupadmin'], $groups )
         )
        $isadmin = true;
      else
        $isadmin = false;

      // osszegyujtjuk a csoportokat, ez alapjan osztjuk ki a csoport hozzaferest
      $ret['groups'] = array_merge(
        $ret['groups'],
        $groups
      );

      // kotelezo, tobbi lehet hogy nincs
      $ret['user']['nickname'] = $ldap::implodePossibleArray(' ', $result['sAMAccountName'] );

      if ( isset( $result['mail'] ) )
        $ret['user']['email'] = $ldap::implodePossibleArray(' ', $result['mail'] );

      if ( isset( $result['sn'] ) )
        $ret['user']['namelast'] = $ldap::implodePossibleArray(' ', $result['sn'] );

      if ( isset( $result['givenName'] ) )
        $ret['user']['namefirst'] = $ldap::implodePossibleArray(' ', $result['givenName'] );

      break;
    }

    if ( !empty( $ret['user'] ) )
      $ret['user']['isclientadmin'] = (int) $isadmin;

    return $this->directoryuser = $ret;;
  }

}
