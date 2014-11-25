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
    $lastslice = $this->getLastSlice( $values );

    // SEEK utan jon egy PLAY akkor nem hozunk letre uj sliceot mert a
    // SEEK-ben "bennevan" a PLAY
    if (
         $values['action'] == 'PLAY' and
         $lastslice and
         $lastslice['startaction'] == 'SEEK' and
         !$lastslice['stopaction']
       )
      return $this->updateSlice( $values ); // akkor updateljuk, nem krealunk ujjat
    elseif ( // mar nyitottunk egy slice-ot, de a newslice csak kesobb esett be
             $lastslice and
             $lastslice['viewsessionid'] === $values['viewsessionid']
           )
      return $this->row; // ignore

    $values['startaction'] = $values['action'];
    $this->insert( $values );
    return $this->row;
  }

  protected function closeAndCreateSlice( $values ) {
    $this->closeSlice( $values );
    return $this->newSlice( $values );
  }

  protected function closeSlice( $values ) {
    return $this->updateSlice( $values, $values['action'] );
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
      $row = $this->newSlice( $values );

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
