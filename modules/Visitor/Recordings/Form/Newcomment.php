<?php
namespace Visitor\Recordings\Form;
class Newcomment extends \Visitor\Form {
  public $configfile = 'Newcomment.php';
  public $template   = 'Visitor/genericform.tpl';
  public $recordingsModel;
  private $anonUser;

  public function init() {
    
    $this->recordingsModel = $this->controller->modelIDCheck(
      'recordings',
      $this->application->getNumericParameter('id')
    );
    $this->controller->toSmarty['recording'] = $this->recordingsModel->row;

    $user   = $this->bootstrap->getSession('user');
    if (
         !$this->recordingsModel->row['isanonymouscommentsenabled'] and
         !$user['id']
       )
      return $this->controller->handleAccessFailure('member');

    parent::init();
    
  }
  
  public function onComplete() {

    $values = $this->form->getElementValues( 0 );
    $l      = $this->bootstrap->getLocalization();
    $user   = $this->bootstrap->getSession('user');

    if ( $user['id'] )
      $values['userid'] = $user['id'];
    else {

      $this->anonUser = $this->bootstrap->getSession('anonuser');
      if ( !$this->anonUser['id'] and $this->bootstrap->config['recaptchaenabled'] ) {

        if ( !$values['recaptcharesponse'] )
          $this->jsonOutput( array('status' => 'error', 'error' => 'captcharequired' ) );

        $recaptcha = new \ReCaptcha\ReCaptcha(
          $this->bootstrap->config['recaptchapriv']
        );
        $resp = $recaptcha->verify(
          $values['recaptcharesponse'],
          $this->controller->getIPAddress()
        );

        if ( !$resp->isSuccess() )
          $this->jsonOutput( array(
              'status'     => 'error',
              'error'      => 'captchaerror',
              'errorcodes' => $resp->getErrorCodes(),
            )
          );

      }

      $anonymModel = $this->bootstrap->getModel('anonymous_users');
      $this->anonUser = $anonymModel->getOrInsertUserFromToken();
      $anonuser = $anonymModel->registerForSession();
      $this->controller->toSmarty['anonuser'] = $anonuser->toArray();
      $values['anonymoususerid'] = $anonuser['id'];

    }

    $values['timestamp'] = date('Y-m-d H:i:s');
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
    if ( isset( $comment['userid'] ) ) {
      $usersModel->select( $comment['userid'] );
      $this->controller->toSmarty['commentuser'] = $usersModel->row;
    } else
      $this->controller->toSmarty['commentanonuser'] = $this->anonUser;


    if ( $comment['replyto'] ) {

      $commentModel = $this->bootstrap->getModel('comments');
      $commentModel->select( $comment['replyto'] );

      // ha sajat magunknak valaszolunk ne kuldjunk emailt
      if (
           $commentModel->row['userid'] and
           $commentModel->row['userid'] != $comment['userid']
         ) {

        $usersModel->select( $commentModel->row['userid'] );
        if ( !$usersModel->row['isusergenerated'] ) {
          $this->controller->toSmarty['replyuser'] = $usersModel->row;

          $this->controller->sendOrganizationHTMLEmail(
            $usersModel->row['email'],
            sprintf(
              $l('recordings', 'comments_reply_subject'),
              $this->recordingsModel->row['title']
            ),
            $this->controller->fetchSmarty('Visitor/Recordings/Email/Commentsreply.tpl')
          );
        }

      }

    }

    // sajat recordingukra valaszoltunk, ne kuldjunk emailt
    if (
         isset( $comment['userid'] ) and
         $comment['userid'] == $this->recordingsModel->row['userid']
       )
      return;

    $usersModel->select( $this->recordingsModel->row['userid'] );
    $this->controller->toSmarty['recordinguser'] = $usersModel->row;

    if ( $usersModel->row['isusergenerated'] )
      return;

    $this->controller->sendOrganizationHTMLEmail(
      $usersModel->row['email'],
      sprintf(
        $l('recordings', 'comments_new_subject'),
        $this->recordingsModel->row['title']
      ),
      $this->controller->fetchSmarty('Visitor/Recordings/Email/Commentsnew.tpl')
    );

  }

}
