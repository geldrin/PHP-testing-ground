<?php
function smarty_modifier_getIngressURL( $feed ) {
  static $feedModel;
  if ( !$feedModel )
    $feedModel = \Bootstrap::getInstance()->getModel('livefeeds');
  
  $feedModel->row = $feed;
  $feedModel->id  = $feed['id'];
  return $feedModel->getIngressURL();
}
