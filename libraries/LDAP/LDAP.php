<?php
namespace LDAP;

// code from http://stackoverflow.com/questions/8560874/php-ldap-add-function-to-escape-ldap-special-characters-in-dn-syntax
// by DaveRandom
if (!function_exists('ldap_escape')) {
    define('LDAP_ESCAPE_FILTER', 0x01);
    define('LDAP_ESCAPE_DN',     0x02);

    /**
     * @param string $subject The subject string
     * @param string $ignore Set of characters to leave untouched
     * @param int $flags Any combination of LDAP_ESCAPE_* flags to indicate the
     *                   set(s) of characters to escape.
     * @return string
     */
    function ldap_escape($subject, $ignore = '', $flags = 0)
    {
        static $charMaps = array(
            LDAP_ESCAPE_FILTER => array('\\', '*', '(', ')', "\x00"),
            LDAP_ESCAPE_DN     => array('\\', ',', '=', '+', '<', '>', ';', '"', '#'),
        );

        // Pre-process the char maps on first call
        if (!isset($charMaps[0])) {
            $charMaps[0] = array();
            for ($i = 0; $i < 256; $i++) {
                $charMaps[0][chr($i)] = sprintf('\\%02x', $i);;
            }

            for ($i = 0, $l = count($charMaps[LDAP_ESCAPE_FILTER]); $i < $l; $i++) {
                $chr = $charMaps[LDAP_ESCAPE_FILTER][$i];
                unset($charMaps[LDAP_ESCAPE_FILTER][$i]);
                $charMaps[LDAP_ESCAPE_FILTER][$chr] = $charMaps[0][$chr];
            }

            for ($i = 0, $l = count($charMaps[LDAP_ESCAPE_DN]); $i < $l; $i++) {
                $chr = $charMaps[LDAP_ESCAPE_DN][$i];
                unset($charMaps[LDAP_ESCAPE_DN][$i]);
                $charMaps[LDAP_ESCAPE_DN][$chr] = $charMaps[0][$chr];
            }
        }

        // Create the base char map to escape
        $flags = (int)$flags;
        $charMap = array();
        if ($flags & LDAP_ESCAPE_FILTER) {
            $charMap += $charMaps[LDAP_ESCAPE_FILTER];
        }
        if ($flags & LDAP_ESCAPE_DN) {
            $charMap += $charMaps[LDAP_ESCAPE_DN];
        }
        if (!$charMap) {
            $charMap = $charMaps[0];
        }

        // Remove any chars to ignore from the list
        $ignore = (string)$ignore;
        for ($i = 0, $l = strlen($ignore); $i < $l; $i++) {
            unset($charMap[$ignore[$i]]);
        }

        // Do the main replacement
        $result = strtr($subject, $charMap);

        // Encode leading/trailing spaces if LDAP_ESCAPE_DN is passed
        if ($flags & LDAP_ESCAPE_DN) {
            if ($result[0] === ' ') {
                $result = '\\20' . substr($result, 1);
            }
            if ($result[strlen($result) - 1] === ' ') {
                $result = substr($result, 0, -1) . '\\20';
            }
        }

        return $result;
    }
}

class LDAP {
  protected $debug;
  protected $d;

  protected $conn;
  protected $bootstrap;
  protected $config = array(
    'server'   => '',
    'username' => '', // the RDN or DN
    'password' => '', // the associated password
    'options'  => array(
      LDAP_OPT_REFERRALS        => 0,
      LDAP_OPT_PROTOCOL_VERSION => 3,
    ),
  );

  public function __construct($bootstrap, $config) {
    $this->bootstrap = $bootstrap;
    if (!empty($config))
      $this->config = array_merge($this->config, $config);

    if ( $bootstrap->config['debugauth'] ) {
      $this->debug = true;
      $this->d = \Springboard\Debug::getInstance();
    }

    $this->init();
  }

  protected function l( $line ) {
    if ( !$this->debug )
      return;

    $line .= "\nSID: " . session_id();
    $this->d->log(
      false,
      'authdebug.txt',
      $line,
      false,
      true,
      true
    );
  }

  protected function init() {
    $this->conn = \ldap_connect( $this->config['server'] );
    if ( !$this->conn ) {
      $this->l('ldap/ldap::init ldap_connect failed with server: ' . $this->config['server'] );
      throw new \Exception("Could not connect to LDAP server");
    }

    foreach($this->config['options'] as $option => $value ) {

      $success = \ldap_set_option( $this->conn, $option, $value );
      if (!$success) {
        $this->l("ldap/ldap::init ldap_set_option failed, option: $option value: $value error: " . \ldap_error( $this->conn ));
        throw new \Exception("Could not set option $option to value $value");
      }

    }

    if (!$this->config['username'])
      $this->config['username'] = null;

    if (!$this->config['password'])
      $this->config['password'] = null;

    // squelch mert warningot dob ha rosz a username/password...
    if ( !@\ldap_bind($this->conn, $this->config['username'], $this->config['password'] ) ) {
      $this->l("ldap/ldap::init ldap_bind failed with username: {$this->config['username']} error: " . \ldap_error( $this->conn ));
      throw new \Exception("Bind failed with user " . $this->config['username'] );
    } else {
      $this->l("ldap/ldap::init ldap_bind success with username: {$this->config['username']}");
    }

  }

  public function search( $basedn, $filter, $attributes = null, $attrsonly = null, $sizelimit = null, $timelimit = null, $deref = null ) {

    // squelch mert ha kap egy invalid DN-t akkor dob WARNING-ot
    $result = @\ldap_search(
      $this->conn,
      $basedn,
      $filter,
      $attributes,
      $attrsonly,
      $sizelimit,
      $timelimit,
      $deref
    );

    $this->l("ldap/ldap::search ldap_search basedn: {$basedn} filter: {$filter}");

    if ( !$result )
      return $result;

    return new \LDAP\Search( $this->conn, $result );
  }

  // akkor kell hasznalni ha az adott dolog egy filter-ben szerepel, lasd
  // a ->search metodust
  public static function escape( $value, $isdn = false ) {
    $flags = $isdn? LDAP_ESCAPE_DN: LDAP_ESCAPE_FILTER;
    return ldap_escape( $value, null, $flags );
  }

  public static function getTimestamp( $ldaptimestamp ) {
    $ts = preg_replace(
      "/(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2}).+/",
      "$1-$2-$3 $4:$5:$6",
      $ldaptimestamp
    );

    if (!$ts or $ts === $ldaptimestamp)
      return '';

    return $ts;
  }

  // binarisbol, ilyet:
  // 3f79048f-42cd-4c77-8426-835cd9f8a3ad
  public static function formatGUID( $binguid ) {
    $guid = array();
    $hex  = bin2hex( $binguid );

    $guid[] =
      substr($hex, -26, 2) .
      substr($hex, -28, 2) .
      substr($hex, -30, 2) .
      substr($hex, -32, 2)
    ;
    $guid[] = substr($hex, -22, 2) . substr($hex, -24, 2);
    $guid[] = substr($hex, -18, 2) . substr($hex, -20, 2);
    $guid[] = substr($hex, -16, 4);
    $guid[] = substr($hex, -12, 12);

    return implode('-', $guid);
  }

  public static function getArray( $value ) {
    $pieces = array();
    $count  = $value['count'];
    for ( $i = 0; $i < $count; $i++ )
      $pieces[] = $value[ $i ];

    return $pieces;
  }

  public static function implodePossibleArray( $glue, $value ) {
    if ( !is_array( $value ) )
      return $value;

    $pieces = self::getArray( $value );
    return implode( $glue, $pieces );
  }

}
