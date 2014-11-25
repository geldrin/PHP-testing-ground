<?php
namespace Model;

class View_statistics_live extends \Model\View_statistics {
  protected static $updateKeepFields = array(
    'timestampuntil' => true,
  );

  public function log( $values ) {
    $values = $this->populateStreamInfo( $values );
    $values['timestampuntil'] = date('Y-m-d H:i:s');

    $this->runStateMachine( $values );

  }

  protected function newSlice( $values ) {
    $values['timestampuntil'] = $values['timestampfrom'] = date('Y-m-d H:i:s');
    return parent::newSlice( $values );
  }

  protected function closeSlice( $values ) {
    unset( $values['timestampfrom'] );
    return parent::closeSlice( $values );
  }

  protected function updateSlice( $values, $stopaction = null ) {
    $values['timestampuntil'] = date('Y-m-d H:i:s');
    return parent::updateSlice( $values, $stopaction );
  }

}
