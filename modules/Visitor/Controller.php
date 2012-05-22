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
    
    $cache = $this->bootstrap->getCache( 'organizations-' . $host, null );
    if ( $cache->expired() ) {
      
      $orgModel = $this->bootstrap->getModel('organizations');
      if ( !$orgModel->checkDomain( $host ) ) {
        
        $fallbackurl = @$this->bootstrap->config['organizationfallbackurl'];
        
        if ( !$fallbackurl )
          die();
        else
          $this->redirect( $fallbackurl );
        
      }
      
      $organization = $orgModel->row;
      $l            = $this->bootstrap->getLocalization();
      $languages    = $l->getLov('languages');
      $languagekeys = explode(',', $organization['languages'] );
      $organization['languages'] = array();
      
      foreach( $languagekeys as $language )
        $organization['languages'][ $language ] = $languages[ $language ];
      
      $cache->put( $organization );
      
    } else
      $organization = $cache->get();
    
    $scheme    = SSL? 'https://': 'http://';
    $baseuri   = $scheme . $organization['domain'] . '/';
    $staticuri = $scheme . 'static.' . $organization['domain'] . '/';
    
    $this->toSmarty['supportemail'] = $this->bootstrap->config['mail']['fromemail'] =
      $this->application->config['mail']['fromemail'] = $organization['supportemail']
    ;
    $this->toSmarty['organization'] = $this->organization        = $organization;
    $this->toSmarty['BASE_URI']     = $organization['baseuri']   = $baseuri;
    $this->toSmarty['STATIC_URI']   = $organization['staticuri'] = $staticuri;
    
    $this->organization = $organization;
    
  }
  
  public function handleAccessFailure( $permission ) {
    
    if ( $permission == 'member' )
      return parent::handleAccessFailure( $permission );
    
    header('HTTP/1.0 403 Forbidden');
    $this->redirectToController('contents', 'nopermission' . $permission );
    
  }
  
  public function modelOrganizationAndIDCheck( $table, $id, $redirectto = 'index' ) {
    
    if ( $id <= 0 ) {
      
      if ( $redirectto !== false )
        $this->redirect( $redirectto );
      else
        return false;
      
    }
    
    $model = $this->bootstrap->getModel( $table );
    $model->addFilter('id', $id );
    $model->addFilter('organizationid', $this->organization['id'] );
    
    $row = $model->getRow();
    
    if ( empty( $row ) and $redirectto !== false )
      $this->redirect( $redirectto );
    elseif ( empty( $row ) )
      return false;
    
    $model->id  = $row['id'];
    $model->row = $row;
    
    return $model;
    
  }
  
  public function modelOrganizationAndUserIDCheck( $table, $id, $redirectto = 'index' ) {
    
    $user = $this->bootstrap->getSession('user');
    
    if ( $id <= 0 or !isset( $user['id'] ) ) {
      
      if ( $redirectto !== false )
        $this->redirect( $redirectto );
      else
        return false;
      
    }
    
    $model = $this->bootstrap->getModel( $table );
    $model->addFilter('id', $id );
    
    if ( $user['iseditor'] )
      $model->addTextFilter("
        userid = '" . $user['id'] . "' OR
        organizationid = '" . $user['organizationid'] . "'
      ");
    else
      $model->addFilter('userid', $user['id'] );
    
    $row = $model->getRow();
    
    if ( empty( $row ) and $redirectto !== false )
      $this->redirect( $redirectto );
    elseif ( empty( $row ) )
      return false;
    
    $model->id  = $row['id'];
    $model->row = $row;
    
    return $model;
    
  }
  
  public function output( $string, $disablegzip = false, $disablekill = false ) {
    
    if ( $this->bootstrap->overridedisablegzip !== null )
      $disablegzip = $this->bootstrap->overridedisablegzip;
    
    parent::output( $string, $disablegzip, $disablekill );
    
  }
  
}
