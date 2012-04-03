<?php
namespace Visitor\Index;

class Controller extends \Visitor\Controller {
  public $permissions = array(
    'index'  => 'public',
  );
  
  public function indexAction() {
    
    $recordingsModel = $this->bootstrap->getModel('recordings');
    $this->toSmarty['recordings'] = $recordingsModel->getRandomRecordings( 3, $this->organization['id'] );
    $this->smartyoutput('Visitor/Index/index.tpl');
    
  }
  
}
