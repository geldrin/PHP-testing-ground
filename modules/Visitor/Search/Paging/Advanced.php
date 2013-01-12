<?php
namespace Visitor\Search\Paging;
class Advanced extends \Visitor\Paging {
  protected $orderkey = 'relevancy_desc';
  protected $sort = array(
    'relevancy_desc'         => 'relevancy DESC',
    'recordedtimestamp_desc' => 'recordedtimestamp DESC',
    'recordedtimestamp'      => 'recordedtimestamp',
  );
  
  protected $insertbeforepager = Array( 'Visitor/Search/Paging/AdvancedBeforepager.tpl' );
  protected $template = 'Visitor/Search/Paging/Advanced.tpl';
  protected $recordingsModel;
  protected $user;
  
  public function init() {
    
    $l                 = $this->bootstrap->getLocalization();
    $this->user        = $this->bootstrap->getSession('user');
    $this->foreachelse = $l('', 'foreachelse');
    $this->title       = $l('search', 'advanced_title');
    $this->controller->toSmarty['listclass'] = 'recordinglist';
    $this->controller->toSmarty['form']      =
      $this->getForm()->getHTML()
    ;
    parent::init();
    
    $this->searchterms = $this->getSearchParams();
    
    if ( !$this->searchterms['q'] )
     $this->foreachelse = $l('search', 'search_minimum_3chars');
    
  }
  
  protected function setupPager() {
    parent::setupPager();
    
    foreach( $this->searchterms as $key => $value ) {
      
      if ( $value )
        $this->pager->pass( $key, $value );
      
    }
    
  }
  
  protected function setupCount() {
    
    if ( !$this->searchterms['q'] )
      return $this->itemcount = 0;
    
    $this->recordingsModel = $this->bootstrap->getModel('recordings');
    
    return $this->itemcount = $this->recordingsModel->getSearchAdvancedCount(
      $this->user,
      $this->controller->organization['id'],
      $this->searchterms
    );
    
  }
  
  protected function getItems( $start, $limit, $orderby ) {
    
    if ( !$this->searchterms['q'] )
      return array();
    
    $items = $this->recordingsModel->getSearchAdvancedArray(
      $this->user,
      $this->controller->organization['id'],
      $this->searchterms,
      $start, $limit, $orderby
    );
    
    $items = $this->recordingsModel->addPresentersToArray( $items );
    
    return $items;
    
  }
  
  public function getForm() {
    
    $l    = $this->bootstrap->getLocalization();
    $form = $this->bootstrap->getForm(
      'search_advanced',
      \Springboard\Language::get() . '/search/advanced',
      'get',
      $this->bootstrap->getAdoDB(),
      'adodb'
    );
    
    $form->jspath = 'js/clonefish.js';
    
    $form->jspath =
      $this->controller->toSmarty['STATIC_URI'] . 'js/clonefish.js'
    ;
    
    $form->messagecontainerlayout =
      '<div class="formerrors"><ul>%s</ul></div>'
    ;
    
    $form->messageprefix = '';
    $form->submit        = $l('search', 'submit');
    
    $form->layouts['tabular']['container']  =
      "<table cellpadding=\"0\" cellspacing=\"0\" class=\"searchtable\">\n%s\n</table>\n"
    ;
    
    $form->layouts['tabular']['element'] =
      '<div class="element">%prefix%%element%%postfix%%errordiv%</div>'
    ;
    
    $form->layouts['tabular']['buttonrow'] =
      '<tr class="buttonrow"><td colspan="3">%s</td></tr>'
    ;
    
    $form->layouts['tabular']['button'] =
      '<input type="submit" value="%s" class="submitbutton" />'
    ;
    
    $configfile =
      $this->application->config['modulepath'] .
      'Visitor/Search/Form/Configs/Advanced.php'
    ;
    
    $values = $this->application->getParameters();
    include( $configfile ); // innen jon a $config
    
    $form->addElements( $config, $values, false );
    
    return $form;
    
    
  }
  
  public function getSearchParams() {
    
    $params = array(
      'q'               => '',
      'category'        => '',
      'languages'       => '',
      'department'      => '',
      'category'        => '',
      'wholeword'       => 0,
      'uploaddatefrom'  => '',
      'uploaddateto'    => '',
      'createdatefrom'  => '',
      'createdateto'    => '',
      'contributorname' => '',
      'contributorjob'  => '',
      'contributororganization' => '',
    );
    
    foreach( $params as $key => $value )
      $params[ $key ] = $this->application->getParameter( $key );
    
    $l = $this->bootstrap->getLocalization();
    $checkparams = array(
      'q',
      'uploaddatefrom',
      'uploaddateto',
      'createdatefrom',
      'createdateto',
      'contributorname',
      'contributorjob',
      'contributororganization',
    );
    
    foreach( $params as $key => $value ) {
      
      if ( in_array( $key, $checkparams ) and $value == $l('search', $key ) )
        $params[ $key ] = '';
      
    }
    
    return $params;
    
  }
  
}
