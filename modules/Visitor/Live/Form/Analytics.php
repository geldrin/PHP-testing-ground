<?php
namespace Visitor\Live\Form;

class Analytics extends \Visitor\HelpForm {
  public $configfile = 'Analytics.php';
  public $template   = 'Visitor/Live/Analytics.tpl';
  public $needdb     = true;
  
  protected $channelModel;
  protected $feedModel;
  protected $feedids = array();
  protected $feeds = array();

  private function validateDateTime( $value, $default = null ) {

    if ( !preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:{2}$/', $value ) )
      return $default;

    return $value;

  }

  public function init() {

    $this->channelModel = $this->controller->modelOrganizationAndUserIDCheck(
      'channels',
      $this->application->getNumericParameter('id')
    );
    $this->feedModel = $this->bootstrap->getModel('livefeeds');

    /*
    nem nezzuk hogy a channel liveevent e, Andras keresere
    if ( !$this->channelModel->row['isliveevent'] )
      $this->controller->redirect('');
    */

    $feeds   = $this->channelModel->getFeeds();
    foreach( $feeds as $feed ) {
      $this->feeds[ $feed['id'] ] = $feed['name'];
      $this->feedids[] = $feed['id'];
    }

    // sanitize the feedids
    $feedids = $this->application->getParameter('feedids', $this->feedids );
    foreach( $feedids as $k => $v ) {

      if ( !isset( $feeds[ $v ] ) )
        unset( $feedids[ $k ] );

    }

    $goodstarttime = substr( $this->channelModel->row['starttimestamp'], 0, 16 );
    $starttime     = $this-> validateDateTime( $this->application->getParameter(
        'starttimestamp',
        $goodstarttime
      ),
      $goodstarttime
    );
    $goodendtime = substr( $this->channelModel->row['endtimestamp'], 0, 16 );
    $endtime     = $this->validateDateTime( $this->application->getParameter(
        'endtimestamp',
        $goodendtime
      ),
      $goodendtime
    );

    if ( empty( $feedids ) )
      $this->controller->redirect('');

    $filter = array(
      'originalstarttimestamp' => $this->channelModel->row['starttimestamp'],
      'originalendtimestamp'   => $this->channelModel->row['endtimestamp'],
      'starttimestamp' => $starttime,
      'endtimestamp'   => $endtime,
      'livefeedids'    => $feedids,
      'resolution'     => $this->application->getNumericParameter('resolution',
        $this->feedModel->getMinStep( $starttime, $endtime )
      ),
    );
    $data   = $this->feedModel->getStatistics( $filter );

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
