<?php
namespace Visitor\Live\Form;


class Inviteteachers extends \Visitor\HelpForm {
  public $configfile = 'Inviteteachers.php';
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

    $l = $this->bootstrap->getLocalization();
    $this->controller->toSmarty['title'] = $l('live', 'inviteteachers');

    $this->controller->toSmarty['feed'] = $this->feedModel->row;
    $this->controller->toSmarty['needselect2'] = true;
    $this->controller->toSmarty['formclass'] = 'leftdoublebox';
    $this->controller->toSmarty['helpclass'] = 'rightbox';
    $this->controller->toSmarty['insertbefore'] = 'Visitor/Live/Inviteteachers_before.tpl';

    $this->pin = $this->feedModel->row['pin'];

    if ( $this->invModel )
      $this->pin = $this->invModel->row['pin'];

    parent::init();
  }

  public function onComplete() {
    $values = $this->form->getElementValues( 0 );
    $row = array(
      'livefeedid' => $this->feedModel->id,
      'pin' => $this->feedModel->row['pin'],
      'timestamp' => date('Y-m-d H:i:s'),
    );

    $emails = $this->validateAndGetEmails();
    $row['emails'] = implode("\n", $emails );

    if ( !empty( $this->form->getMessages() ) )
      return;

    $users = $this->validateAndGetUsers( $emails );
    $userids = array();
    foreach( $users as $user ) {
      $userids[] = $user['id'];
      $emails[] = $user['email'];
    }
    $row['userids'] = implode(',', $userids );

    $invModel = $this->bootstrap->getModel('livefeed_teacherinvites');
    $invModel->insert( $row );

    $this->sendEmails( $row['pin'], $emails );
    $this->redirect(
      $this->application->getParameter(
        'forward',
        'live/teacherinvites/' . $this->feedModel->id
      )
    );
  }

  private function validateAndGetEmails() {
    $ret = array();
    if ( empty( $_REQUEST['emails'] ) )
      return $ret;

    $emails = \Springboard\Tools::explodeAndTrim(
      "\n",
      $_REQUEST['emails']
    );

    include_once( $this->bootstrap->config['libpath'] . 'clonefish/constants.php');

    $l = $this->bootstrap->getLocalization();
    foreach( $emails as $email ) {
      $email = trim( $email );
      if ( preg_match( CF_EMAIL, $email ) )
        $ret[] = $email;
      else {
        $this->form->addMessage(
          sprintf(
            $l('live', 'teacher_invalidemail'),
            htmlspecialchars( $email, ENT_QUOTES, 'UTF-8' )
          )
        );
        $this->form->invalidate();
      }
    }

    $ret = array_unique( $ret );
    return $ret;
  }

  // ha mar van egy user meghivva adott email-el akkor
  // toroljuk az emails tombbol
  private function validateAndGetUsers( &$emails ) {
    $ret = array();
    if ( empty( $_REQUEST['userids'] ) )
      return $ret;

    $userids = array();
    foreach( $_REQUEST['userids'] as $userid ) {
      $userid = trim( $userid );
      $userid = intval( $userid, 10 );
      if ( $userid )
        $userids[] = $userid;
    }

    $userModel = $this->bootstrap->getModel('users');
    $users = $userModel->getUsersByIDs(
      $userids,
      $this->controller->organization['id'],
      'id, email'
    );
    unset( $userids );

    $emailLookup = array_flip( $emails );
    foreach( $users as $user ) {
      // emailekbol toroljuk a users-ben is megjelenoket
      if ( isset( $emailLookup[ $user['email'] ] ) ) {
        $key = $emailLookup[ $user['email'] ];
        unset( $emails[ $key ] );
      }
    }

    return $users;
  }

  public function getUsers() {
    if ( !$this->invModel )
      return array();

    $this->bootstrap->includeTemplatePlugin('nickformat');
    $userids = \Springboard\Tools::explodeIDs(
      ',',
      $this->invModel->row['userids']
    );
    $userModel = $this->bootstrap->getModel('users');
    $users = $userModel->getUsersByIDs(
      $userids,
      $this->controller->organization['id']
    );

    $userdata = array();
    foreach( $users as $user )
      $userdata[ $user['id'] ] =
        smarty_modifier_nickformat(
          $user, $this->controller->organization
        ) . ' (' . $user['email'] . ')'
      ;

    return $userdata;
  }

  private function sendEmails( $pin, $emails ) {
    $l = $this->bootstrap->getLocalization();
    $this->controller->toSmarty['pin'] = $pin;

    $subject = $l('live', 'inviteemail_subject');
    $body = $this->controller->fetchSmarty(
      'Visitor/Live/Inviteemail.tpl'
    );

    foreach( $emails as $email )
      $this->controller->sendOrganizationHTMLEmail(
        $email,
        $subject,
        $body
      );
  }
}
