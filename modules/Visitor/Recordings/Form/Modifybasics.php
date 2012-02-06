<?php
namespace Visitor\Recordings\Form;

class Modifybasics extends \Visitor\Recordings\ModifyForm {
  public $configfile   = 'Modifybasics.php';
  public $template     = 'Visitor/genericform.tpl';
  
  public function onComplete() {
    
    $values = $this->form->getElementValues( 0 );
    $user   = $this->bootstrap->getUser();
    
    $values['metadataupdatedtimestamp'] = date('Y-m-d H:i:s');
    $values['organizationid']           = $user->organizationid;
    
    // if the recording has an organization and differs from the users
    // dont update it, otherwise update it, is this necessary even?
    if (
         $this->recordingModel->row['organizationid'] and
         $this->recordingModel->row['organizationid'] != $user->organizationid 
       )
      unset( $values['organizationid'] );
    
    $this->recordingModel->updateRow( $values );
    $this->recordingModel->updateFulltextCache();
    
    $this->controller->redirect(
      'recordings/modifyclassification/' . $recordingModel->id,
      array( 'forward' => $values['forward'] )
    );
    
  }
  
}
