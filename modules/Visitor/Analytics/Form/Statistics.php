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
    
    $filename = 'videosquare-statistics-' . date('YmdHis') . '.csv';
    \Springboard\Browser::downloadHeaders( $filename, 'text/csv' );

    $f = fopen('php://output', 'w');
    fputcsv(
      $f,
      array(
        'userId',
        'userEmail',
        'recordingId',
        'recordingTitle',
        'recordingLength',
        'totalWatchedPercent',
        'totalCompleted',
        'sessionWatchedDurationSeconds',
        'sessionWatchedPercent',
        'sessionWatchedFromSeconds',
        'sessionWatchedUntilSeconds',
        'sessionWatchedFromTimestamp',
        'sessionWatchedUntilTimestamp',
      ),
      $this->delimiter
    );

    foreach( $data as $row )
      fputcsv(
        $f,
        array(
          $row['userid'],
          $row['email'],
          $row['recordingid'],
          $row['title'],
          $row['recordinglength'],
          $row['totalwatchedpercent'],
          $row['totalcompleted'],
          $row['sessionwatchedduration'],
          $row['sessionwatchedpercent'],
          $row['sessionwatchedfrom'],
          $row['sessionwatcheduntil'],
          $row['sessionwatchedtimestampfrom'],
          $row['sessionwatchedtimestampuntil'],
        ),
        $this->delimiter
      );

    fclose( $f );
    die();

  }

}
