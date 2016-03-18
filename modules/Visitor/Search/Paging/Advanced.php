<?php
namespace Visitor\Search\Paging;
class Advanced extends \Visitor\Paging {
  protected $orderkey = 'relevancy_desc';
  protected $sort = array(
    'relevancy_desc'             => 'relevancy DESC',
    'recordedtimestamp_desc'     => 'recordedtimestamp DESC',
    'recordedtimestamp'          => 'recordedtimestamp',
    'uploadedtimestamp'          => 'timestamp',
    'uploadedtimestamp_desc'     => 'timestamp DESC',
    'lastmodifiedtimestamp'      => 'metadataupdatedtimestamp',
    'lastmodifiedtimestamp_desc' => 'metadataupdatedtimestamp DESC',
  );

  protected $insertbeforepager = Array( 'Visitor/Search/Paging/AdvancedBeforepager.tpl' );
  protected $template = 'Visitor/recordinglistitem.tpl';
  protected $recordingsModel;
  protected $user;
  
  public function init() {
    
    $l                 = $this->bootstrap->getLocalization();
    $this->user        = $this->bootstrap->getSession('user');
    $this->foreachelse = $l('', 'foreachelse');
    $this->title       = $l('search', 'advanced_title');
    
    if ( isset( $_REQUEST['s'] ) and $_REQUEST['s'] == 1 ) {
      $form = $this->getForm( $this->application->getParameters() );
      $this->formvalid = $form->validate();
    } else {
      $form = $this->getForm( array() );
      $this->formvalid = false;
    }
    
    $this->controller->toSmarty['needselect2'] = true;
    $this->controller->toSmarty['listclass'] = 'recordinglist';
    $this->controller->toSmarty['form']      =
      $form->getHTML()
    ;
    parent::init();

    $this->searchterms = $this->getSearchParams();
    $this->controller->toSmarty['searchurl'] =
      $this->getUrl() . '?s=1&' . http_build_query( $this->searchterms )
    ;

    if ( !$this->formvalid )
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
    
    if ( !$this->formvalid )
      return $this->itemcount = null;
    
    $this->recordingsModel = $this->bootstrap->getModel('recordings');
    
    return $this->itemcount = $this->recordingsModel->getSearchAdvancedCount(
      $this->user,
      $this->controller->organization['id'],
      $this->searchterms
    );
    
  }
  
  protected function getItems( $start, $limit, $orderby ) {
    
    if ( !$this->formvalid )
      return array();
    
    $items = $this->recordingsModel->getSearchAdvancedArray(
      $this->user,
      $this->controller->organization['id'],
      $this->searchterms,
      $start, $limit, $orderby
    );
    
    $items = $this->recordingsModel->addPresentersToArray(
      $items,
      true,
      $this->controller->organization['id']
    );
    
    return $items;
    
  }
  
  public function getForm( $values ) {
    
    $l    = $this->bootstrap->getLocalization();
    $form = $this->bootstrap->getForm(
      'search_advanced',
      \Springboard\Language::get() . '/search/advanced',
      'get',
      $this->bootstrap->getAdoDB(),
      'adodb'
    );

    $form->jsalert = false;
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
    
    $form->errorstyle = ' class="formerror"';
    $form->layouts['tabular']['errordiv'] = '
      <div id="%divid%" class="formerrordiv"></div>
      <div class="clear"></div>
    ';
    
    $configfile =
      $this->application->config['modulepath'] .
      'Visitor/Search/Form/Configs/Advanced.php'
    ;
    
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
      'createdatefrom',
      'createdateto',
      'uploaddatefrom',
      'uploaddateto',
    );
    
    foreach( $params as $key => $value ) {
      
      if ( in_array( $key, $checkparams ) and $value == $l('search', $key ) )
        $params[ $key ] = '';
      
    }
    
    return $params;
    
  }
  
  public function checkAdvancedSearchInputs( $q, $contributorjob, $contributororganization, $contributorname ) {
    
    $l = $this->bootstrap->getLocalization();
    if (
        (
          strlen( trim( $q ) ) and
          $q != $l('search', 'q')
        ) or
        (
          strlen( trim( $contributorjob ) ) and
          $contributorjob != $l('search', 'contributorjob')
        ) or
        (
          strlen( trim( $contributororganization ) ) and
          $contributororganization != $l('search', 'contributororganization')
        ) or
        (
          strlen( trim( $contributorname ) ) and
          $contributorname != $l('search', 'contributorname')
        )
      )
      return true;
    else
      return false;
    
  }
  
}
