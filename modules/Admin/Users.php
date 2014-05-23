<?php
namespace Admin;
class Users extends \Springboard\Controller\Admin {
  
  public function init() {
    
    // azert public hogy be tudjuk allitani adott domainen a usert
    // muszaj hogy ne inditsunk session-t mert az a mostani domainre allitana
    // a sessiont, nekunk pedig a cel domainre kell,
    $this->permissions['loginas'] = 'public';
    parent::init();
    
  }
  
  public function loginasAction() {
    
    $userid = $this->application->getNumericParameter('id');
    
    if ( $userid <= 0 )
      $this->redirect('users');
    
    $crypto    = $this->bootstrap->getEncryption();
    $userModel = $this->bootstrap->getModel('users');
    $orgModel  = $this->bootstrap->getModel('organizations');
    $userModel->select( $userid );
    $organization = $orgModel->getOrganizationByID(
      $userModel->row['organizationid']
    );
    
    if ( empty( $userModel->row ) )
      $this->redirect('users');
    
    // eltesszuk a user azonosito hasht ami ha nincs meg az adott domainen
    // akkor nem hagyjuk bejelentkezni a usert az adott id-vel
    $cache = $this->bootstrap->getCache('admin-users-loginas', 300, true );
    $hash  = $crypto->getHash(
      @$_SERVER['HTTP_USER_AGENT'] . '-' . $_SERVER['REMOTE_ADDR']
    );
    
    $basedomain = substr(
      $this->bootstrap->config['baseuri'],
      0,
      strpos( $this->bootstrap->config['baseuri'], '/')
    );
    
    if ( $_SERVER['SERVER_NAME'] != $orgModel->row['domain'] ) {
      
      $this->checkAccess('admin');
      $cache->put( $hash );
      $url = 'http://' . $orgModel->row['domain'] . $_SERVER['REQUEST_URI'];
      $this->redirect( $url );
      
    } elseif ( $_SERVER['SERVER_NAME'] == $basedomain ) {
      
      // az alap domainre akarunk beloginolni
      $this->checkAccess('admin');
      $cache->put( $hash );
      
    }
    
    if ( $cache->get() != $hash )
      $this->redirect('users');
    
    $cache->put("");
    $cache->expire();
    $this->bootstrap->config['cookiedomain'] = $orgModel->row['cookiedomain'];
    $this->bootstrap->config['sessionidentifier'] = $orgModel->row['domain'];
    $userModel->registerForSession(); // sima user-kent register
    $this->redirect( 'http://' . $orgModel->row['domain'] );
    
  }
  
  public function redirectToMainDomain() {
    if ( @$this->action != 'loginas' )
      return parent::redirectToMainDomain();
  }
  
}
