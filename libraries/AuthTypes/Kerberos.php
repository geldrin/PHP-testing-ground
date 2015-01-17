<?php
namespace AuthTypes;

class Kerberos extends \AuthTypes\Base {

  public function handle($type) {

    // az organization egyatalan var kerberos logint?
    $domains = array();
    $doms = explode(',', $type['domains'] );
    foreach($doms as $value)
      $domains[ trim($value) ] = true;

    $user = $this->bootstrap->getSession('user');
    $l = $this->bootstrap->getLocalization();

    // a kerberos login ebbe a ket mezobe rakja a login nevet username@DOMAIN alakban
    if ( isset( $_SERVER["REDIRECT_REMOTE_USER"] ) )
      $remoteuser = $_SERVER["REDIRECT_REMOTE_USER"];
    elseif ( isset( $_SERVER["REMOTE_USER"] ) )
      $remoteuser = $_SERVER["REMOTE_USER"];
    else
      $remoteuser = '';

    // nincs remoteuser
    if ( !strlen( trim( $remoteuser ) ) ) {
      $e = new \AuthTypes\Exception("remote_user empty or not found");
      $e->redirecturl     = 'users/login';
      $e->redirectmessage = $l('users', 'kerberosloginfailed');
      $e->redirectparams  = array('error' => 'kerberosfailed');
      throw $e;
    }

    $pos    = strpos( $remoteuser, '@' );
    $domain = substr( $remoteuser, $pos + 1 );

    // a domain nincs a vart domain-u loginok kozott
    if ( !isset( $domains[ $domain ] ) ) {
      $e = new \AuthTypes\Exception("remote_user's domain was unexpected");
      $e->redirecturl     = 'users/login';
      $e->redirectmessage = $l('users', 'kerberosloginfailed');
      $e->redirectparams  = array('error' => 'domain');
      throw $e;
    }

    // we notice changes via the remoteuser changing undearneath us
    // TODO check for a timeout to check if LDAP permissions changed?
    if (
         $user['id'] and
         $user['source'] === 'kerberos' and
         $user['externalid'] === $remoteuser
       )
      return false; // false mert nem tortent bejelentkeztetes

    $user->clear(); // reseteljuk a usert a biztonsag kedveert

    // ujra van user['id'] mert a findAndMarkUser regisztralja
    $valid = $this->findAndMarkUser( $type, $remoteuser );
    $userModel = $this->bootstrap->getModel('users');

    if ( $valid === null ) { // a null azt jelzi hogy nincs ilyen user

      $ldapuser = $this->getLDAPUser( $remoteuser );
      if ( empty( $ldapuser['user'] ) ) {
        $e = new \AuthTypes\Exception("user found but not member of ldap group");
        $e->redirecturl     = 'contents/ldapnoaccess';
        throw $e;
      }

      $newuser = array_merge( array(
          'externalid' => $remoteuser,
          'source'     => 'kerberos',
        ),
        $ldapuser['user']
      );

      $userModel->insertExternal( $newuser,
        $this->organization
      );

      $userModel->updateSessionInformation();
      $userModel->updateLastlogin(
        "(Kerberos auto-login)\n" .
        \Springboard\Debug::getRequestInformation( 0, false ),
        $this->ipaddresses
      );
      $userModel->registerForSession();
      $this->markUser($type);

      $this->syncLdapGroupsForUser( $user, $ldapuser['groups'] );

    } else {

      $ldapuser = $this->getLDAPUser( $remoteuser );
      if ( $valid and empty( $ldapuser['user'] ) ) {
        // le lett tiltva a felhasznalo LDAP-bol, de elotte valid volt
        $userModel->select( $user['id'] );
        $userModel->updateRow( array(
            'disabled' => $userModel::USER_DIRECTORYDISABLED,
          )
        );

        $e = new \AuthTypes\Exception("user found but no longer member of ldap group");
        $e->redirecturl     = 'contents/ldapnoaccess';
        $e->redirectparams  = array('error' => 'accessrevoked');
        throw $e;

      } elseif ( !$valid and !empty( $ldapuser['user'] ) and $user['disabled'] == $userModel::USER_DIRECTORYDISABLED ) {
        // vigyazunk hogy csak akkor engedjuk vissza a felhasznalot ha mi
        // tiltottuk le
        $userModel->select( $user['id'] );
        // az if ota megvaltozhatott
        if ( $userModel->row['disabled'] == $userModel::USER_DIRECTORYDISABLED ) {
          $userModel->updateRow( array(
              'disabled' => $userModel::USER_VALIDATED,
            )
          );
        } elseif ( $userModel->row['disabled'] != $userModel::USER_VALIDATED ) {
          $e = new \AuthTypes\Exception("user found but is manually banned");
          $e->redirecturl     = 'contents/ldapnoaccess';
          $e->redirectparams  = array('error' => 'banned');
          throw $e;
        }

      } elseif ( !$valid and empty( $ldapuser['user'] ) ) {
        $e = new \AuthTypes\Exception("user found but is manually banned");
        $e->redirecturl     = 'contents/ldapnoaccess';
        $e->redirectparams  = array('error' => 'banned');
        throw $e;
      }

      // ha hirtelen modosult valami, itt synceljuk
      $this->syncLdapWithUser( $user, $ldapuser['user'] );
      // valid, es van ldapuser, a groupokat synceljuk ha kell
      $this->syncLdapGroupsForUser( $user, $ldapuser['groups'] );
      $this->markUser($type);
    }

    return true;
  }

  public function syncLdapWithUser( $user, $ldapuser ) {
    $userModel = $this->bootstrap->getModel('users');
    $userModel->id = $user['id'];
    $userModel->updateRow( $ldapuser );
    $userModel->registerForSession();
  }

  private function syncLdapGroupsForUser( $user, $groups ) {
    $groupModel = $this->bootstrap->getModel('groups');
    $userModel  = $this->bootstrap->getModel('users');
    $userModel->id = $user['id'];

    $existinggroups  = $userModel->getAssocDirectoryGroupIDs( $this->organization['id'] );
    $directorygroups = $groupModel->getDirectoryGroups( $this->organization['id'] );
    $neededgroups    = array();
    $lookuptable     = array();
    $groupids        = array(); // megjegyezzuk a csoportokat a torleshez
    $needupdate      = false; // kell e updatelni a felhasznalo csoportjait
    $needcleargroups = (bool) count( $existinggroups ); // ha nincs, akkor nem torlunk feleslegesen

    foreach( $groups as $group )
      $lookuptable[ $group ] = true;

    unset( $groups );

    foreach( $directorygroups as $group ) {
      $groupids[] = $group['id'];

      if ( isset( $lookuptable[ $group['dn'] ] ) )
        $neededgroups[] = $group['id'];
    }

    // megallaptjuk hogy kell e a db-hez nyulni, ha elter a csoportok szama
    // akkor biztos hogy kell
    if ( count( $neededgroups ) != count( $existinggroups ) )
      $needupdate = true;

    if ( !$needupdate ) {

      foreach( $neededgroups as $id ) {

        // valamilyen csoportnak nem tagja a felhasznalo
        if ( !isset( $existinggroups[ $id ] ) ) {
          $needupdate = true;
          break;
        } else
          // toroljuk a tombbol azokat a csoportokat amiknek a tagja
          // kesobb ellenorizzuk hogy a tombben maradt e valami mert az azt
          // jelenti hogy olyan csoportnak tagja aminek nem kellene hogy tagja
          // legyen
          unset( $existinggroups[ $id ] );

      }

      if ( !$needupdate and count( $existinggroups ) != 0 )
        $needupdate = true;

    }

    if ( !$needupdate )
      return;

    if ( $needcleargroups ) // vannak csoportjai, toroljuk oket
      $userModel->clearFromGroups( $groupids );

    $userModel->addGroups( $neededgroups );

  }

  private function getLDAPUser( $remoteuser ) {

    $ret = array(
      'user'   => array(),
      'groups' => array(),
    );

    $isadmin = false;
    $filter  =
      '(&(objectClass=user)(objectCategory=person)(userPrincipalName=' .
        \LDAP\LDAP::escape( $remoteuser ) .
      '))'
    ;

    foreach( $this->organization['authdirectories'] as $directory ) {

      if ( $directory['type'] !== 'ldap' )
        continue;

      $ldap = $this->bootstrap->getLDAP( array(
          'server'   => $directory['server'],
          'username' => $directory['user'],
          'password' => $directory['password'],
        )
      );

      $results = $ldap->search(
        $directory['ldapusertreedn'],
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

        if (
             empty( $groups ) or
             !in_array( $directory['ldapgroupaccess'], $groups )
           )
          continue;

        $isadmin = in_array( $directory['ldapgroupadmin'], $groups );

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
    }

    if ( !empty( $ret['user'] ) )
      $ret['user']['isclientadmin'] = (int) $isadmin;

    return $ret;
  }

}
