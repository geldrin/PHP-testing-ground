<?php
namespace Visitor\Contributors\Form;
class Modify extends \Visitor\Contributors\Form\Create {
  public $configfile = 'Modify.php';
  public $template   = 'Visitor/genericform.tpl';
  public $needdb     = true;
  
  protected $contribroleModel;
  protected $contributorModel;
  
  public function init() {
    
    parent::init();
    $user = $this->bootstrap->getSession('user');
    
    $this->contribroleModel = $this->controller->modelIDCheck(
      'contributors_roles',
      $this->application->getNumericParameter('crid')
    );
    $this->recordingsModel  = $this->controller->modelOrganizationAndUserIDCheck(
      'recordings',
      $this->contribroleModel->row['recordingid']
    );
    $this->contributorModel = $this->controller->modelOrganizationAndIDCheck(
      'contributors',
      $this->contribroleModel->row['contributorid']
    );
    
    if (
         $this->contributorModel->row['createdby'] != $user['id'] and
         (
           ( !$user['isclientadmin'] and !$user['isadmin'] and !$user['iseditor'] ) or
           $user['organizationid'] != $this->contributorModel->row['organizationid']
         )
       )
      $this->controller->redirect('');
    
    foreach( $this->contribvalues as $value )
      $this->values[ $value ] = $this->contributorModel->row[ $value ];
    
    $this->values['contribrole']  = $this->contribroleModel->row['roleid'];
    $this->values['orgid']        = $this->contribroleModel->row['organizationid'];
    
    if ( $this->values['orgid'] ) {
      
      $orgModel = $this->controller->modelOrganizationAndIDCheck(
        'organizations',
        $this->values['orgid']
      );
      
      $this->values['organization'] = $orgModel->getName();
      
    }
    
    $this->controller->toSmarty['nolayout'] = true;
    
  }
  
  public function onComplete() {
    
    $values = $this->form->getElementValues( 0 );
    $l      = $this->bootstrap->getLocalization();
    $user   = $this->bootstrap->getSession('user');
    
    $role = array(
      'roleid'         => $values['contributorrole'],
      'organizationid' => $this->insertOrGetOrganization( $values ),
    );
    
    $contributor = array();
    foreach( $this->contribvalues as $value )
      $contributor[ $value ] = $values[ $value ];
    
    $this->contributorModel->updateRow( $contributor );
    $this->contribroleModel->updateRow( $role );
    
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
  
}
