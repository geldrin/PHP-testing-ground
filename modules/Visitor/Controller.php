<?php
namespace Visitor;

class Controller extends \Springboard\Controller\Visitor {
  
  public function handleAccessFailure( $permission ) {
    
    if ( $permission == 'member' )
      return parent::handleAccessFailure( $permission );
    
    header('HTTP/1.0 403 Forbidden');
    $this->redirectToController('contents', 'nopermission' . $permission );
    
  }
  
  public function modelOrganizationAndIDCheck( $table, $idparam = 'id', $forwardto = 'index' ) {
    
    $id = $this->application->getNumericParameter( $idparam );
    
    if ( $id <= 0 )
      $this->redirect( $redirectto );
    
    $organization = $this->bootstrap->getOrganization();
    $model        = $this->bootstrap->getModel( $table );
    $model->addFilter('id', $id );
    $model->addFilter('organizationid', $organization->id );
    
    $row = $model->getRow();
    
    if ( empty( $row ) )
      $this->redirect( $redirectto );
    
    $model->id  = $row['id'];
    $model->row = $row;
    
    return $model;
    
  }
  
  public function modelOrganizationAndUserIDCheck( $table, $idparam =  'id', $forwardto = 'index' ) {
    
    $id = $this->application->getNumericParameter( $idparam );
    
    $organization = $this->bootstrap->getOrganization();
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
    
    if ( empty( $row ) )
      $this->redirect( $redirectto );
    
    $model->id  = $row['id'];
    $model->row = $row;
    
    return $model;
    
  }
  
}
