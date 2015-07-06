<?php
namespace Visitor\Analytics\Form;
class Accreditedrecordings extends \Visitor\HelpForm {
  public $configfile = 'Accreditedrecordings.php';
  public $template   = 'Visitor/genericform.tpl';
  public $needdb     = true;
  private $delimiter = ';';

  public function init() {
    
    $this->controller->toSmarty['helpclass'] = 'rightbox halfbox';
    parent::init();
  }
  
  public function postSetupForm() {
    
    $l = $this->bootstrap->getLocalization();
    $this->controller->toSmarty['title'] = $l('users', 'register_title');
    
  }
  
  public function onComplete() {
    $values = $this->form->getElementValues( 0 );
    $progressModel = $this->bootstrap->getModel('recording_view_progress');

    if ( $values['email'] ) {
      $userModel = $this->bootstrap->getModel('users');
      $exists = $userModel->checkEmailAndDisabledStatus(
        $values['email'], '0', $this->controller->organization['id']
      );

      if ( !$exists ) {
        $l = $this->bootstrap->getLocalization();
        $this->form->addMessage(
          $l('analytics','accreditedrecordings_emailnotfoundhelp')
        );
        $this->form->invalidate();
        return;
      }
    }

    $filename = 'videosquare-accreditrecordings-' . date('YmdHis') . '.csv';
    \Springboard\Browser::downloadHeaders( $filename, 'text/csv' );

    $data = $progressModel->getAccreditedDataCursor(
      $this->controller->organization,
      $values
    );

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
