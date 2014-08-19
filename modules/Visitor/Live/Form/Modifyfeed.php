<?php
namespace Visitor\Live\Form;

class Modifyfeed extends \Visitor\HelpForm {
  public $configfile = 'Modifyfeed.php';
  public $template   = 'Visitor/genericform.tpl';
  public $needdb     = true;
  
  protected $channelModel;
  protected $feedModel;
  protected $streamreclinkid;
  
  public function init() {
    
    $this->feedModel    = $this->controller->modelIDCheck(
      'livefeeds',
      $this->application->getNumericParameter('id')
    );
    
    $this->channelModel = $this->controller->modelOrganizationAndUserIDCheck(
      'channels',
      $this->feedModel->row['channelid']
    );
    
    if ( !$this->channelModel->row['isliveevent'] )
      $this->controller->redirect();
    
    $this->values = $this->feedModel->row;
    
    if ( $this->feedModel->row['feedtype'] == 'vcr' )
      $this->values['recordinglinkid'] = $this->streamreclinkid = $this->feedModel->getVCRReclinkID();
    
    $l = $this->bootstrap->getLocalization();
    $this->controller->toSmarty['title']     = $l('live', 'modifyfeed_title');
    $this->controller->toSmarty['formclass'] = 'leftdoublebox';
    $this->controller->toSmarty['helpclass'] = 'rightbox small';
    
    parent::init();
    
  }
  
  public function onComplete() {
    
    $values       = $this->form->getElementValues( 0 );
    $createstream = false;

    $values['smilstatus']        = 'regenerate';
    $values['contentsmilstatus'] = 'regenerate';

    if ( isset( $values['feedtype'] ) and $this->feedModel->row['feedtype'] != $values['feedtype'] ) {
      
      // minden streamet torlunk, valtozott a feedtype
      $this->feedModel->deleteStreams();
      if ( $values['feedtype'] == 'vcr' ) {
        $this->feedModel->createVCRStream( $values['recordinglinkid'] );
        $this->streamreclinkid = $values['recordinglinkid'];
      } else
        $createstream = true; // es ha elo streamre valtotta at akkor elkuldjuk streamet csinalni
      
    } elseif ( !isset( $values['feedtype'] ) )
      $createstream = false;
      
    $this->handleAccesstypeForModel( $this->feedModel, $values );
    
    unset( $values['departments'], $values['groups'] );
    
    $this->feedModel->updateRow( $values );
    
    if ( $this->feedModel->row['feedtype'] == 'vcr' ) {
      
      if (
           $this->streamreclinkid != $values['recordinglinkid'] and
           !$this->feedModel->modifyVCRStream( $values['recordinglinkid']
         ) )
        $this->redirectToController('contents', 'live_reclinkid_invalidstatus');
      
      $this->controller->redirect('live/managefeeds/' . $this->channelModel->id );
      
    }
    
    if ( $createstream )
      $this->controller->redirect(
        $this->application->getParameter(
          'forward',
          'live/createstream/' . $this->feedModel->id
        )
      );
    
    $this->controller->redirect(
      $this->application->getParameter(
        'forward',
        'live/managefeeds/' . $this->channelModel->id
      )
    );
    
  }
  
}
