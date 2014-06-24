<?php
namespace Visitor\Recordings\Form;
class Newcomment extends \Visitor\Form {
  public $configfile = 'Newcomment.php';
  public $template   = 'Visitor/genericform.tpl';
  public $recordingsModel;
  
  public function init() {
    
    $this->recordingsModel = $this->controller->modelIDCheck(
      'recordings',
      $this->application->getNumericParameter('id')
    );
    $this->controller->toSmarty['recording'] = $this->recordingsModel->row;

    parent::init();
    
  }
  
  public function onComplete() {
    
    $values = $this->form->getElementValues( 0 );
    $l      = $this->bootstrap->getLocalization();
    $user   = $this->bootstrap->getSession('user');

    $values['timestamp'] = date('Y-m-d H:i:s');
    $values['userid']    = $user['id'];
    $values['ipaddress'] = implode(', ', $this->controller->getIPAddress( true ) );
    
    $comment = $this->recordingsModel->insertComment(
      $values, $this->controller->commentsperpage
    );
    $this->handleEmail( $comment );

    if ( !$this->isAjaxRequest() ) {
      include_once(
        $this->bootstrap->config['templatepath'] .
        'Plugins/modifier.filenameize.php'
      );

      $this->controller->redirect(
        'recordings/details/' . $this->recordingsModel->id . ',' .
        smarty_modifier_filenameize( $this->recordingsModel->row['title'] )
        /* nem supportolja a history.js . '#comment-' . $comment['sequenceid'] */
      );
    }

    $output = $this->controller->getComments(
      $this->recordingsModel,
      -1
    );

    $output['focus'] = $comment['sequenceid'];

    $this->controller->jsonoutput( $output );

  }
  
  private function handleEmail( $comment ) {

    if ( !$this->recordingsModel->row['notifyaboutcomments'] )
      return;

    $l = $this->bootstrap->getLocalization();
    $this->controller->toSmarty['comment'] = $comment;
    $usersModel = $this->bootstrap->getModel('users');
    $usersModel->select( $comment['userid'] );
    $this->controller->toSmarty['commentuser'] = $usersModel->row;

    if ( $comment['replyto'] ) {

      $commentModel = $this->bootstrap->getModel('comments');
      $commentModel->select( $comment['replyto'] );

      // ha sajat magunknak valaszolunk ne kuldjunk emailt
      if ( $commentModel->row['userid'] != $comment['userid'] ) {

        $usersModel->select( $commentModel->row['userid'] );
        // TODO check email is generated or not
        $this->controller->toSmarty['replyuser'] = $usersModel->row;

        $this->controller->sendOrganizationHTMLEmail(
          $usersModel->row['email'],
          $l('recordings', 'comments_reply_subject'),
          $this->controller->fetchSmarty('Visitor/Recordings/Email/Commentsreply.tpl')
        );

      }

    }

    // sajat recordingukra valaszoltunk, ne kuldjunk emailt
    if ( $comment['userid'] == $this->recordingsModel->row['userid'] )
      return;

    $usersModel->select( $this->recordingsModel->row['userid'] );
    $this->controller->toSmarty['recordinguser'] = $usersModel->row;

    $this->controller->sendOrganizationHTMLEmail(
      $usersModel->row['email'],
      $l('recordings', 'comments_new_subject'),
      $this->controller->fetchSmarty('Visitor/Recordings/Email/Commentsnew.tpl')
    );

  }

}
