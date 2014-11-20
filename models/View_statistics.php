<?php
namespace Model;

abstract class View_statistics extends \Springboard\Model {
  protected static $updateKeepFields = array();
  protected static $stateTable = array(
    'PLAY'    => 'newSlice',
    'SEEK'    => 'closeAndCreateSlice',
    'PAUSE'   => 'closeSlice',
    'STOP'    => 'closeSlice',
    'PLAYING' => 'updateSlice',
  );

  abstract public function log( $values );

  public function populateStreamInfo( &$values ) {
    if ( !isset( $values['url'] ) )
      return $values;

    $streamdata = parse_url( $values['url'] );
    unset( $values['url'] );

    $values['streamscheme'] = $streamdata['scheme'];
    $values['streamserver'] = $streamdata['host'];
    $values['streamurl']    = $streamdata['path'];

    return $values;
  }

  protected function runStateMachine( $values ) {
    $this->startTrans();

    if ( !isset( static::$stateTable[ $values['action'] ] ) )
      throw new \Exception("Unhandled action: " . $values['action'] );

    $method = static::$stateTable[ $values['action'] ];
    $this->$method( $values );

    $this->endTrans();
  }

  protected function newSlice( $values ) {
    if ( $values['action'] == 'PLAY' ) {

      $lastslice = $this->getLastSlice( $values );
      // SEEK utan jon egy PLAY akkor nem hozunk letre uj sliceot mert a
      // SEEK-ben "bennevan" a PLAY
      if (
           $lastslice and
           $lastslice['startaction'] == 'SEEK' and !$lastslice['stopaction']
         )
        return $this->updateSlice( $values ); // akkor updateljuk, nem krealunk ujjat

    }

    $values['startaction'] = $values['action'];
    $this->insert( $values );
  }

  protected function closeAndCreateSlice( $values ) {
    $this->closeSlice( $values );
    $this->newSlice( $values );
  }

  protected function closeSlice( $values ) {
    $this->updateSlice( $values, $values['action'] );
  }

  protected function getLastSlice( $values ) {
    if ( $this->row )
      return $this->row;

    $viewid = $this->db->qstr( $values['viewsessionid'] );
    $ret    = $this->db->getRow("
      SELECT *
      FROM " . $this->table . "
      WHERE viewsessionid = $viewid
      ORDER BY id DESC
      LIMIT 1
    ");

    if ( $ret ) {
      $this->id  = $ret['id'];
      $this->row = $ret;
    }

    return $ret;

  }

  protected function updateSlice( $values, $stopaction = null ) {
    $row = $this->getLastSlice( $values );

    if ( !$row )
      throw new \Exception("No open slice found!");

    $filteredvalues = array();
    foreach( $values as $field => $value ) {
      if ( isset( static::$updateKeepFields[ $field ] ) )
        $filteredvalues[ $field ] = $value;
    }

    if ( $stopaction )
      $filteredvalues['stopaction'] = $stopaction;

    if ( empty( $filteredvalues ) )
      throw new \Exception(
        "Nothing to update the open slice with: " . var_export( $values, true )
      );

    $this->updateRow( $filteredvalues );
  }

}
