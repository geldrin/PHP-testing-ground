<?php
namespace Visitor\Live\Form;

class Modify extends \Visitor\HelpForm {
  public $configfile = 'Modify.php';
  public $template   = 'Visitor/genericform.tpl';
  public $needdb     = true;
  
  protected $channelModel;
  protected $parentchannelModel;
  
  public function init() {
    
    $this->channelModel = $this->controller->modelOrganizationAndUserIDCheck(
      'channels',
      $this->application->getNumericParameter('id')
    );
    
    if ( !$this->channelModel->row['isliveevent'] )
      $this->controller->redirect();
    
    $this->values = $this->channelModel->row;
    $this->values['starttimestamp'] = substr( $this->values['starttimestamp'], 0, 16 );
    $this->values['endtimestamp']   = substr( $this->values['endtimestamp'], 0, 16 );
    $this->controller->toSmarty['formclass'] = 'leftdoublebox';
    $this->controller->toSmarty['helpclass'] = 'rightbox small';
    parent::init();
    
  }
  
  public function postSetupForm() {
    
    $l = $this->bootstrap->getLocalization();
    $this->controller->toSmarty['title'] = $l('live', 'modify_title');
    
  }
  
  protected function needAccessSync( $newvalues ) {
    
    $oldvalues = $this->channelModel->row;
    
    if ( $oldvalues['accesstype'] != $newvalues['accesstype'] )
      return true;
    
    switch ( $oldvalues['accesstype'] ) {
      
      case 'departmentsorgroups':
        $groups      = $this->hasSameAccess( 'groups', $newvalues['groups'] );
        $departments = $this->hasSameAccess( 'departments', $newvalues['departments'] );
        return ($groups and $departments);
        break;
      
    }
    
    return false;
    
  }
  
  protected function hasSameAccess( $type, $newaccess ) {
    
    $channelid = $this->channelModel->id;
    $db        = $this->bootstrap->getAdoDB();
    
    if ( $type == 'departments' )
      $column = 'departmentid';
    elseif ( $type == 'groups' )
      $column = 'groupid';
    
    $existingaccess = $db->getCol("
      SELECT $column
      FROM access
      WHERE
        channelid = '$channelid' AND
        $column IS NOT NULL
    ");
    
    if ( count( $existingaccess ) != count( $newaccess ) )
      return true;
    
    $needsync = false;
    foreach( $existingaccess as $access ) {
      
      if ( !in_array( $access, $newaccess ) ) {
        
        $needsync = true;
        break;
        
      }
      
    }
    
    return $needsync;
    
  }
  
  public function onComplete() {
    
    $values   = $this->form->getElementValues( 0 );
    $needsync = $this->needAccessSync( $values );
    
    if ( @$values['starttimestamp'] )
      $values['starttimestamp'] .= ':00';
    
    if ( @$values['endtimestamp'] )
      $values['endtimestamp'] .= ':59';
    
    $this->handleAccesstypeForModel( $this->channelModel, $values );
    $this->channelModel->updateRow( $values );
    
    if ( $needsync )
      $this->channelModel->syncAccessWithFeeds();
    
    $this->controller->redirect(
      $this->application->getParameter(
        'forward',
        'live/details/' . $this->channelModel->id
      )
    );
    
  }
  
}
