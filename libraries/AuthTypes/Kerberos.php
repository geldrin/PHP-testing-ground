<?php
namespace AuthTypes;

class Kerberos extends \AuthTypes\Base {
  protected $source = 'kerberos';

  public function handle( $type, $module, $action ) {

    // a tipus teljesen ignoralni akarjuk, mintha torolve lenne
    if ( $type['disabled'] < 0 )
      return false;

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
      $e->redirecturl     = 'contents/ldapnoaccess';
      $e->redirectmessage = $l('users', 'kerberosloginfailed');
      $e->redirectparams  = array('error' => 'kerberosfailed');
      $e->info['type']    = $type;
      throw $e;
    }

    $pos    = strpos( $remoteuser, '@' );
    if ( $pos === false ) {
      $uname = $remoteuser;
      $domain = '';
    } else {
      $uname  = substr( $remoteuser, 0, $pos );
      $domain = substr( $remoteuser, $pos + 1 );
    }

    // a domain nincs a vart domain-u loginok kozott
    // ez azzal jar hogy ha nincsen letrehozva megfelelo organizations.authtypes
    // akkor a site elerheto lesz
    if ( !isset( $domains[ $domain ] ) )
      return false;

    // az adott tipushoz tartozik es az adott tipusnal hibat akarunk kiiratni
    // mert le van tiltva
    if ( $type['disabled'] > 0 ) {
      $e = new \AuthTypes\Exception("auth type disabled");
      $e->redirecturl     = 'contents/ldapnoaccess';
      $e->redirectmessage = $l('users', 'kerberosloginfailed');
      $e->redirectparams  = array('error' => 'typedisabled');
      $e->info['type']    = $type;
      $e->info['remoteuser'] = $remoteuser;
      throw $e;
    }

    if (
         $user['id'] and
         $user['source'] === $this->source and
         $user['externalid'] === $remoteuser and
         !$this->shouldReauth( $type )
       )
      return false; // false mert nem tortent bejelentkeztetes

    $user->clear(); // reseteljuk a usert a biztonsag kedveert

    // megkeressuk a usert az auth directoryban
    $directoryuser = $this->handleAuthDirectory( $remoteuser );

    // ha nincs user inserteljuk, es ha hiba van exceptionoket dobunk
    $this->handleUser( $type, $remoteuser, $directoryuser );

    // ha volt akkor updateljuk a usert
    if ( $this->directory ) {
      // ha hirtelen modosult valami, itt synceljuk
      $this->directory->syncWithUser( $user );
    }

    $this->markUser( $type );
    return true;
  }

  protected function handleAuthDirectory( $externalid ) {

    $pos    = strpos( $externalid, '@' );
    if ( $pos === false )
      $domain = '';
    else
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

  // megfelelo exceptionoket dob es insertalja a usert, elokesziti a
  // sessiont
  protected function handleUser( $type, $remoteuser, $directoryuser ) {
    $userModel = $this->bootstrap->getModel('users');

    // ujra van user['id'] mert a findAndMarkUser regisztralja, es be is lepteti
    // ha letezik
    $valid = $this->findAndMarkUser( $type, $remoteuser );

    $pos = strpos( $remoteuser, '@' );
    if ( $pos === false ) {
      $uname = $remoteuser;
      $domain = '';
    } else {
      $uname  = substr( $remoteuser, 0, $pos );
      $domain = substr( $remoteuser, $pos + 1 );
    }

    if ( $valid === null ) { // a null azt jelzi hogy nincs ilyen user

      $newuser       = array(
        'nickname'   => $uname,
        'namefirst'  => $uname,
        'externalid' => $remoteuser,
        'source'     => $this->source,
      );

      // nem talaltunk directoryt a usernek => nem talaltuk meg ldap-ban vagy
      // szimplan nem sikerult az ldap lekeres
      if ( empty( $directoryuser ) ) {
        $e = new \AuthTypes\Exception("user found but not member of ldap group");
        $e->redirecturl     = 'contents/ldapnoaccess';
        $e->redirectparams  = array('error' => 'nongroupmember');
        $e->info['type']    = $type;
        $e->info['remoteuser'] = $remoteuser;
        throw $e;
      } elseif ( $directoryuser ) // van minden, csak adatbazisban nem letezik
        $newuser = array_merge( $newuser, $directoryuser );

      $userModel->insertExternal( $newuser,
        $this->organization
      );

      $userModel->updateSessionInformation();
      $userModel->updateLastlogin(
        "({$this->source} auto-login)\n" .
        \Springboard\Debug::getRequestInformation( 0, false ),
        $this->ipaddresses
      );
      $userModel->registerForSession();

    } else {

      if ( $valid and empty( $directoryuser ) ) {
        // le lett tiltva a felhasznalo Directory-bol, de elotte valid volt
        // vagy be sincs sorolva az ldap csoportba
        $userModel->select( $user['id'] );
        $userModel->updateRow( array(
            'disabled' => $userModel::USER_DIRECTORYDISABLED,
          )
        );

        $user->clear(); // mert beleptette a findAndMarkUser
        $e = new \AuthTypes\Exception("user found but no longer member of ldap group");
        $e->redirecturl     = 'contents/ldapnoaccess';
        $e->redirectparams  = array('error' => 'accessrevoked');
        $e->info['type']    = $type;
        $e->info['user']    = $userModel->row;
        $e->info['remoteuser'] = $remoteuser;
        throw $e;

      } elseif ( !$valid and !empty( $directoryuser ) ) {
        if ( $user['disabled'] == $userModel::USER_DIRECTORYDISABLED ) {
          // vigyazunk hogy csak akkor engedjuk vissza a felhasznalot ha mi
          // tiltottuk le
          $userModel->select( $user['id'] );
          // az if ota megvaltozhatott
          if ( $userModel->row['disabled'] == $userModel::USER_DIRECTORYDISABLED ) {
            $userModel->updateRow( array(
                'disabled' => $userModel::USER_VALIDATED,
              )
            );
            $user['disabled'] = $userModel::USER_VALIDATED;
          }

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
          $e->redirectparams  = array('error' => 'banned1');
          $e->info['type']    = $type;
          $e->info['user']    = $userModel->row;
          $e->info['remoteuser'] = $remoteuser;
          $e->info['directoryuser'] = $directoryuser;
          throw $e;
        }

      } elseif ( !$valid and empty( $directoryuser ) ) {
        $user->clear(); // mert beleptette a findAndMarkUser
        $e = new \AuthTypes\Exception("user found but is manually banned and not member of ldap group");
        $e->redirecturl     = 'contents/ldapnoaccess';
        $e->redirectparams  = array('error' => 'banned2');
        $e->info['type']    = $type;
        $e->info['user']    = $userModel->row;
        $e->info['remoteuser'] = $remoteuser;
        throw $e;
      }

    }

  }
}
