<?php
namespace Visitor;

class Controller extends \Springboard\Controller\Visitor {
  public $organization;
  
  public function init() {
    $this->setupOrganization();
    parent::init();
  }
  
  public function setupOrganization() {
    
    $host = $_SERVER['SERVER_NAME'];
    
    $cache = $this->bootstrap->getCache( 'organizations-' . $host, null, true );
    if ( $cache->expired() ) {
      
      $orgModel = $this->bootstrap->getModel('organizations');
      if ( !$orgModel->checkDomain( $host ) )
        throw new Exception('Organization not found!');
      
      $organization = $orgModel->row;
      $cache->put( $organization );
      
    } else
      $organization = $cache->get();
    
    $this->organization = $organization;
    
    $smarty = $this->bootstrap->getSmarty();
    $smarty->assign('organization', $organization );
    
  }
  
  public function handleAccessFailure( $permission ) {
    
    if ( $permission == 'member' )
      return parent::handleAccessFailure( $permission );
    
    header('HTTP/1.0 403 Forbidden');
    $this->redirectToController('contents', 'nopermission' . $permission );
    
  }
  
  public function modelOrganizationAndIDCheck( $table, $id, $forwardto = 'index' ) {
    
    if ( $id <= 0 )
      $this->redirect( $redirectto );
    
    $model        = $this->bootstrap->getModel( $table );
    $model->addFilter('id', $id );
    $model->addFilter('organizationid', $this->organization['id'] );
    
    $row = $model->getRow();
    
    if ( empty( $row ) and $forwardto !== false )
      $this->redirect( $redirectto );
    elseif ( empty( $row ) )
      return false;
    
    $model->id  = $row['id'];
    $model->row = $row;
    
    return $model;
    
  }
  
  public function modelOrganizationAndUserIDCheck( $table, $id, $forwardto = 'index' ) {
    
    $user         = $this->bootstrap->getUser();
    
    if ( $id <= 0 or !isset( $user->id ) )
      $this->redirect( $redirectto );
    
    $model = $this->bootstrap->getModel( $table );
    $model->addFilter('id', $id );
    
    if ( $user->iseditor )
      $model->addTextFilter("
        userid = '" . $user->id . "' OR
        organizationid = '" . $user->organizationid . "'
      ");
    else
      $model->addFilter('userid', $user->id );
    
    $row = $model->getRow();
    
    if ( empty( $row ) and $forwardto !== false )
      $this->redirect( $redirectto );
    elseif ( empty( $row ) )
      return false;
    
    $model->id  = $row['id'];
    $model->row = $row;
    
    return $model;
    
  }
  
}
