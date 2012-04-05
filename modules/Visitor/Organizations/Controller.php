<?php
namespace Visitor\Organizations;

class Controller extends \Visitor\Controller {
  public $permissions = array(
    'index'             => 'public',
    'newsdetails'       => 'public',
    'listnews'          => 'public',
    'createnews'        => 'newseditor',
    'modifynews'        => 'newseditor',
    'modifyintroduction' => 'clientadmin',
  );
  
  public $forms = array(
    'createnews'        => 'Visitor\\Organizations\\Form\\Createnews',
    'modifynews'        => 'Visitor\\Organizations\\Form\\Modifynews',
    'modifyintroduction' => 'Visitor\\Organizations\\Form\\Modifyintroduction',
  );
  
  public $paging = array(
    'listnews'          => 'Visitor\\Organizations\\Paging\\Listnews',
  );
  
  public function newsdetailsAction() {
    
    $id        = $this->application->getNumericParameter('id');
    
    if ( $id <= 0 )
      $this->redirectToController('contents', 'http404');
    
    $newsModel = $this->bootstrap->getModel('organizations_news');
    $user      = $this->bootstrap->getSession('user');
    $data      = $newsModel->selectAccessibleNews( $id, $this->organization['id'], $user );
    
    if ( !$data )
      $this->redirectToController('contents', 'http404');
    
    $this->toSmarty['news'] = $data;
    $this->smartyoutput('Visitor/Organizations/Newsdetails.tpl');
    
  }
  
}
