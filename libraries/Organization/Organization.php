<?php

class Organization {
  protected $organization;
  
  public function __construct( $bootstrap ) {
    $this->bootstrap = $bootstrap;
  }
  
  public function setup() {
    
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
    
    if ( SSL )
      $scheme = 'https://';
    else
      $scheme = 'http://';
    
    $smarty = $this->bootstrap->getSmarty();
    $smarty->assign('BASE_URI',     $scheme . $host . '/' );
    $smarty->assign('organization', $organization );
    
    return $this->organization = $organization;
    
  }
  
  public function __get( $key ) {
    return $this->organization[ $key ];
  }
  
}
