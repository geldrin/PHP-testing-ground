<?php
namespace Visitor\Recordings\Form;

class Modifysharing extends \Visitor\Recordings\ModifyForm {
  public $configfile   = 'Modifysharing.php';
  public $template     = 'Visitor/genericform.tpl';
  public $needdb       = true;
  
  public function init() {
    
    parent::init();
    
    if ( $this->recordingsModel->row['visiblefrom'] )
      $this->values['wanttimelimit'] = 1;
    
    if ( $this->values['visiblefrom'] )
      $this->values['visiblefrom']  = substr( $this->values['visiblefrom'], 0, 10 );
    else
      unset( $this->values['visiblefrom'] );
    
    if ( $this->values['visibleuntil'] )
      $this->values['visibleuntil'] = substr( $this->values['visibleuntil'], 0, 10 );
    else
      unset( $this->values['visibleuntil'] );

    if ( $this->values['featureduntil'] )
      $this->values['featureduntil'] = substr( $this->values['featureduntil'], 0, 16 );
    else
      unset( $this->values['featureduntil'] );

  }
  
  public function onComplete() {
    
    $values = $this->form->getElementValues( 0 );
    $oldapprovalstatus = $this->recordingsModel->row['approvalstatus'];

    if ( !$values['wanttimelimit'] )
      $values['visibleuntil'] = $values['visiblefrom'] = null;

    if ( !$values['isfeatured'] or !$values['featureduntil'] )
      $values['featureduntil'] = null;

    $this->handleAccesstypeForModel( $this->recordingsModel, $values );
    unset( $values['departments'], $values['groups'] );
    $this->recordingsModel->updateRow( $values );
    $this->recordingsModel->updateFulltextCache( true );
    $this->recordingsModel->updateChannelIndexPhotos(); // a channel szamlalok miatt
    $this->recordingsModel->updateCategoryCounters();
    
    if (
         $oldapprovalstatus != $this->recordingsModel->row['approvalstatus'] and
         $this->recordingsModel->row['approvalstatus'] == 'pending'
       ) {

      $l         = $this->bootstrap->getLocalization();
      $user      = $this->bootstrap->getSession('user');
      $userModel = $this->bootstrap->getModel('users');
      $this->controller->toSmarty['user']      = $user->toArray();
      $this->controller->toSmarty['recording'] = $this->recordingsModel->row;
      $title = sprintf(
        $l('recordings', 'approvalstatus_subject'),
        $this->recordingsModel->row['title']
      );
      $body  = $this->controller->fetchSmarty('Visitor/Recordings/Email/Approvalneeded.tpl');
      $users = $userModel->getUsersWithPermission(
        "editor",
        $user['id'],
        $this->controller->organization['id']
      );

      foreach( $users as $user )
        $this->controller->sendOrganizationHTMLEmail( $user['email'], $title, $body );

    }

    $this->controller->redirect(
      $this->application->getParameter('forward', 'recordings/myrecordings')
    );
    
  }
  
}
