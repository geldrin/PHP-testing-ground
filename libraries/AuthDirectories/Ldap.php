<?php
namespace AuthDirectories;

class Ldap extends \AuthDirectories\Base {
  public function syncWithUser( $user ) {
    parent::syncWithUser( $user );

    if ( !$this->directoryuser )
      return;

    $groupsModel = $this->bootstrap->getModel("groups");
    $groupsModel->updateMembersFromExternalId(
      $user['externalid'], $user['id']
    );
  }

  public function handle( $remoteuser ) {
    $groupsModel = $this->bootstrap->getModel("groups");
    $accountname = $remoteuser;
    if ( preg_match( $this->bootstrap->config['directoryusernameregex'], $remoteuser, $match ) )
      $accountname = $match['username'];

    $isadmin = 0;
    $filter  =
      '(&(objectClass=user)(objectCategory=person)(sAMAccountName=' .
        \LDAP\LDAP::escape( $accountname ) .
      '))'
    ;
    $ldap    = $this->bootstrap->getLDAP( array(
        'server'   => $this->directory['server'],
        'username' => $this->directory['user'],
        'password' => $this->directory['password'],
      )
    );
    $ret     = array();

    $results = $ldap->search(
      $this->directory['ldapusertreedn'],
      $filter,
      array(
        "objectguid", "dn",
        "commonName", "sn", "givenName", "mail",
        "sAMAccountName", "userPrincipalName"
      )
    );
    $access = $groupsModel->getDirectoryGroupsForExternalId(
      $remoteuser, $this->directory
    );

    foreach( $results as $result ) { // csak egy result lesz

      // ha nincs ldapgroupaccess akkor nincs user
      if ( !$access['hasaccess'] )
        continue;

      $isadmin = $access['isadmin'];

      // lehet hogy nincs, megprobaljuk tolteni abbol ami van
      $ret['nickname'] = $ldap::implodePossibleArray(' ', $result['sAMAccountName'] );

      if ( isset( $result['mail'] ) ) {
        $ret['email'] = $ldap::implodePossibleArray(' ', $result['mail'] );

        if ( !$ret['nickname'] )
          $ret['nickname'] = substr( $ret['email'], 0, strpos( $ret['email'], '@') );
      }

      if ( isset( $result['givenName'] ) ) {
        $ret['namefirst'] = $ldap::implodePossibleArray(' ', $result['givenName'] );
        if ( !$ret['nickname'] )
          $ret['nickname'] = $ret['namefirst'];
      }

      if ( isset( $result['sn'] ) ) {
        $ret['namelast'] = $ldap::implodePossibleArray(' ', $result['sn'] );
        if ( !$ret['nickname'] )
          $ret['nickname'] = $ret['namelast'];
      }

      break;
    }

    if ( !empty( $ret ) ) {
      if ( $isadmin )
        $ret['isuploader'] = 1;

      $ret['isclientadmin'] = (int) $isadmin;
    }

    return $this->directoryuser = $ret;
  }

}
