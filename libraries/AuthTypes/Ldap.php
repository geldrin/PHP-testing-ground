<?php
namespace AuthTypes;

class Ldap extends \AuthTypes\Kerberos {
  protected $source = 'ldap';

  public function handleForm( $type, $form ) {
    if ( $type['disabled'] < 0 )
      return null;

    $sessionUser = $this->bootstrap->getSession('user');
    $l = $this->bootstrap->getLocalization();

    $values = $form->getElementValues(0);
    $username = $values['email'];
    $password = $values['password'];

    if (
         $sessionUser['id'] and
         $sessionUser['source'] === $this->source and
         $sessionUser['externalid'] === $username and
         !$this->shouldReauth( $type )
       )
      return false; // false mert nem tortent bejelentkeztetes

    $directoryUser = $this->handleDirectoryLogin( $username, $password );
    if ( empty( $directoryUser ) ) // nem tudtunk belepni
      return false;

    $this->handleUser( $type, $username, $directoryUser );

    if ( $this->directory ) {
      // ha hirtelen modosult valami, itt synceljuk
      $this->directory->syncWithUser( $sessionUser );
    }

    $this->markUser( $type );
    return true;
  }

  private function handleDirectoryLogin( $user, $password ) {

    $pos    = strpos( $user, '@' );
    if ( $pos === false )
      $domain = '';
    else
      $domain = strtolower( substr( $user, $pos + 1 ) );

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
    return $this->directory->handleLogin( $user, $password );

  }

}
