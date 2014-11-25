<?php
namespace Model;

class View_statistics_ondemand extends \Model\View_statistics {
  protected static $updateKeepFields = array(
    'positionuntil' => true,
    'timestamp'     => true,
  );

  public function log( $values ) {
    
    $values = $this->populateStreamInfo( $values );
    $values['timestamp'] = date('Y-m-d H:i:s');

    $this->runStateMachine( $values );

  }

  protected function newSlice( $values ) {
    if ( !isset( $values['positionfrom'] ) or !$values['positionfrom'] )
      $values['positionfrom'] = $values['positionuntil'];

    unset( $values['positionuntil'] );
    return parent::newSlice($values);
  }

}
