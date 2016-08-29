<?php
namespace AuthDirectories;

class Ldap extends \AuthDirectories\Base {
  public function __construct( $bootstrap, $organization, $directory ) {
    parent::__construct( $bootstrap, $organization, $directory );

    // default ertekeket biztositsunk
    $this->setDirectoryKeyIfEmpty(
      'ldapuserquery',
      '(&(objectCategory=person)(objectClass=user)(sAMAccountName=%USERNAME%))'
    );
    $this->setDirectoryKeyIfEmpty(
      'ldapusernameregex',
      '/^(?<username>.+)@.*$/'
    );
  }

  private function setDirectoryKeyIfEmpty( $key, $value ) {
    if ( !isset( $this->directory[ $key ] ) or !$this->directory[ $key ] )
      $this->directory[ $key ] = $value;
  }

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
    if ( preg_match( $this->directory['ldapusernameregex'], $remoteuser, $match ) )
      $accountname = $match['username'];

    $this->l(
      "directory/ldap::handle, remoteuser: $remoteuser, after regex accountname: $accountname"
    );

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

    // sAMAccountName mindig domain nelkuli, nyerjuk ki domain nelkul
    // ha ugy jonne
    $pos = strpos( $accountname, '@');
    if ( $pos !== false )
      $user = substr( $accountname, 0, $pos );
    else
      $user = $accountname;

    $groupsModel = $this->bootstrap->getModel("groups");
    $isadmin = 0;
    $ret     = array();
    $filter  = strtr( $this->directory['ldapuserquery'], array(
        '%ACCOUNTNAME%'           => \LDAP\LDAP::escape( $accountname ),
        '%UNESCAPED_ACCOUNTNAME%' => $accountname,
        '%USERNAME%'              => \LDAP\LDAP::escape( $user ),
        '%UNESCAPED_USERNAME%'    => $user,
      )
    );

    $results = $ldap->search(
      $this->directory['ldapusertreedn'],
      $filter,
      array(
        "objectguid", "dn",
        "commonName", "sn", "givenName", "mail",
        "sAMAccountName", "userPrincipalName"
      )
    );

    if ( $results === false )
      throw new \Exception(
        "LDAP user search for $accountname failed, " .
        "org_directory was: " . var_export( $this->directory, true )
      );

    foreach( $results as $result ) { // csak egy result lesz

      $this->l(
        "directory/ldap::getAccountInfo, filter result: \n" . var_export( $result, true )
      );

      $accountname = $ldap::implodePossibleArray(' ', $result['sAMAccountName'] );
      if ( !$accountname ) { // nincs accountname? instant elhasalunk
        $this->l(
          "directory/ldap::getAccountInfo, filter nem talalt sAMAccountName-et ami kotelezo hogy legyen"
        );
        continue;
      }

      $access = $groupsModel->getDirectoryGroupsForExternalId(
        $accountname, $this->directory
      );

      // ha nincs ldapgroupaccess akkor nincs user
      if ( !$access['hasaccess'] ) {
        $this->l(
          "directory/ldap::getAccountInfo, sAMAccountName alapjan nem tagja a csoportnak"
        );
        continue;
      }

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

    $this->l(
      "directory/ldap::getAccountInfo, result: \n" . var_export( $ret, true )
    );

    return $ret;
  }

  public function handleLogin( $user, $password ) {
    $ret = array();

    $this->l(
      "directory/ldap::handleLogin, user: $user, password: $password"
    );

    if ( preg_match( $this->directory['ldapusernameregex'], $user, $match ) )
      $user = $match['username'];

    try {

      $ldap = $this->bootstrap->getLDAP( array(
          'server'   => $this->directory['server'],
          'username' => $user,
          'password' => $password,
        )
      );

      $ret = $this->getAccountInfo( $ldap, $user );
    } catch( \Exception $e ) {
      // valami rosz, vagy a user/pw vagy az ldap server
    }

    return $this->directoryuser = $ret;
  }

}
