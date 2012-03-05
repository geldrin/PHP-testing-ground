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
  }
  
  public function onComplete() {
    
    $values    = $this->form->getElementValues( 0 );
    $l         = $this->bootstrap->getLocalization();
    $user      = $this->getUser();
    
    $values['timestamp'] = date('Y-m-d H:i:s');
    $values['userid']    = $user->id;
    
    $this->recordingsModel->addComment( $values );
    $this->recordingsModel->updateCommentCount();
    
    $comments     = $recordingsModel->getComments();
    $commentcount = $recordingsModel->getCommentsCount();
    
    $output = array(
      'owncomment'   => true,
      'comments'     => $comments,
      'nocomments'   => $l('recordings', 'nocomments'),
      'commentcount' => $commentcount,
    );
    
    $this->controller->jsonoutput( $output );
    
  }
  
}
