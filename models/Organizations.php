<?php
namespace Model;

class Organizations extends \Springboard\Model\Multilingual {
  public $multistringfields = array( 'name', 'nameshort', 'introduction', );
  
  public function checkDomain( $domain ) {
    
    $this->clearFilter();
    $this->addFilter('domain', $domain, false, false );
    $this->addFilter('disabled', 0 );
    $organization = $this->getRow();
    
    if ( !$organization )
      return false;
    
    $this->id  = $organization['id'];
    $this->row = $organization;
    
    return true;
    
  }
  
  public function findChildrenIDs( $parentid = null ) {
    
    if ( $parentid === null )
      $this->ensureID();
    
    if ( !$parentid )
      $parentid = $this->db->qstr( $this->id );
    else
      $parentid = $this->db->qstr( $parentid );
    
    $children = $this->db->getCol("
      SELECT id
      FROM organizations
      WHERE parentid = " . $parentid
    );
    
    foreach( $children as $parentid )
      $children = array_merge( $children, $this->findChildrenIDs( $parentid ) );
    
    return $children;
    
  }
  
  public function setup() {
    
    $this->ensureObjectLoaded();
    $this->updateRow( array('organizationid' => $this->id ) );
    
    $this->setupDefaultGenres();
    $this->setupDefaultChanneltypes();
    
  }
  
  public function setupDefaultGenres() {
    
    $this->ensureID();
    
    $genres     = include(
      $this->bootstrap->config['datapath'] . 'defaultvalues/genres.php'
    );
    $genreModel = $this->bootstrap->getModel('genres');
    $parentid   = '0';
    
    foreach ( $genres as $data ) {
      
      if ( empty( $data ) )
        continue;
      
      $strings = array(
        'name_stringid' => array(
          'hu' => $data['namehungarian'],
          'en' => $data['nameenglish']
        ),
      );
      
      if ( $data['origparentid'] !== '0' )
        $data['parentid'] = $parentid;
      else
        $data['parentid'] = 0;
      
      $data['organizationid'] = $this->id;
      $row = $genreModel->insert( $data, $strings, false );
      
      if ( $data['origparentid'] == 0 )
        $parentid = $row['id'];
      
    }
    
  }
  
  public function setupDefaultChanneltypes() {
    
    $this->ensureID();
    
    $channeltypes = include(
      $this->bootstrap->config['datapath'] . 'defaultvalues/channeltypes.php'
    );
    $typeModel = $this->bootstrap->getModel('channel_types');
    
    foreach ( $channeltypes as $data ) {
      
      if ( empty( $data ) )
        continue;
      
      $strings = array(
        'name_stringid' => array(
          'hu' => $data['namehungarian'],
          'en' => $data['nameenglish']
        ),
      );
      
      if ( !$data['weight'] )
        $data['weight'] = 100;
      
      $data['organizationid'] = $this->id;
      $row = $typeModel->insert( $data, $strings, false );
      
    }
    
  }
  
}
