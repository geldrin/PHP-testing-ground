<?php
namespace Visitor\Live\Form;

class Analytics extends \Visitor\HelpForm {
  public $configfile = 'Analytics.php';
  public $template   = 'Visitor/Live/Analytics.tpl';
  public $needdb     = true;
  
  protected $channelModel;
  protected $feedModel;
  
  private function validateDateTime( $value, $default = null ) {

    if ( !preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:{2}$/', $value ) )
      return $default;

  }

  public function init() {

    $this->channelModel = $this->controller->modelOrganizationAndUserIDCheck(
      'channels',
      $this->application->getNumericParameter('id')
    );

    /*
    nem nezzuk hogy a channel liveevent e, Andras keresere
    if ( !$this->channelModel->row['isliveevent'] )
      $this->controller->redirect('');
    */

    $feeds   = $this->channelModel->getFeeds();
    $feedids = $this->application->getParameter('feedids', array() );
    $starttime = $this->application->getParameter(
      'starttimestamp',
      substr( $this->channelModel->row['starttimestamp'], 0, 16 )
    );
    $endtime = $this->application->getParameter(
      'endtimestamp',
      substr( $this->channelModel->row['endtimestamp'], 0, 16 )
    );

    // sanitize the feedids
    foreach( $feedids as $k => $v ) {

      if ( !isset( $feeds[ $v ] ) )
        unset( $feedids[ $k ] );

    }

    if ( empty( $feedids ) )
      $this->controller->redirect('');

    $filter = array(
      'starttimestamp' => $starttime,
      'endtimestamp'   => $endtime,
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
    $this->form->method = 'GET';

  }
  
  public function onComplete() {

    $values   = $this->form->getElementValues( 0 );
    /*
    $this->controller->redirect(
      $this->application->getParameter(
        'forward',
        'live/managefeeds/' . $this->channelModel->id
      )
    );
    */

  }
  
}
