<?php
namespace Visitor\Contributors\Form;
class Create extends \Visitor\Form {
  public $configfile = 'Create.php';
  public $template   = 'Visitor/genericform.tpl';
  public $needdb     = true;
  
  protected $recordingsModel;
  protected $contribvalues = array('nameprefix', 'namefirst', 'namelast', 'nameformat');
  
  public function init() {
    
    parent::init();
    
    $recordingid = $this->application->getNumericParameter('recordingid');
    
    if ( $recordingid )
      $this->recordingsModel = $this->controller->modelOrganizationAndUserIDCheck(
        'recordings',
        $recordingid
      );
    
    $this->controller->toSmarty['nolayout'] = true;
    
  }
  
  public function onComplete() {
    
    $values = $this->form->getElementValues( 0 );
    $l      = $this->bootstrap->getLocalization();
    $user   = $this->bootstrap->getSession('user');
    
    $contributor = array(
      'timestamp'      => date('Y-m-d H:i:s'),
      'createdby'      => $user['id'],
      'organizationid' => $this->controller->organization['id'],
    );
    
    foreach( $this->contribvalues as $value )
      $contributor[ $value ] = $values[ $value ];
    
    $contributorModel = $this->bootstrap->getModel('contributors');
    $contributorModel->insert( $contributor );
    
    if ( $values['recordingid'] ) {
      
      $role = array(
        'contributorid'  => $contributorModel->id,
        'recordingid'    => $this->recordingsModel->id,
        'jobgroupid'     => 1,
        'roleid'         => $values['contributorrole'],
      );
      
      $contribroleModel = $this->bootstrap->getModel('contributors_roles');
      $contribroleModel->insert( $role );
      $contribroleModel->updateRow( array(
          'weight' => $contribroleModel->id,
        )
      );
      
    }
    
    $this->controller->toSmarty['recordingid']  = $this->recordingsModel->id;
    $this->controller->toSmarty['contributors'] =
      $this->recordingsModel->getContributorsWithRoles()
    ;
    $this->controller->jsonOutput( array(
        'status' => 'OK',
        'html'   => $this->controller->fetchSmarty('Visitor/Recordings/Contributors.tpl'),
      )
    );
    
  }
  
  protected function insertOrGetOrganization( $values ) {
    
    if ( !$values['orgid'] and $values['organization'] ) { // insert organization
      
      $orgModel = $this->bootstrap->getModel('organizations');
      $orgModel->insert( array(
          'organizationid'     => $this->controller->organization['id'],
          'name'               => '',
          'nameshort'          => '',
          'name_stringid'      => 0,
          'nameshort_stringid' => 0,
        ),
        array(
          'name_stringid' => array(
            'hu' => $values['hu_organization'],
            'en' => $values['en_organization'],
          ),
          'nameshort_stringid' => array(
            'hu' => $values['hu_organizationshort'],
            'en' => $values['en_organizationshort'],
          ),
        ),
        false
      );
      
      return $orgModel->id;
      
    } elseif ( $values['orgid'] ) { // update orgid
      
      $orgModel = $this->controller->modelOrganizationAndIDCheck(
        'organizations',
        $values['orgid']
      );
      return $orgModel->id;
      
    } else // clear orgid
      return null;
    
  }
  
}
