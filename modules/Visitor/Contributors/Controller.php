<?php
namespace Visitor\Contributors;

class Controller extends \Visitor\Controller {
  public $permissions = array(
    'index'  => 'public',
    'search' => 'uploader',
    'create' => 'uploader',
    'modify' => 'uploader',
  );
  
  public $paging = array(
  );
  
  public $forms = array(
    'create' => 'Visitor\\Contributors\\Form\\Create',
    'modify' => 'Visitor\\Contributors\\Form\\Modify',
  );
  
  public function indexAction() {
    $this->redirect('');
  }
  
  public function searchAction() {
    
    $term   = $this->application->getParameter('term');
    $output = array(
      'status' => 'OK',
      'data'   => array(),
    );
    
    if ( !$term )
      $this->jsonoutput( $output );
    
    $contribModel = $this->bootstrap->getModel('contributors');
    $results      = $contribModel->search( $term, $this->organization['id'] );
    
    if ( empty( $results ) )
      $this->jsonoutput( $output );
    
    include_once( $this->bootstrap->config['templatepath'] . 'Plugins/modifier.nameformat.php' );
    foreach( $results as $result ) {
      
      $data = array(
        'value' => $result['id'],
        'label' => smarty_modifier_nameformat( $result ),
        'img'   => $this->bootstrap->staticuri,
      );
      
      if ( $result['indexphotofilename'] )
        $data['img'] .= $result['indexphotofilename']; // TODO
      else
        $data['img'] .= 'images/avatar_placeholder.png';
      
      $output['data'][] = $data;
      
    }
    
    $this->jsonoutput( $output['data'] );
    
  }
  
}
