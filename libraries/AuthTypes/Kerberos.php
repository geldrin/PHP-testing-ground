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

    $valid = $this->findAndMarkUser( $type, $remoteuser );
    if ( !$valid ) {
      $userModel = $this->bootstrap->getModel('users');
      $userModel->insertExternal( array(
          'externalid' => $remoteuser,
          'source'     => 'kerberos',
        ),
        $this->organization
      );
      // TODO add ldap permissions
      $userModel->updateSessionInformation();
      $userModel->updateLastlogin(
        "($source auto-login)\n" .
        \Springboard\Debug::getRequestInformation( 0, false ),
        $ipaddresses
      );
      $userModel->registerForSession();
      $this->markUser($type);
    }

    return true;
  }

}
