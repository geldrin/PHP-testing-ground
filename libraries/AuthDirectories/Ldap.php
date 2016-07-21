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
    $accountname = $remoteuser;
    if ( preg_match( $this->bootstrap->config['directoryusernameregex'], $remoteuser, $match ) )
      $accountname = $match['username'];

    $ldap    = $this->bootstrap->getLDAP( array(
        'server'   => $this->directory['server'],
        'username' => $this->directory['user'],
        'password' => $this->directory['password'],
      )
    );

    return $this->directoryuser = $this->getAccountInfo(
      $ldap, $accountname
    );
  }

  private function getAccountInfo( $ldap, $accountname ) {
    $groupsModel = $this->bootstrap->getModel("groups");
    $isadmin = 0;
    $ret     = array();
    $filter  =
      '(&(objectCategory=person)(objectClass=user)(sAMAccountName=' .
        \LDAP\LDAP::escape( $accountname ) .
      '))'
    ;

    $results = $ldap->search(
      $this->directory['ldapusertreedn'],
      $filter,
      array(
        "objectguid", "dn",
        "commonName", "sn", "givenName", "mail",
        "sAMAccountName", "userPrincipalName"
      )
    );

    // TODO jogosultsag synceles
    foreach( $results as $result ) { // csak egy result lesz

      $accountname = $ldap::implodePossibleArray(' ', $result['sAMAccountName'] );
      if ( !$accountname ) // nincs accountname? instant elhasalunk
        continue;

      $access = $groupsModel->getDirectoryGroupsForExternalId(
        $accountname, $this->directory
      );

      // ha nincs ldapgroupaccess akkor nincs user
      if ( !$access['hasaccess'] )
        continue;

      $isadmin = $access['isadmin'];
      $ret['nickname'] = $accountname;

      if ( isset( $result['mail'] ) ) {
        $ret['email'] = $ldap::implodePossibleArray(' ', $result['mail'] );

        if ( !$ret['nickname'] )
          $ret['nickname'] = substr(
            $ret['email'], 0, strpos( $ret['email'], '@')
          );
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

    return $ret;
  }

  public function handleLogin( $user, $password ) {
    $ret = array();

    if ( preg_match( $this->bootstrap->config['directoryusernameregex'], $user, $match ) )
      $user = $match['username'];

    try {

      $ldap = $this->bootstrap->getLDAP( array(
          'server'   => $this->directory['server'],
          'username' => $user,
          'password' => $password,
        )
      );

      // sAMAccountName mindig domain nelkuli, nyerjuk ki domain nelkul
      // ha ugy jonne
      $pos = strpos( $user, '@');
      if ( $pos !== false )
        $user = substr( $user, 0, $pos );

      $ret = $this->getAccountInfo( $ldap, $user );
    } catch( \Exception $e ) {
      // valami rosz, vagy a user/pw vagy az ldap server
    }

    return $this->directoryuser = $ret;
  }

}
