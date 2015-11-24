<?php
namespace Visitor\Analytics\Form;
class Statistics extends \Visitor\HelpForm {
  public $configfile = 'Statistics.php';
  public $template   = 'Visitor/Analytics/Statistics.tpl';
  public $needdb     = true;
  private $delimiter = ';';

  public function init() {

    $this->controller->toSmarty['helpclass'] = 'rightbox halfbox';
    parent::init();
  }

  public function postSetupForm() {

    $l = $this->bootstrap->getLocalization();
    $this->controller->toSmarty['title'] = $l('analytics', 'statistics_title');

  }

  public function onComplete() {
    $values = $this->form->getElementValues( 0 );

    $values['organizationid'] = $this->controller->organization['id'];
    $values['recordingids'] = !empty( $_REQUEST['searchrecordings'] )
      ? $_REQUEST['searchrecordings']
      : array()
    ;

    $values['livefeedids'] = !empty( $_REQUEST['searchlive'] )
      ? $_REQUEST['searchlive']
      : array()
    ;

    $values['groupids'] = !empty( $_REQUEST['searchgroups'] )
      ? $_REQUEST['searchgroups']
      : array()
    ;

    $values['userids'] = !empty( $_REQUEST['searchusers'] )
      ? $_REQUEST['searchusers']
      : array()
    ;

    // sanitize
    foreach( array('recordingids', 'livefeedids', 'groupids', 'userids') as $field ) {
      foreach( $values[ $field ] as $key => $value ) {
        $value = intval( $value );
        if ( $value <= 0 )
          unset( $values[ $field ][ $key ] );
        else
          $values[ $field ][ $key ] = $value;
      }
    }

    $filename =
      'videosquare-statistics-' . $values['type'] . '-' . date('YmdHis') . '.csv'
    ;

    $fields = array(
      'userid'     => 'userId',
      'email'      => 'userEmail',
      'externalid' => 'userExternalId',
      'viewsessionid' => 'sessionID',
    );

    switch( $values['type'] ) {
      case 'recordings':
        $extrafields = array(
          'recordingid'             => 'recordingId',
          'title'                   => 'recordingTitle',
          'recordinglength'         => 'recordingLength',
          'sessionwatchedduration'  => 'sessionWatchedDurationSeconds',
          'sessionwatchedfrom'      => 'sessionWatchedFromSeconds',
          'sessionwatcheduntil'     => 'sessionWatchedUntilSeconds',
          'sessionwatchedtimestamp' => 'sessionWatchedTimestamp',
        );
        if ( $values['extrainfo'] ) {
          $extrafields['sessionipaddress'] = 'sessionIPAddress';
          $extrafields['sessionuseragent'] = 'sessionUserAgent';
        }

        $fields = array_merge( $fields, $extrafields );

        $recordingsModel = $this->bootstrap->getModel('recordings');

        $data = $recordingsModel->getStatistics( $values );
        break;
      case 'live':
        $extrafields = array(
          'channelid'           => 'eventId',
          'title'               => 'eventTitle',
          'starttimestamp'      => 'eventStartTimestamp',
          'endtimestamp'        => 'eventEndTimestamp',
          'watchstarttimestamp' => 'watchStartTimestamp',
          'watchendtimestamp'   => 'watchEndTimestamp',
          'watchduration'       => 'watchDurationSeconds',
        );
        if ( $values['extrainfo'] ) {
          $extrafields['sessionipaddress'] = 'sessionIPAddress';
          $extrafields['sessionuseragent'] = 'sessionUserAgent';
        }

        $fields = array_merge( $fields, $extrafields );

        $livefeedModel = $this->bootstrap->getModel('livefeeds');
        $data = $livefeedModel->getStatisticsData( $values );
        break;
      default:
        throw new \Exception("Unknown type!");
        break;
    }

    $f = \Springboard\Browser::initCSVHeaders(
      $filename,
      array_values( $fields ),
      $this->delimiter
    );

    foreach( $data as $row ) {
      $csvdata = array();
      foreach( $fields as $key => $field )
        $csvdata[] = $row[ $key ];

      fputcsv( $f, $csvdata, $this->delimiter );
    }

    fclose( $f );
    die();

  }

}
