#!/usr/bin/env php
<?php
define('BASE_PATH',  realpath( dirname( __FILE__ ) ) . '/' );
set_time_limit(0);

if ( isset( $_SERVER['APPLICATION_ENV'] ) and $_SERVER['APPLICATION_ENV'] == 'developer' )
  define('PRODUCTION', false );
else
  define('PRODUCTION', true );

include_once( BASE_PATH . 'libraries/Springboard/Application/Cli.php');

$app    = new Springboard\Application\Cli( BASE_PATH, false );
new UpdateDB( $app, $argv );
echo "Done!\n";

class UpdateDB {
  private $application;
  private $bootstrap;
  private $current = array();
  private $history = array();
  private $toUpdate = array();
  private $toInsert = array();
  private $shortnameToID = array();

  public function __construct( $app, $argv ) {
    $this->application = $app;
    $this->bootstrap = $app->bootstrap;

    $debug = false;
    foreach( $argv as $arg ) {
      if ( stripos( $arg, 'debug' ) !== false ) {
        $debug = true;
        break;
      }
    }

    $this->debug = $this->bootstrap->debug = $debug;

    // egyelore hardcoded
    $table = 'help_contents';
    // az eppeni defaultvalues-ban talalhato ertekeket hasheljuk
    // hogy tudjuk ha nincs mit valtoztatni
    $this->setupCurrentValues( $table );

    // mivel nem tudjuk hogy eppen milyen allapotban van az adatbazis
    // (lehet hogy reg volt updatelve, es regiek az ertekek) ezert
    // git-bol lekerdezzuk a regi verziokat, es hasheljuk oket
    $this->setupHistory( $table );

    // lekerdezzuk az adatbazisbol az ertekeket es hasheljuk oket
    // ha az a hash szerepel a history-ban talalhato hashek kozott
    // akkor az a sor updatelheto a jelenlegi ertekekkel
    // mert tudjuk hogy nem irjuk felul a user sajat valtozasait
    // itt ellenorizzuk hogy a defaultvalues ertekek ugyanazok e
    // mint amik az adatbazisban vannak
    $this->initWhatToUpdate( $table );

    // az eppeni defaultvaluesban talalhato ertekeket megjeloljuk
    // frissitesre az elozo lepesek szerint
    $this->initUpdateValues( $table );

    // konkretan updateljuk az adatbazist
    $this->updateValues( $table );

    // megnezzuk hogy van e uj rekord az eppeni defaultvalues-ban
    // ha van akkor itt megjeloljuk insertalasra
    $this->initWhatToInsert( $table );

    // konkretan insertelunk
    $this->insertValues( $table );
  }

  private function setupCurrentValues( $table ) {
    $file = "data/defaultvalues/{$table}.php";
    $data = include( $file );
    foreach( $data as $row ) {
      $helpkey = $row['shortname'];
      $this->current[ $helpkey ] = array();
      foreach( $row as $field => $value )
        $this->current[ $helpkey ][ $field ] = md5( $value );
    }
  }

  private function setupHistory( $table ) {
    // git-bol lekerjuk a file elozo verzioit, max maxversion-nyit
    // megnezzuk hogy valid php filok-e, ha igen akkor vesszuk az ertekek
    // hash-jet (md5), majd ezt elrakjuk egy hash-table-be ahol
    // a kulcs az md5, az ertek pedig true
    if ( !isset( $this->history[ $table ] ) )
      $this->history[ $table ] = array();

    if ( !chdir( BASE_PATH ) )
      throw new \Exception('Could not change directory to ' . BASE_PATH );

    $tmpfile = tempnam( sys_get_temp_dir(), 'UPDATEDB' );
    $file = "data/defaultvalues/{$table}.php";
    $cmd = 'git log --follow --pretty="tformat:%H" -- ' . $file;
    if ( !$this->exec( $cmd, $output ) )
      throw new \Exception("Could not get git log for file $file");

    foreach( $output as $ix => $commit ) {
      // az adott revizioban hogy nezett ki a file
      $cmd = "git show {$commit}:{$file} > {$tmpfile}";
      if ( !$this->exec( $cmd ) )
        throw new \Exception("Could not checkout commit $commit for file $file");

      // syntax check
      $cmd = 'php -l ' . $tmpfile . ' 2>&1';
      if ( !$this->exec( $cmd ) ) {
        if ( $this->debug )
          echo $cmd, " failed, skipping commit\n";
        continue;
      }

      $this->handleHistoryFile( $tmpfile, $table );
    }

    unlink( $tmpfile );
  }

  private function handleHistoryFile( $file, $table ) {
    $data = include( $file );
    if ( !is_array( $data ) )
      throw new \Exception("$file did not return an array");

    if ( empty( $data ) )
      throw new \Exception("$file returned an empty array");

    foreach( $data as $row ) {
      $helpkey = $row['shortname'];
      if ( !isset( $this->history[ $table ][ $helpkey ] ) )
        $this->history[ $table ][ $helpkey ] = array();

      // reference, for less typing
      $history = &$this->history[ $table ][ $helpkey ];
      foreach( $row as $field => $value ) {
        switch( $field ) {
          case 'title':
          case 'titleen':
          case 'body':
          case 'bodyen':
            if ( !isset( $history[ $field ] ) )
              $history[ $field ] = array();

            $key = md5( $value );
            $history[ $field ][ $key ] = true;
            break;
        }
      }
    }
  }

  private function exec( $cmd, &$output = null ) {
    if ( $output === null )
      $output = array();

    if ( $this->debug )
      echo "executing: ", $cmd, "\n";

    exec( $cmd, $output, $exitcode );
    if ( $this->debug )
      echo
        "exit code: ", var_export( $exitcode, true ),
        "\noutput was: ", implode( "\n", $output ), "\n"
      ;

    return $exitcode == 0;
  }

  private function isInHistory( $table, $helpkey, $field, $hash ) {
    return isset( $this->history[ $table ][ $helpkey ][ $field ][ $hash ] );
  }

  private function initWhatToUpdate( $table ) {
    if ( !isset( $this->toUpdate[ $table ] ) )
      $this->toUpdate[ $table ] = array();

    $db = $this->bootstrap->getAdodb();
    $rs = $db->query("
      SELECT
        hc.id,
        hc.shortname,
        sthu.value AS title,
        sten.value AS titleen,
        sbhu.value AS body,
        sben.value AS bodyen
      FROM help_contents AS hc
      LEFT JOIN strings AS sthu ON(
        sthu.language = 'hu' AND
        sthu.translationof = hc.title_stringid
      )
      LEFT JOIN strings AS sten ON(
        sten.language = 'en' AND
        sten.translationof = hc.title_stringid
      )
      LEFT JOIN strings AS sbhu ON(
        sbhu.language = 'hu' AND
        sbhu.translationof = hc.body_stringid
      )
      LEFT JOIN strings AS sben ON(
        sben.language = 'en' AND
        sben.translationof = hc.body_stringid
      )
      WHERE hc.organizationid = 0
    ");

    foreach( $rs as $row ) {
      $helpkey = $row['shortname'];
      if ( !isset( $this->toUpdate[ $table ][ $helpkey ] ) )
        $this->toUpdate[ $table ][ $helpkey ] = array();

      $toUpdate = &$this->toUpdate[ $table ][ $helpkey ];
      foreach( $row as $field => $value ) {
        switch( $field ) {
          case 'title':
          case 'titleen':
          case 'body':
          case 'bodyen':

            $hash = md5( $value );
            // ugyanaz mint jelenleg?
            if (
                 isset( $this->current[ $helpkey ] ) and
                 $this->current[ $helpkey ][ $field ] === $hash
               )
              continue;

            // vagy a historyban nincs benne ergo ez custom help content
            if ( !$this->isInHistory( $table, $helpkey, $field, $hash ) )
              continue;

            $toUpdate[ $field ] = '';
            break;
        }
      }
    }
  }

  private function initUpdateValues( $table ) {
    $file = "data/defaultvalues/{$table}.php";
    $data = include( $file );
    foreach( $data as $row ) {
      $helpkey = $row['shortname'];
      if ( !isset( $this->toUpdate[ $table ][ $helpkey ] ) )
        continue;

      $toUpdate = &$this->toUpdate[ $table ][ $helpkey ];
      foreach( $toUpdate as $field => $value )
        $toUpdate[ $field ] = $row[ $field ];
    }
  }

  private function updateValues( $table ) {
    $model = $this->bootstrap->getModel( $table );
    $this->shortnameToID = $model->db->getAssoc("
      SELECT shortname, id
      FROM $table
      WHERE organizationid = 0
    ");

    foreach( $this->toUpdate[ $table ] as $shortname => $row ) {
      $model->id = $this->shortnameToID[ $shortname ];

      $strings = array(
        'title_stringid' => array(),
        'body_stringid' => array(),
      );
      $values = array();
      foreach( $row as $field => $value ) {
        switch( $field ) {
          case 'title':
            $values[ $field ] = $value;
            $strings['title_stringid']['hu'] = $value;
            break;
          case 'titleen':
            $strings['title_stringid']['en'] = $value;
            break;
          case 'body':
            $values[ $field ] = $value;
            $strings['body_stringid']['hu'] = $value;
            break;
          case 'bodyen':
            $strings['body_stringid']['en'] = $value;
            break;
        }
      }

      foreach( $strings as $key => $value )
        if ( empty( $value ) )
          unset( $strings[ $key ] );

      if ( empty( $strings ) )
        continue;

      if ( empty( $values ) ) {
        foreach( $strings as $key => $value ) {
          $pos = strpos( $key, '_' );
          $key = substr( $key, 0, $pos );
          $values[ $key ] = reset( $value );
        }
      }

      echo "Updating $table #{$model->id} - {$shortname}\n";
      $model->update( $rs, $values, false, $strings, false );
    }
  }

  private function initWhatToInsert( $table ) {
    $file = "data/defaultvalues/{$table}.php";
    $data = include( $file );
    foreach( $data as $row ) {
      $helpkey = $row['shortname'];
      if ( !isset( $this->shortnameToID[ $helpkey ] ) )
        $this->toInsert[ $helpkey ] = $row;
    }
  }

  private function insertValues( $table ) {
    $model = $this->bootstrap->getModel( $table );

    foreach( $this->toInsert as $shortname => $data ) {
      $strings = array();

      if ( isset( $data['title'] ) ) {

        $strings['title_stringid'] = array(
          'hu' => $data['title'],
          'en' => $data['titleen'],
        );

      }

      if ( isset( $data['body'] ) ) {

        $strings['body_stringid'] = array(
          'hu' => $data['body'],
          'en' => $data['bodyen'],
        );

      }

      echo "Inserting {$shortname}\n";
      var_dump( $strings );
      $model->insert( $data, $strings, false );
    }
  }
}
