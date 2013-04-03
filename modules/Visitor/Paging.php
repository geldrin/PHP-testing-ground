<?php
namespace Visitor;
abstract class Paging extends \Springboard\Controller\Paging {
  protected $pagestoshow = 5;
  
  public function redirectToMainDomain() {}
  
  protected function setupPager() {
    
    parent::setupPager();
    
    if ( $this->pager )
      $this->pager->divider = ' <span class="divider">|</span>';
    
  }
  
}
