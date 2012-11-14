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
    $this->controller->toSmarty['formclass'] = 'leftdoublebox';
    parent::init();
    
  }
  
  public function postSetupForm() {
    
    $l = $this->bootstrap->getLocalization();
    $this->controller->toSmarty['title'] = $l('live', 'modify_title');
    
  }
  
  public function onComplete() {
    
    $values        = $this->form->getElementValues( 0 );
    $oldaccesstype = $this->channelModel->row['accesstype'];
    
    if ( @$values['starttimestamp'] )
      $values['starttimestamp'] .= ' 08:00:00';
    
    if ( @$values['endtimestamp'] )
      $values['endtimestamp'] .= ' 20:00:00';
    
    $this->handleAccesstypeForModel( $this->channelModel, $values );
    $this->channelModel->updateRow( $values );
    
    if ( $oldaccesstype != $values['accesstype'] )
      $this->channelModel->syncAccessWithFeeds();
    
    $this->controller->redirect(
      $this->application->getParameter(
        'forward',
        'live/details/' . $this->channelModel->id
      )
    );
    
  }
  
}
