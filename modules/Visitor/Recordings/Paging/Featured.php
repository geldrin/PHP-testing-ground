<?php
namespace Visitor\Recordings\Paging;

class Featured extends \Visitor\Paging {
  protected $orderkey = 'timestamp_desc';
  protected $sort     = Array(
    'timestamp_desc'       => 'timestamp DESC',
    'timestamp'            => 'timestamp',
    'views_desc'           => 'numberofviews DESC',
    'views'                => 'numberofviews',
    'viewsthisweek_desc'   => 'numberofviewsthisweek DESC',
    'viewsthisweek'        => 'numberofviewsthisweek',
    'viewsthismonth_desc'  => 'numberofviewsthismonth DESC',
    'viewsthismonth'       => 'numberofviewsthismonth',
    'rating_desc'          => 'rating DESC, numberofratings DESC',
    'rating'               => 'rating, numberofratings DESC',
    'ratingthisweek_desc'  => 'ratingthisweek DESC, numberofratings DESC',
    'ratingthisweek'       => 'ratingthisweek, numberofratings DESC',
    'ratingthismonth_desc' => 'ratingthismonth DESC, numberofratings DESC',
    'ratingthismonth'      => 'ratingthismonth, numberofratings DESC',
  );
  protected $template = 'Visitor/recordinglistitem.tpl';
  protected $insertbeforepager = Array( 'Visitor/Recordings/Paging/FeaturedBeforepager.tpl' );
  protected $recordingsModel;
  protected $filter = '';
  protected $type   = '';
  protected $user;
  
  public function init() {
    
    $l                 = $this->bootstrap->getLocalization();
    $this->foreachelse = $l('recordings', 'foreachelse');
    $this->filter      = "r.organizationid = '" . $this->controller->organization['id'] . "'";
    $this->user        = $this->bootstrap->getSession('user');
    
    $this->type = $this->application->getParameter('subaction', 'featured');
    switch( $this->type ) {
      case 'newest':
        $this->orderkey = 'timestamp_desc';
      break;
      
      case 'highestrated':
        $this->orderkey = 'rating';
      break;
      
      case 'mostviewed':
        $this->orderkey = 'views_desc';
      break;
      
      case 'featured':
      default:
        $this->type    = 'featured';
        $this->filter .= " AND r.isfeatured = '1'";
        break;
      
    }
    
    $this->title = $l('recordings', 'featured_' . $this->type );
    $this->controller->toSmarty['listclass'] = 'recordinglist';
    $this->controller->toSmarty['type']      = $this->type;
    $this->controller->toSmarty['module']    = 'featured';
    if ( $this->type == 'featured' ) {
      $this->controller->toSmarty['form']    = $this->getSearchForm()->getHTML();
    }
    parent::init();
    
  }
  
  protected function setupCount() {
    
    $this->recordingsModel = $this->bootstrap->getModel('recordings');
    $this->itemcount = $this->recordingsModel->getRecordingsCount(
      $this->filter, $this->user, $this->controller->organization['id']
    );
    
  }
  
  protected function getItems( $start, $limit, $orderby ) {
    
    $items = $this->recordingsModel->getRecordingsWithUsers(
      $start, $limit, $this->filter, $orderby,
      $this->user, $this->controller->organization['id']
    );
    return $items;
    
  }
  
  protected function getUrl() {
    return
      $this->controller->getUrlFromFragment( $this->module . '/' . $this->action ) .
      '/' . $this->type
    ;
  }
  
  protected function getSearchForm() {

    $l    = $this->bootstrap->getLocalization();
    $form = $this->bootstrap->getForm(
      'recordingssearchform',
      \Springboard\Language::get() . '/recordings/togglefeatured',
      'post'
    );
    
    $form->jspath =
      $this->controller->toSmarty['STATIC_URI'] . 'js/clonefish.js'
    ;
    
    $form->messagecontainerlayout =
      '<div class="formerrors"><ul>%s</ul></div>'
    ;
    
    $form->messageprefix = '';
    $form->layouts['tabular']['container']  =
      "<table cellpadding=\"0\" cellspacing=\"0\" class=\"formtable\">\n%s\n</table>\n"
    ;
    
    $form->layouts['tabular']['element'] =
      '<tr %errorstyle%>' .
        '<td class="labelcolumn">' .
          '<label for="%id%">%displayname%</label>' .
        '</td>' .
        '<td class="elementcolumn">%prefix%%element%%postfix%%errordiv%</td>' .
      '</tr>'
    ;
    
    $form->layouts['tabular']['buttonrow'] =
      '<tr class="buttonrow"><td colspan="2">%s</td></tr>'
    ;
    
    $form->layouts['tabular']['button'] =
      '<input type="submit" value="%s" class="submitbutton" />'
    ;
    
    $form->layouts['rowbyrow']['errordiv'] =
      '<div id="%divid%" style="display: none; visibility: hidden; ' .
      'padding: 2px 5px 2px 5px; background-color: #d03030; color: white;' .
      'clear: both;"></div>'
    ;
    
    $configfile =
      $this->application->config['modulepath'] .
      'Visitor/Recordings/Form/Configs/Recordingssearch.php'
    ;
    
    $values = $this->application->getParameters();
    include( $configfile ); // innen jon a $config
    
    $form->addElements( $config, $values, false );
    
    return $form;
    
  }
}
