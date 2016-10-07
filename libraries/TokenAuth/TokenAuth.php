<?php
namespace TokenAuth;

class TokenAuth {
  private $bootstrap;
  private $organization;
  private $d;

  public function __construct( $bootstrap, $organization ) {
    $this->bootstrap = $bootstrap;
    $this->organization = $organization;
    $this->d = \Springboard\Debug::getInstance();
  }

  private function l( $msg, $force = false ) {
    if ( !$force and !$this->bootstrap->config['debugauth'] )
      return false;

    $msg .= "\nSID: " . session_id();
    $this->d->log(
      false,
      'tokendebug.txt',
      $msg,
      false,
      true,
      true
    );
  }

  public static function tokenAccessCheck( $token, $organization, $row ) {
    // ha nincs token
    if ( $token === null )
      return null;

    // ha teljesen publikus a felvetel
    if ( $row['accesstype'] === 'public' )
      return null;

    // ha a felvetel nem akar token checket
    if ( !$row['istokenrequired'] )
      return null;

    $auth = new TokenAuth( \Bootstrap::getInstance(), $organization );
    $tokenValid = $auth->tokenValid( $token );
    if ( $tokenValid === false )
      return 'tokeninvalid';
    else if ( $tokenValid )
      return true;

    // ha tokenValid null lenne akkor szimplan tovabb megyunk a checkkekkel
    // null akkor lehet ha nincs organization szinten bekapcsolva a token check
    // (nincs tokenverifyurl)
    return null;
  }

  // null viszateresi ertek azt jelzi hogy a token nem letezik, nem szabad nezni
  // hogy valid e
  public function tokenValid( $token, $recordingid = 0, $livefeedid = 0 ) {
    if ( !$this->organization['istokenverifyenabled'] or $token === null )
      return null;

    // nagyon egyszeruen csak megnezzuk hogy a token lathato karaktereket
    // tartalmazzon
    if ( !ctype_graph( $token ) ) {
      $this->l("Token contained non-printable chars, refusing");
      return false;
    }

    // ervenyes e meg a token idoben?
    $redis = $this->bootstrap->getRedis();
    $tokenKey = $this->getTokenKey( $token, $recordingid, $livefeedid );
    $value = $redis->get( $tokenKey );

    // meg sose lattuk vagy lejart, ujra ellenorizzuk
    if ( !$value ) {
      $lockKey = $tokenKey . '-lock';
      $locked = $redis->setnx( $lockKey, 1 );
      // sikerult lockolni, a mi requestunket eri a megtiszteltetes hogy
      // validalja a tokent es mindenki masnak elmondja mi tortent
      if ( $locked ) {
        // baj eseten ne legyen a lock permanens, 60 masodperc a lejarat
        $redis->expire( $lockKey, 60 );

        // ez fog redisbe irni a getTokenKey altal viszaadott kulcsra
        $result = $this->checkTokenURL(
          $token, $recordingid, $livefeedid
        );

        // mehet mindenki aki vart rank hogy checkeljuk a tokent
        $redis->del( $lockKey );

        // mi tudjuk a valaszt is, rogton vissza is terunk
        return $result;
      }

      // lockolva volt, varunk amig elengedik a lockot
      $i = 1210; // 1200 * 50ms = 60sec, kicsit tobbet alszunk just in case
      while( $redis->exists( $lockKey ) and $i > 0 ) {
        $i--;
        usleep( 50000 ); // 50 milisecet alszunk
      }

      // ha tenyleg 60secig aludtunk akkor valami baj volt
      // toroljuk a lockot, nem szabadna megtortennie, de menjunk biztosra
      if ( $i <= 0 )
        $redis->del( $lockKey );

      // itt kapjuk vissza hogy mi tortent a lock-ot tarto requesttol
      // ha itt se talaljuk a tokent akkor nem sikerult validalni a tokent
      $value = $redis->get( $tokenKey );
    }

    if ( !$value ) {
      $this->l("Token expired or invalid: $tokenKey");
      return false;
    }

    $expectedValue = $this->getTokenValue();
    if ( $value != $expectedValue ) {
      $this->l(
        "Token valid but does not have the right value, key: $tokenKey" .
        " expected: $expectedValue actual: $value"
      );
      return false;
    }

    // ha a cache tovabbra is el, es a megfelelo erteke van, tuti jo
    $this->l("Token valid and cached: $tokenKey");
    return true;
  }

  // ha tobb felvetelhez azonos tokennel sikeres valaszt kapunk, igy tamogatjuk
  // a recordingid es livefeedid parameterek kozul csak az egyiknek szabad
  // nem nullanak lennie
  private function getTokenKey( $token, $recordingid, $livefeedid ) {
    // mindent int-re hogy ha null-at adnak at akkor stringkent 0 jelenjen meg
    $recordingid = intval( $recordingid );
    $livefeedid  = intval( $livefeedid );
    return "token:$token|rec:$recordingid|live:$livefeedid";
  }

  private function getTokenValue() {
    /* TODO?
    $sid = session_id();
    if ( !$sid )
      $sid = '1';

    return $sid;
    */
    return '1';
  }

  // az egyetlen hely ami a redisbe ir a megfelelo token key-re
  private function checkTokenURL( $token, $recordingid, $livefeedid ) {
    $user = $this->bootstrap->getSession('user');

    $curl = new \Springboard\CURL( $this->bootstrap );
    $data = $curl->get(
      $this->organization['tokenverifyurl'],
      array(
        'token'       => $token,
        'recordingid' => $recordingid,
        'livefeedid'  => $livefeedid,
        'userid'      => $user['id'],
      )
    );

    $this->l(
      "Checking token: $token, httpcode: " . $curl->httpcode .
      " error: " . $curl->curlerror .
      " result: " . \Springboard\Debug::varDump( $data ),
      $curl->httpcode != 200 // forceoljuk a logolast ha non-200 a statuscode
    );

    $verifyResult = json_decode( $data, true );
    if ( empty( $verifyResult ) or !$verifyResult['success'] )
      return false;

    $ttlSeconds = intval( $verifyResult['ttlSeconds'] );

    $redis = $this->bootstrap->getRedis();
    $tokenKey = $this->getTokenKey( $token, $recordingid, $livefeedid );
    $redis->setex( $tokenKey, $ttlSeconds, $this->getTokenValue() );

    return true;
  }
}
