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

    // nincs remoteuser, ez azt jelenti hogy muszaj hogy legyen kerberos login
    // es nincsen fallback sima loginnal
    if ( !strlen( trim( $remoteuser ) ) ) {
      $e = new \AuthTypes\Exception("remote_user empty or not found");
      $e->redirecturl     = 'users/login';
      $e->redirectmessage = $l('users', 'kerberosloginfailed');
      $e->redirectparams  = array('error' => 'kerberosfailed');
      throw $e;
    }

    $pos    = strpos( $remoteuser, '@' );
    $uname  = substr( $remoteuser, 0, $pos );
    $domain = substr( $remoteuser, $pos + 1 );

    // a domain nincs a vart domain-u loginok kozott
    // ez azzal jar hogy ha nincsen letrehozva megfelelo organizations.authtypes
    // akkor a site elerheto lesz
    if ( !isset( $domains[ $domain ] ) )
      return false;

    if (
         $user['id'] and
         $user['source'] === 'kerberos' and
         $user['externalid'] === $remoteuser and
         !$this->shouldReauth( $type )
       )
      return false; // false mert nem tortent bejelentkeztetes

    $user->clear(); // reseteljuk a usert a biztonsag kedveert

    // ujra van user['id'] mert a findAndMarkUser regisztralja
    $valid = $this->findAndMarkUser( $type, $remoteuser );
    $userModel = $this->bootstrap->getModel('users');

    if ( $valid === null ) { // a null azt jelzi hogy nincs ilyen user

      $directoryuser = $this->handleAuthDirectory( $remoteuser );
      $newuser       = array(
        'nickname'   => $uname,
        'namelast'   => $uname,
        'externalid' => $remoteuser,
        'source'     => 'kerberos',
      );

      // nem talaltunk directoryt a usernek, szimplan beleptetjuk
      if ( $directoryuser and empty( $directoryuser['user'] ) ) {
        $e = new \AuthTypes\Exception("user found but not member of ldap group");
        $e->redirecturl     = 'contents/ldapnoaccess';
        throw $e;
      } elseif ( $directoryuser )
        $newuser = array_merge( $newuser, $directoryuser['user'] );

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

      if ( $this->directory )
        $this->directory->syncGroupsForUser( $user );

    } else {

      $directoryuser = $this->handleAuthDirectory( $remoteuser );
      if ( $valid and $directoryuser and empty( $directoryuser['user'] ) ) {
        // le lett tiltva a felhasznalo Directory-bol, de elotte valid volt
        $userModel->select( $user['id'] );
        $userModel->updateRow( array(
            'disabled' => $userModel::USER_DIRECTORYDISABLED,
          )
        );

        $e = new \AuthTypes\Exception("user found but no longer member of ldap group");
        $e->redirecturl     = 'contents/ldapnoaccess';
        $e->redirectparams  = array('error' => 'accessrevoked');
        throw $e;

      } elseif ( !$valid and $directoryuser and !empty( $directoryuser['user'] ) ) {
        if ( $user['disabled'] == $userModel::USER_DIRECTORYDISABLED ) {
          // vigyazunk hogy csak akkor engedjuk vissza a felhasznalot ha mi
          // tiltottuk le
          $userModel->select( $user['id'] );
          // az if ota megvaltozhatott
          if ( $userModel->row['disabled'] == $userModel::USER_DIRECTORYDISABLED )
            $userModel->updateRow( array(
                'disabled' => $userModel::USER_VALIDATED,
              )
            );

        }

        if (
             (
               isset( $userModel->row['disabled'] ) and
               $userModel->row['disabled'] != $userModel::USER_VALIDATED
             ) or
             $user['disabled'] != $userModel::USER_VALIDATED
           ) {
          $e = new \AuthTypes\Exception("user found but is manually banned");
          $e->redirecturl     = 'contents/ldapnoaccess';
          $e->redirectparams  = array('error' => 'banned');
          throw $e;
        }

      } elseif ( !$valid and $directoryuser and empty( $directoryuser['user'] ) ) {
        $e = new \AuthTypes\Exception("user found but is manually banned");
        $e->redirecturl     = 'contents/ldapnoaccess';
        $e->redirectparams  = array('error' => 'banned');
        throw $e;
      }

      if ( $this->directory ) {
        // ha hirtelen modosult valami, itt synceljuk
        $this->directory->syncWithUser( $user );
        // valid, es van directoryuser, a groupokat synceljuk ha kell
        $this->directory->syncGroupsForUser( $user );
      }

      $this->markUser($type);
    }

    return true;
  }

  protected function handleAuthDirectory( $externalid ) {

    $pos    = strpos( $externalid, '@' );
    $domain = strtolower( substr( $externalid, $pos + 1 ) );
    $found  = false;
    foreach( $this->organization['authdirectories'] as $directory ) {
      $domains = explode(',', strtolower( $directory['domains'] ) );

      if ( !in_array( $domain, $domains ) )
        continue;

      $found = true;
      break;
    }

    if ( !$found )
      return false;

    $this->directory = $this->getDirectory( $directory );
    return $this->directory->handle( $externalid );

  }

}
