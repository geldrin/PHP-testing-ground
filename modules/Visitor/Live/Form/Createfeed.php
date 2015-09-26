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
    
    $row = array(
      'name'                    => $values['name'],
      'slideonright'            => $values['slideonright'],
      'accesstype'              => $values['accesstype'],
      'moderationtype'          => $values['moderationtype'],
      'anonymousallowed'        => $values['anonymousallowed'],
      'isnumberofviewspublic'   => $values['isnumberofviewspublic'],
      'issecurestreamingforced' => $values['issecurestreamingforced'],
      'feedtype'                => $values['feedtype'],
      'channelid'               => $this->channelModel->id,
      'userid'                  => $user['id'],
      'organizationid'          => $this->controller->organization['id'],
    );
    $possiblefields = array(
      'recordinglinkid',
      'needrecording',
      'introrecordingid',
    );

    foreach( $possiblefields as $field ) {
      if ( isset( $values[ $field ] ) )
        $row[ $field ] = $values[ $field ];
    }

    $feedModel->insert( $row );
    $this->handleAccesstypeForModel( $feedModel, $values, false );
    
    if ( $values['feedtype'] == 'vcr' ) {
      
      $feedModel->createVCRStream( $values['recordinglinkid'] );
      $this->controller->redirect('live/managefeeds/' . $this->channelModel->id );
      
    }

    if ( $values['livestreamgroupid'] ) {
      $default = 'live/managefeeds/' . $this->channelModel->id;
      $feedModel->handleStreamTemplate( $values['livestreamgroupid'] );
    } else
      $default = 'live/createstream/' . $feedModel->id;

    $this->controller->redirect(
      $this->application->getParameter(
        'forward',
        $default
      )
    );
    
  }
  
}
