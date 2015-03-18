<?php
namespace Model;

class Anonymous_users extends \Springboard\Model {
  private function generateToken() {
    return md5( microtime(true) . mt_rand() );
  }

  private function setToken( $token = null ) {
    if ( !$token )
      $token = $this->generateToken();

    setcookie('anontok', $token, strtotime('+6 months'), '/');
    return $token;
  }

  private function insertAndSetNewToken() {
    $tries = 0;
    $limit = 10;
    while($tries < $limit) {
      try {

        $tries++;
        $anontok = $this->generateToken();
        $this->insert( array(
            'token'     => $anontok,
            'timestamp' => date('Y-m-d H:i:s'),
          )
        );
        $this->setToken( $anontok );
        break;

      } catch( \Exception $e ) {
        if ( $tries >= $limit )
          throw $e;
      }
    }

    return $this->row;
  }

  public function getOrInsertUserFromToken() {
    if (
         !isset( $_COOKIE['anontok'] ) or
         !preg_match('/^[0-9a-f]{32}$/', $_COOKIE['anontok'] )
       ) {
      $insert = true;
    } else {
      $anontok = $_COOKIE['anontok'];
      $insert = false;
    }

    if ( $insert )
      $row = $this->insertAndSetNewToken();
    else {

      $row = $this->getRow('token = ' . $this->db->qstr( $anontok ));
      if ( empty( $row ) )
        $row = $this->insertAndSetNewToken();
      else {
        $this->id = $row['id'];
        $this->row = $row;
      }

    }

    return $row;
  }

  public function registerForSession() {
    $user = $this->bootstrap->getSession('recordings-anonuser');
    $user->setArray( $this->row );
    return $user;
  }

}
