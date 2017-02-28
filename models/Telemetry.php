<?php
namespace Model;

class Telemetry extends \Springboard\Model {
  public function getHashFromTrace( $stacktrace, $raw = false ) {
    $hash = hash_init('sha256');
    hash_update( $hash, $stacktrace['message'] );

    // nev nem mindig van bizonyos browsereken (IE, mi mas)
    if ( isset( $stacktrace['name'] ) )
      hash_update( $hash, $stacktrace['name'] );

    hash_update( $hash, $stacktrace['stack'][0]['line'] );
    hash_update( $hash, $stacktrace['stack'][0]['url'] );
    return hash_final( $hash, $raw );
  }

  public function insertTrace( $stacktrace ) {
    $hash = $this->getHashFromTrace( $stacktrace );
    // TODO hash-en unique index, ha ugyanaz akkor novelni egy szamlalot insert helyett
    // ringbuffer-kent mukodjon hogy ne kelljen torodni azzal ha tul sok a rekord
    $d = \Springboard\Debug::getInstance();

    $line =
      'JS Exception: ' . @$stacktrace['name'] . '("' . $stacktrace['message'] . "\")\n" .
      $stacktrace['url'] . "\nStack:\n" . trim( $d::varDump( $stacktrace['stack'] ) )
    ;

    $d->log(
      false,
      'telemetry_traces.txt',
      $line,
      false,
      true,
      true
    );
  }
}
