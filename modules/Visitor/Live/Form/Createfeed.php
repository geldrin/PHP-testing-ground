<?php
namespace Visitor\Live\Form;

class Createfeed extends \Visitor\HelpForm {
  public $configfile = 'Createfeed.php';
  public $template   = 'Visitor/genericform.tpl';
  public $needdb     = true;
  
  protected $channelModel;
  
  public function init() {
    
    $this->channelModel = $this->controller->modelOrganizationAndUserIDCheck(
      'channels',
      $this->application->getNumericParameter('id')
    );
    $this->values['accesstype'] = $this->channelModel->row['accesstype'];
    
    switch( $this->channelModel->row['accesstype'] ) {
      
      case 'departmentsorgroups':
        
        $this->values['departments'] = $this->channelModel->db->getCol("
          SELECT departmentid
          FROM access
          WHERE
            channelid = '" . $this->channelModel->id . "' AND
            departmentid IS NOT NULL
        ");
        
        $this->values['groups'] = $this->channelModel->db->getCol("
          SELECT groupid
          FROM access
          WHERE
            channelid = '" . $this->channelModel->id . "' AND
            groupid IS NOT NULL
        ");
        break;
      
    }
    
    $l = $this->bootstrap->getLocalization();
    $this->controller->toSmarty['title'] = $l('live', 'createfeed_title');
    $this->controller->toSmarty['formclass'] = 'leftdoublebox';
    $this->controller->toSmarty['helpclass'] = 'rightbox small';
    parent::init();
    
  }
  
  public function onComplete() {
    
    $values    = $this->form->getElementValues( 0 );
    $user      = $this->bootstrap->getSession('user');
    $feedModel = $this->bootstrap->getModel('livefeeds');
    
    $values['channelid']      = $this->channelModel->id;
    $values['userid']         = $user['id'];
    $values['organizationid'] = $this->controller->organization['id'];
    unset( $values['id'] );
    
    $feedModel->insert( $values );
    $this->handleAccesstypeForModel( $feedModel, $values, false );
    
    if ( $values['feedtype'] == 'vcr' ) {
      
      $feedModel->createVCRStream( $values['recordinglinkid'] );
      $this->controller->redirect('live/managefeeds/' . $this->channelModel->id );
      
    }
    
    $this->controller->redirect(
      $this->application->getParameter(
        'forward',
        'live/createstream/' . $feedModel->id
      )
    );
    
  }
  
}
