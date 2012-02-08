<?php
namespace Visitor\Recordings\Paging;
class Myrecordings extends \Springboard\Controller\Paging {
  protected $orderkey = 'creation_desc';
  protected $sort = array(
    'creation'      => 'id',
    'creation_desc' => 'id DESC',
  );
  protected $template = 'Visitor/Recordings/paging/Myrecordings.tpl';
  protected $recordingsModel;
  
  public function init() {
    $this->foreachelse = 'No recordings found';
    $this->title = 'My recordings';
  }
  
  protected function setupCount() {
    
    $this->recordingsModel = $this->bootstrap->getModel('recordings');
    $user  = $this->bootstrap->getUser();
    
    $this->recordingsModel->addFilter('userid', $user->id );
    return $this->itemcount = $this->recordingsModel->getCount();
    
  }
  
  protected function getItems( $start, $limit, $orderby ) {
    return $this->recordingsModel->getArray( $start, $limit, false, $orderby );
  }
  
}
