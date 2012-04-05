<?php
namespace Visitor\Index;

class Controller extends \Visitor\Controller {
  public $permissions = array(
    'index'  => 'public',
  );
  
  public function indexAction() {
    
    $recordingsModel = $this->bootstrap->getModel('recordings');
    $newsModel = $this->bootstrap->getModel('organizations_news');
    
    $this->toSmarty['introduction'] = $this->organization['introduction'];
    $this->toSmarty['recordings'] = $recordingsModel->getRandomRecordings( 3, $this->organization['id'] );
    $this->toSmarty['news'] = $newsModel->getRecentNews( 5, $this->organization['id'] );
    $this->smartyoutput('Visitor/Index/index.tpl');
    
  }
  
}
