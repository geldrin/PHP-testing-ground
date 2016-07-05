<?php
namespace Visitor\Live\Form;

class Createteacher extends \Visitor\HelpForm {
  public $configfile = 'Createteacher.php';
  public $template   = 'Visitor/genericform.tpl';
  public $user;

  public $feedModel;
  public $invModel;
  public $pin;

  public function init() {
    if ( !$this->controller->organization['islivepinenabled'] )
      $this->controller->redirect('');

    $this->feedModel = $this->controller->modelOrganizationAndUserIDCheck(
      'livefeeds',
      $this->application->getNumericParameter('id')
    );

    $fromInviteID = $this->application->getNumericParameter('frominviteid');

    if ( $fromInviteID ) {
      $this->invModel = $this->controller->modelIDCheck(
        'livefeed_teacherinvites',
        $fromInviteID
      );

      if ( $this->invModel->row['livefeedid'] != $this->feedModel->id )
        $this->controller->redirect('');
    }

    $this->controller->toSmarty['insertbefore'] = 'Visitor/Live/Inviteteachers_before.tpl';

    parent::init();

  }

  public function values() {
    $this->pin = $this->feedModel->row['pin'];
    if ( !$this->invModel )
      return;

    $this->pin = $this->invModel->row['pin'];
    $this->values['emails'] = $this->invModel->row['emails'];
    // TODO users info
    $this->values['userids'] = $this->invModel->row['userids'];
  }

  public function onComplete() {

    $values = $this->form->getElementValues( 0 );
    // masoljuk rogton a pint
    $values['pin'] = $this->feedModel->row['pin'];

    $this->invModel->insert( $values );
    $this->sendEmails();
  }

  private function sendEmails() {
    // TODO
  }
}
