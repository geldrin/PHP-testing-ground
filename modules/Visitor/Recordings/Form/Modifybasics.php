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
         $this->recordingsModel->row['organizationid'] and
         $this->recordingsModel->row['organizationid'] != $user->organizationid 
       )
      unset( $values['organizationid'] );
    
    $this->recordingsModel->updateRow( $values );
    $this->recordingsModel->updateFulltextCache();
    
    $this->controller->redirect(
      'recordings/modifyclassification/' . $this->recordingsModel->id,
      array( 'forward' => $values['forward'] )
    );
    
  }
  
}
