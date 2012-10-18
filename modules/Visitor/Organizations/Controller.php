<?php
namespace Visitor\Organizations;

class Controller extends \Visitor\Controller {
  public $permissions = array(
    'index'             => 'public',
    'newsdetails'       => 'public',
    'listnews'          => 'public',
    'newsrss'           => 'public',
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
  
  public function newsrssAction() {
    
    header("Content-type: text/xml; charset=utf-8");
    
    $cache = $this->bootstrap->getCache( 'rss_news_' . $this->organization['id'] );
    if ( !$cache->expired() and PRODUCTION ) {
      
      $this->output( $cache->get() );
      return;
      
    }
    
    $l         = $this->bootstrap->getLocalization();
    $newsModel = $this->bootstrap->getModel('organizations_news');
    $items     = $newsModel->getRecentNews( 10, $this->organization['id'] );
    
    $this->toSmarty['builddate'] = $this->getBuildDate( current( $items ) );
    $this->toSmarty['pubdate']   = date('r');
    $this->toSmarty['items']     = $items;
    
    $output = $this->fetchSmarty('Visitor/Organizations/Newsrss.tpl');
    $cache->put( $output );
    $this->output( $output );
    
  }
  
  protected function getBuildDate( $item ) {
    
    if ( @$item['starts'] )
      $timestamp = $item['starts'];
    else
      $timestamp = $item['timestamp'];
    
    return date('r', strtotime( $timestamp ) );
    
  }

}
