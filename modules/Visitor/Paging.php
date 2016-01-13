<?php
namespace Visitor;
abstract class Paging extends \Springboard\Controller\Paging {
  protected $pagestoshow = 5;
  protected $ignoreSortKeys = array();
  protected $sortLocaleModule;
  protected $perpageselector = false;

  public function redirectToMainDomain() {}
  
  protected function setupPager() {
    
    parent::setupPager();
    
    if ( $this->pager ) {
      
      $this->pager->perpageformmethod = 'get';
      $this->pager->divider = ' <span class="divider"></span>';
      
    }
    
  }
  
  protected function needPager() {
    
    if ( $this->perpageselector and $this->itemcount )
      return true;
    
    if ( $this->itemcount > $this->perpage and $this->itemcount )
      return true;
    else
      return false;
    
  }
  
  protected function display() {
    $this->prepareSortTemplate();
    parent::display();
  }
  
  protected function prepareSortTemplate() {
    $this->bootstrap->includeTemplatePlugin('sortarrows');
    $l = $this->bootstrap->getLocalization();
    // setupOrder le kelett fusson
    $current = $this->orderkey;
    $module  = $this->sortLocaleModule ?: $this->controller->module;
    $orders  = array(
      'items'       => array(),
      'activeKey'   => $current,
      'activeLabel' => smarty_modifier_sortarrows(
        $l( $module, 'sort_' . $current ),
        null,
        $current,
        $current
      ),
    );

    foreach( $this->sort as $key => $value ) {
      if ( isset( $this->ignoreSortKeys[ $key ] ) )
        continue;

      $orders['items'][] = array(
        'sortkey' => $key,
        'label'   => smarty_modifier_sortarrows(
          $l( $module, 'sort_' . $key ),
          null,
          $key,
          $current
        ),
      );
    }

    $this->controller->toSmarty['orders'] = $orders;
  }
}
