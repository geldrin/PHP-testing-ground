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
    $value = $redis->get( $token );
    if ( !$value ) {
      // meg sose lattuk vagy lejart, ujra ellenorizzuk
      $lockKey = $token . '-lock';
      $locked = $redis->setnx( $lockKey, 1 );
      if ( $locked ) {
        $redis->expire( $lockKey, 60 );

        $result = $this->checkTokenURL(
          $token, $recordingid, $livefeedid
        );

        $redis->del( $lockKey );
        return $result;
      }

      // lockolva volt, varunk amig elengedik a lockot
      $i = 1200; // 1200 * 50ms = 60sec
      while( $redis->exists( $lockKey ) and $i > 0 ) {
        $i--;
        usleep( 50000 ); // 50 milisecet alszunk
      }

      // ha tenyleg 60secig aludtunk akkor valami baj volt
      // toroljuk a lockot
      if ( $i <= 0 )
        $redis->del( $lockKey );

      // ha itt se talaljuk a tokent akkor valami nem mukodik
      $value = $redis->get( $token );
    }

    if ( !$value )
      return false;

    $expectedValue = $this->getTokenCacheValue(
      $recordingid, $livefeedid
    );

    // ha a cache tovabbra is el, es a megfelelo erteke van, tuti jo
    if ( $value === $expectedValue )
      return true;

    // minden mas esetben nem jo
    return false;
  }

  // ha konfiguralhatova tesszuk a cache key-t akkor el kell rakni
  // azt is valahova hogy mi szerepeljen benne
  private function getTokenCacheValue( $recordingid, $livefeedid ) {
    $user = $this->bootstrap->getSession('user');
    return "rec:$recordingid|live:$livefeedid|uid:" . $user['id'];
  }

  private function checkTokenURL( $token, $recordingid, $livefeeid ) {
    $user = $this->bootstrap->getSession('user');

    $curl = new \Springboard\CURL( $this->bootstrap );
    $data = $curl->get(
      $this->organization['tokenverifyurl'],
      array(
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
    $value = $this->getTokenCacheValue( $recordingid, $livefeedid );
    $redis->setex( $token, $ttlSeconds, $value );

    return true;
  }
}
