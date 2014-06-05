<?php
namespace Visitor\Live\Form;

class Analytics extends \Visitor\HelpForm {
  public $configfile = 'Analytics.php';
  public $template   = 'Visitor/Live/Analytics.tpl';
  public $needdb     = true;
  
  protected $channelModel;
  protected $feedModel;
  
  public function init() {

    $this->channelModel = $this->controller->modelOrganizationAndUserIDCheck(
      'channels',
      $this->application->getNumericParameter('id')
    );

    if ( !$this->channelModel->row['isliveevent'] )
      $this->controller->redirect('');
    
    $feeds   = $this->channelModel->getFeeds();
    $feedids = $this->application->getParameter('feedids', array() );

    // sanitize the feedids
    foreach( $feedids as $k => $v ) {

      if ( !isset( $feeds[ $v ] ) )
        unset( $feedids[ $k ] );

    }

    if ( empty( $feedids ) )
      $this->controller->redirect('');

    $filter = array(
      'starttimestamp' => $this->channelModel->row['starttimestamp'],
      'endtimestamp'   => $this->channelModel->row['endtimestamp'],
      'livefeedids'    => $feedids,
    );
    $feedModel = $this->bootstrap->getModel('livefeeds');
    $data   = $feedModel->getStatistics( $filter );

    $this->controller->toSmarty['helpclass']     = 'rightbox small';
    $this->controller->toSmarty['channel']       = $this->channelModel->row;
    $this->controller->toSmarty['needanalytics'] = true;
    $this->controller->toSmarty['analyticsdata'] =
      $this->controller->transformStatistics( $data )
    ;

    parent::init();

  }
  
  public function postSetupForm() {
    
    $l = $this->bootstrap->getLocalization();
    $this->controller->toSmarty['title'] = $l('live', 'analytics_title');
    
  }
  
  public function onComplete() {
    
    $values   = $this->form->getElementValues( 0 );
    
    $this->controller->redirect(
      $this->application->getParameter(
        'forward',
        'live/managefeeds/' . $this->channelModel->id
      )
    );
    
  }
  
}
