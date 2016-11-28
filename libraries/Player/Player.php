<?php
namespace Player;

abstract class Player {
  public $bootstrap;
  protected $row = array();
  protected $info = array();
  protected $model;

  public function __construct( $bootstrap, $model ) {
    $this->bootstrap = $bootstrap;
    $this->model = $model;
    $this->row = $model->row;
  }

  public function setInfo( $info ) {
    $this->info = $info;
  }

  public function getGlobalConfig( $info, $isembed = false ) {
    $this->info = $info;
    $cfg = $this->getConfig( $info, $isembed );
    $ret = array(
      'version'     => $this->bootstrap->config['version'],
      'containerid' => $this->getContainerID(),
      'width'       => $this->getWidth( $isembed ),
      'height'      => $this->getHeight( $isembed ),
      'flashplayer' => $this->getFlashConfig( $cfg ),
    );

    if ( $this->needFlowPlayer( $info ) )
      $ret['flowplayer'] = $this->getFlowConfig( $cfg );

    return $ret;
  }

  abstract public function getWidth( $isembed );
  abstract public function getHeight( $isembed );

  abstract protected function needFlowPlayer( $info );
  abstract protected function getFlashConfig( $cfg );
  abstract protected function getFlowConfig( $cfg );
  abstract protected function getConfig( $info, $isembed );
}
