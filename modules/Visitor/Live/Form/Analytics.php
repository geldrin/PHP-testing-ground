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

    if ( !preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $value ) )
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

    if ( empty( $feedids ) and !$this->controller->isAjaxRequest() )
      $this->controller->redirect('');
    elseif ( empty( $feedids ) )
      $this->jsonOutput( array(
          'status' => 'ERR',
        )
      );

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
      $this->transformStatistics( $data )
    ;

    parent::init();

  }
  
  public function postSetupForm() {
    
    $l = $this->bootstrap->getLocalization();
    $this->controller->toSmarty['title'] = $l('live', 'analytics_title');
    $this->form->method = 'GET';

    $this->form->layouts['tabular']['button'] = '
      <input type="submit" value="' . $l('live', 'analytics_reset') . '" class="submitbutton reset" /><br/>
      <br/>
      <input type="submit" value="%s" class="submitbutton" />
    ';

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

  public function displayForm( $submitted ) {

    if ( $submitted and $this->controller->isAjaxRequest() ) {

      $this->jsonOutput( array(
          'status' => 'OK',
          'data'   => $this->controller->toSmarty['analyticsdata'],
        )
      );

    }

    return parent::displayForm( $submitted );

  }

  public function transformStatistics( $data ) {

    $l          = $this->bootstrap->getLocalization();
    $ret        = array(
      'origstartts'  => strtotime( $data['originalstarttimestamp'] ) * 1000,
      'origendts'    => strtotime( $data['originalendtimestamp'] ) * 1000,
      'startts'      => $data['starttimestamp'] * 1000,
      'endts'        => $data['endtimestamp'] * 1000,
      'stepinterval' => $data['step'] * 1000,
      'labels'       => array(),
      'data'         => array(),
    );

    // prepare the chart labels
    foreach( $data['data'] as $value ) {

      foreach( $value as $field => $v ) {

        $ret['labels'][] = $l('live', 'stats_' . $field );

      }

      break;

    }

    $ret['labels'][] = $l('live', 'stats_sum');

    // prepare the values
    foreach( $data['data'] as $key => $value ) {

      $row = array(
        intval( $value['timestamp'] ) * 1000,
      );

      $sum = 0;
      foreach( $value as $field => $v ) {

        if ( $field == 'timestamp' )
          continue;

        $v = intval( $v );
        $row[] = $v;
        $sum += $v;

      }

      unset( $data['data'][ $key ] );
      $row[] = $sum;
      $ret['data'][] = $row;

    }

    return $ret;

  }

}
