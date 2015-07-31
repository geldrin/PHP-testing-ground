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

    if ( !empty( $_REQUEST['searchrecordings'] ) )
      $values['recordingids'] = $_REQUEST['searchrecordings'];
    if ( !empty( $_REQUEST['searchlive'] ) )
      $values['livefeedids'] = $_REQUEST['searchlive'];
    if ( !empty( $_REQUEST['searchgroups'] ) )
      $values['groupids'] = $_REQUEST['searchgroups'];
    if ( !empty( $_REQUEST['searchusers'] ) )
      $values['userids'] = $_REQUEST['searchusers'];

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
    \Springboard\Browser::downloadHeaders( $filename, 'text/csv' );

    $fields = array(
      'timestamp'  => 'recordCreationTimestamp',
      'userid'     => 'userId',
      'email'      => 'userEmail',
      'externalid' => 'userExternalId',
    );

    switch( $values['type'] ) {
      case 'recordings':
        $extrafields = array(
          'recordingid'                  => 'recordingId',
          'title'                        => 'recordingTitle',
          'recordinglength'              => 'recordingLength',
          'uploadedtimestamp'            => 'recordingUploadedTimestamp',
          //'presenters'                   => 'recordingPresenters',
          'sessionwatchedduration'       => 'sessionWatchedDurationSeconds',
          'sessionwatchedpercent'        => 'sessionWatchedPercent',
          'sessionwatchedfrom'           => 'sessionWatchedFromSeconds',
          'sessionwatcheduntil'          => 'sessionWatchedUntilSeconds',
          'sessionwatchedtimestampfrom'  => 'sessionWatchedFromTimestamp',
          'sessionwatchedtimestampuntil' => 'sessionWatchedUntilTimestamp',
        );
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
        $fields = array_merge( $fields, $extrafields );

        $livefeedModel = $this->bootstrap->getModel('livefeeds');
        $data = $livefeedModel->getStatisticsData( $values );
        break;
      default:
        throw new \Exception("Unknown type!");
        break;
    }

    $f = fopen('php://output', 'w');
    fputcsv( $f, array_values( $fields ), $this->delimiter );

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
