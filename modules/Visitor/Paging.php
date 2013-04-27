<?php
namespace Visitor;
abstract class Paging extends \Springboard\Controller\Paging {
  protected $pagestoshow = 5;
  
  public function redirectToMainDomain() {}
  
  protected function setupPager() {
    
    parent::setupPager();
    
    if ( $this->pager ) {
      
      $this->pager->perpageformmethod = 'get';
      $this->pager->divider = ' <span class="divider">|</span>';
      
    }
    
  }
  
  protected function needPager() {
    
    if ( $this->perpageselector )
      return true;
    
    if ( $this->itemcount > $this->perpage )
      return true;
    else
      return false;
    
  }
  
}
