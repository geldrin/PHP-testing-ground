<?php
function defineIfNot( $name, $value ) {
  if ( defined( $name ) )
    return;

  define( $name, $value );
}

defineIfNot('ADODB_FETCH_DEFAULT', 0 );
defineIfNot('ADODB_FETCH_NUM',     1 );
defineIfNot('ADODB_FETCH_ASSOC',   2 );
defineIfNot('ADODB_FETCH_BOTH',    3 );

// is_subclass_of miatt
class adoconnection {}
class PDOadoDB extends adoconnection {
  public $pdo;
  public $fetchMode = PDO::FETCH_ASSOC;
  private $skipCount = 0; // debug backtrace skip count

  public $databaseType; // adoDB compat
  private $failedTrans = false;
  private $affectedRows = 0;

  private static $NOTNULL = 128;

  public function __construct( $dsn, $username = null, $password = null, $driverOptions = null ) {
    if ( $driverOptions === null )
      $driverOptions = array(
        PDO::ATTR_TIMEOUT      => 5,
        PDO::ATTR_PERSISTENT   => true,
        PDO::CASE_NATURAL      => true,
        PDO::ATTR_ORACLE_NULLS => PDO::NULL_NATURAL,
      );

    // force
    $driverOptions[ PDO::ATTR_ERRMODE ] = PDO::ERRMODE_EXCEPTION;
    $this->pdo = new PDO( $dsn, $username, $password, $driverOptions );
    $this->databaseType = $this->pdo->getAttribute( PDO::ATTR_DRIVER_NAME );
  }

  // proxy to pdo
  public function __call( $name, $arguments ) {
    $func = array( $this->pdo, $name );
    if (
         !method_exists( $this->pdo, $name ) or
         !is_callable( $func )
       )
      throw new \Exception('Method not callable on PDO object: ' . $name );

    return call_user_func_array(
      array( $this->pdo, $name ),
      $arguments
    );
  }

  public function debug( $debug ) {
    $this->debug = (bool) $debug;
  }

  // int retval
  public function exec( $statement ) {
    if( $this->debug )
      \Springboard\Debug::d( $statement );

    return $this->pdo->exec( $statement );
  }

  public function prepare( $statement, $driverOptions = null ) {
    if( !$driverOptions )
      $driverOptions = array();

    $stmt = $this->pdo->prepare( $statement, $driverOptions );
    return new PDOAdoDBStatement( $stmt, $this->debug );
  }

  // adodb compat
  public function __set( $property, $value ) {
    switch( $property ) {
      case 'debug':
        $this->debug( $value );
        break;
      default:
        throw new \Exception("Property not found: $property");
        break;
    }
  }

  public function __get( $property ) {
    switch( $property ) {
      // is_resource hivas miatt egy random resource-ot adunk vissza
      case '_connectionID':
        if ( !$this->pdo )
          return false;

        $conn = $this->pdo->getAttribute( PDO::ATTR_CONNECTION_STATUS );
        if ( $conn ) {
          $resource = opendir( BASE_PATH );
          return $resource;
        }

        return false;
        break;
      case 'debug':
        // meg nem lett beallitva a debugging, ugyhogy default false
        return false;
      default:
        throw new \Exception("Property not found: $property");
        break;
    }
  }

  public function SetFetchMode( $mode ) {
    switch ( $mode ) {
      case 0: // ADODB_FETCH_DEFAULT
      case 2: // ADODB_FETCH_ASSOC
        $this->fetchMode = PDO::FETCH_ASSOC;
        break;
      case 1: // ADODB_FETCH_NUM
        $this->fetchMode = PDO::FETCH_NUM;
        break;
      case 3: // ADODB_FETCH_BOTH
        $this->fetchMode = PDO::FETCH_BOTH;
        break;
      default:
        throw new \Exception("Unknown mode: $mode");
        break;
    }
  }

  public function ErrorNo() {
    return $this->errorCode();
  }

  public function ErrorMsg() {
    $info = $this->errorInfo();
    if ( !empty( $info ) and isset( $info[2] ) )
      return $info[2];

    return '';
  }

  public function StartTrans() {
    return $this->beginTrans();
  }
  public function CompleteTrans() {
    return $this->CommitTrans();
  }
  public function FailTrans() {
    return $this->RollbackTrans();
  }
  public function HasFailedTrans() {
    return $this->failedTrans;
  }

  public function BeginTrans() {
    return $this->pdo->beginTransaction();
  }

  public function CommitTrans( $ok = true ) {
    if ( !$ok )
      return $this->RollbackTrans();

    return $this->pdo->commit();
  }

  public function RollbackTrans() {
    $this->failedTrans = true;
    return $this->pdo->rollback();
  }

  public function Insert_ID() {
    return $this->pdo->lastInsertId();
  }

  public function Affected_Rows() {
    return $this->affectedRows;
  }

  public function GetOne( $statement ) {
    $stmt = $this->prepare( $statement );
    $stmt->execute();
    return $stmt->fetchColumn();
  }

  public function GetRow( $statement ) {
    $stmt = $this->prepare( $statement );
    $stmt->execute();
    return $stmt->fetch( $this->fetchMode );
  }

  public function GetArray( $statement ) {
    $stmt = $this->prepare( $statement );
    $ret = $stmt->fetchAll( $this->fetchMode );
    return $ret;
  }

  public function GetAll( $statement ) {
    return $this->getArray( $statement );
  }

  // TODO make it streaming
  public function GetAssoc( $statement ) {
    $data = $this->getArray( $statement );
    return self::_GetAssoc( $data );
  }

  public function GetCol( $statement ) {
    $stmt = $this->prepare( $statement );
    $stmt->execute();
    return $stmt->fetchAll( PDO::FETCH_COLUMN, 0 );
  }

  private function getMetaInfo( $stmt ) {
    $ret = array(
      'table'   => '',
      'pkfield' => 'id',
      'fields'  => array(),
    );

    $colCount = $stmt->columnCount();
    for ( $i = 0; $i < $colCount; $i++ ) {
      $meta = $stmt->getColumnMeta( $i );

      // TODO mivan postgresnel?
      if (
           $this->databaseType == 'mysql' and
           $meta['native_type'] == "LONG"
         )
        $type = PDO::PARAM_INT;
      else
        $type = $meta['pdo_type'];

      if ( in_array( 'not_null', $meta['flags'] ) )
        $type |= self::$NOTNULL;

      $ret['fields'][ $meta['name'] ] = $type;

      if ( in_array( 'primary_key', $meta['flags'] ) ) {
        $ret['pkfield'] = $meta['name'];
        $ret['table']   = $meta['table'];
      }

      if ( !$ret['table'] and $i == 0 )
        $ret['table'] = $meta['table'];
    }

    return $ret;
  }

  private function valueToPDOType( $value, &$type ) {
    $notnull = false;
    if ( $type & self::$NOTNULL )
      $notnull = true;

    $type &= ~ self::$NOTNULL;

    // ne legyen benne veletlenul se
    $type &= ~ PDO::PARAM_INPUT_OUTPUT;

    // ha lehet null akkor legyen
    if ( ( $value === '' or $value === null ) and !$notnull ) {
      $type = PDO::PARAM_NULL;
      return null;
    }

    switch( $type ) {
      case PDO::PARAM_NULL:
        return null;

      case PDO::PARAM_LOB: // FALLTHROUGH
      case PDO::PARAM_STR:
        return (string)$value;

      case PDO::PARAM_BOOL:
        return (bool)$value;

      case PDO::PARAM_INT:
        return (float)$value;

      default:
        throw new \Exception("PDO Type unknown: $type");
    }
  }

  public function GetUpdateSQL( $stmt, $values, $forceUpdate = false, $magicQ = false, $forceNulls = false ) {

    $row = $stmt->fields;
    $meta = $this->getMetaInfo( $stmt->getStatement() );
    if ( empty( $meta['fields'] ) )
      throw new \Exception('No valid fields found');

    if ( !$meta['pkfield'] )
      throw new \Exception('Primary-key column not found');

    if ( !isset( $row[ $meta['pkfield'] ] ) )
      throw new \Exception('Primary key field not found, cannot generate update sql');

    $sql = "UPDATE " . $meta['table'] . " SET ";
    $fields = array();
    foreach( $values as $field => $value ) {
      if ( !isset( $meta['fields'][ $field ] ) )
        continue;

      // ha ugyanaz az ertek, nem updatelunk
      if ( array_key_exists( $field, $row ) and $row[ $field ] == $value )
        continue;

      $type = $meta['fields'][ $field ];
      $val  = $this->valueToPDOType( $value, $type );
      $type = $meta['fields'][ $field ];
      $fields[] = $field . ' = ' . $this->quote(
        $val,
        $type
      );
    }

    $sql .= implode( ', ', $fields );
    $sql .=
      ' WHERE ' . $meta['pkfield'] . ' = ' .
      $this->quote( $row[ $meta['pkfield'] ] )
    ;

    return $sql;
  }

  public function GetInsertSQL( $stmt, $values, $magicQ = false, $force = null ) {
    $fields = array();
    $v = array();

    $meta = $this->getMetaInfo( $stmt->getStatement() );
    foreach( $meta['fields'] as $field => $type ) {
      if ( !array_key_exists( $field, $values ) )
        continue;

      $value = $this->valueToPDOType( $values[ $field ], $type );
      $v[] = $this->quote( $value, $type );
      $fields[] = $field;
    }

    $sql  = "INSERT INTO " . $meta['table'] . " (" . implode(', ', $fields ) . ")\n";
    $sql .= "VALUES (" . implode(', ', $v ) . ")";
    return $sql;
  }

  public function qstr( $string ) {
    return $this->quote( $string );
  }

  public function Execute( $statement ) {
    if( $this->debug )
      \Springboard\Debug::d( $statement );

    $stmt = $this->pdo->query( $statement ); // a stmt mar ->execute-olva jon
    if( !$stmt )
      return false;

    $stmt = new ADODB_PDOStatement( $stmt, $this->fetchMode );
    $this->affectedRows = $stmt->RecordCount();
    return $stmt;
  }

  public function query( $statement ) {
    return $this->execute( $statement );
  }

  public static function _GetAssoc( $data ) {
    $ret = array();
    $first = reset( $data );

    // if there are more than two columns, make the first column the key
    // and all the other columns go into the value
    // (as an array with the keys being the column names)
    if ( is_array( $first ) and count( $first ) > 2 ) {

      foreach( $data as $row ) {
        $firstValue = reset( $row );
        $firstKey = key( $row );

        $ret[ $firstValue ] = array();
        foreach( $row as $key => $value ) {
          if ( $key === $firstKey )
            continue;

          $ret[ $firstValue ][ $key ] = $value;
        }
      }

    } else {
      // purely two columns, first column's value is the key,
      // second column's value is the value

      foreach( $data as $row ) {
        $firstValue = reset( $row );
        $secondValue = next( $row );

        $ret[ $firstValue ] = $secondValue;
      }

    }

    return $ret;
  }
}

// ha a PDOStatement class-bol szarmazunk akkor semmise mukodik valamiert
class PDOAdoDBStatement {
  private $stmt;
  private $debug;

  public function __construct( $stmt, $debug ) {
    $this->stmt = $stmt;
    $this->debug = $debug;
  }

  public function __destruct() {
    $this->stmt->closeCursor();
  }

  // proxy to pdo statement
  public function __call( $name, $arguments ) {
    if ( strpos( $name, 'bind' ) !== 0 )
      $this->stmt->execute();

    $func = array( $this->stmt, $name );
    if (
         !method_exists( $this->stmt, $name ) or
         !is_callable( $func )
       )
      throw new \Exception('Method not callable on PDO object: ' . $name );

    return call_user_func_array(
      array( $this->stmt, $name ),
      $arguments
    );
  }

  private function d( $executed ) {
    $exec = 'Executed successfully: ' . ( $executed? 'true': 'false' );
    $info = $this->errorInfo();
    if ( empty( $info ) or $info[0] === PDO::ERR_NONE )
      $info = 'No error';

    Springboard\Debug::d(
      $info,
      $this->dump(),
      $exec
    );
  }

  public function dump() {
    if ( !ob_start() )
      throw new \Exception('Could not start output buffer!');

    $this->stmt->debugDumpParams();

    $ret = trim( ob_get_contents() );
    ob_end_clean();
    return $ret;
  }

  public function execute( $params = null ) {
    $ret = $this->stmt->execute( $params );
    if ( $this->debug )
      $this->d( $ret );

    return $ret;
  }
}

class ADODB_PDOStatement implements \Iterator {
  public $fields = false;

  private $stmt;
  private $key = 0;
  private $fetchMode;

  public function __construct( &$stmt, $fetchMode ) {
    $this->fetchMode = $fetchMode;
    $this->stmt = $stmt;

    // ha ->execute bol jottunk akkor lehet hogy mondjuk egy DELETE FROM
    // statement, amit viszont nem lehet ->fetchelni, itt leelenorizzuk
    $colCount = $stmt->columnCount();
    if ( $colCount > 0 )
      $this->fields = $this->stmt->fetch( $this->fetchMode );
  }

  public function __destruct() {
    $this->Close();
  }

  public function current() {
    return $this->fields;
  }

  public function next() {
    $this->MoveNext();
  }

  public function key() {
    return $this->key;
  }

  public function valid() {
    if ( $this->fields === false )
      return false;

    return true;
  }

  public function rewind() {
  }

  public function getStatement() {
    return $this->stmt;
  }

  // adodb compat
  public function GetAssoc() {
    $data = $this->stmt->fetchAll( $this->fetchMode );
    return PDOadoDB::_GetAssoc( $data );
  }

  public function GetArray() {
    return $this->stmt->fetchAll( $this->fetchMode );
  }

  public function RecordCount() {
    return $this->stmt->rowCount();
  }

  public function NextRecordSet() {
    return $this->stmt->nextRowset();
  }

  public function MoveNext() {
    $this->fields = $this->stmt->fetch( $this->fetchMode );
    if ( $this->fields === false )
      return false;

    $this->key++;
    return $this->fields;
  }

  public function Close() {
    if ( !$this->stmt )
      return;

    $this->stmt->closeCursor();
    $this->stmt = null;
  }

  public function __get( $property ) {
    switch( $property ) {
      case 'EOF':
        return !$this->fields;
        break;
      default:
        throw new \Exception("Property not found: $property");
        break;
    }
  }

}
