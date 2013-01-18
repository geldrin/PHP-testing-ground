<?php
namespace Visitor\Recordings\Form;

class Modifycontributors extends \Visitor\Recordings\ModifyForm {
  public $configfile = 'Modifycontributors.php';
  public $template   = 'Visitor/Recordings/Modifycontributors.tpl';
  public $needdb     = true;
  
  public function init() {
    
    parent::init();
    $this->controller->toSmarty['needfancybox'] = true;
    $this->controller->toSmarty['recordingid']  = $this->recordingsModel->id;
    $this->controller->toSmarty['contributors'] = $this->contributors =
      $this->recordingsModel->getContributorsWithRoles()
    ;
    
  }
  
  public function postGetForm() {
    
    parent::postGetForm();
    $l = $this->bootstrap->getLocalization();
    $this->form->submit = $l('recordings', 'forward');
    $this->form->layouts['tabular']['buttonrow'] =
      '<tr class="buttonrow">
        <td colspan="2">
          <input type="button" value="' . $l('recordings', 'add') . '" class="submitbutton" id="addcontributor"/>
          %s
        </td>
      </tr>
    ';
    
    
  }
  
  public function onComplete() {
    
    $values = $this->form->getElementValues( 0 );
    
    $this->recordingsModel->updateFulltextCache( true );
    
    $this->controller->redirect(
      'recordings/modifysharing/' . $this->recordingsModel->id,
      array( 'forward' => $values['forward'] )
    );
    
  }
  
}
