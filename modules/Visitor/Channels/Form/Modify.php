<?php
namespace Visitor\Channels\Form;

class Modify extends \Visitor\HelpForm {
  public $configfile = 'Modify.php';
  public $template   = 'Visitor/genericform.tpl';
  public $needdb     = true;
  public $channelroot;

  protected $parentchannelModel;
  protected $channelModel;

  public function init() {

    $this->channelModel = $this->controller->modelOrganizationAndUserIDCheck(
      'channels',
      $this->application->getNumericParameter('id')
    );

    $this->values = $this->channelModel->row;

    if ( $this->channelModel->row['parentid'] ) {

      $this->parentchannelModel = $this->controller->modelOrganizationAndUserIDCheck(
        'channels',
        $this->channelModel->row['parentid']
      );

      $this->channelroot = $this->parentchannelModel->findRoot( $this->parentchannelModel->row );
      $this->values['accesstype'] = $this->channelroot['accesstype'];

    }

    $this->controller->toSmarty['formclass'] = 'leftdoublebox';
    $this->controller->toSmarty['helpclass'] = 'rightbox small';

  }

  public function postSetupForm() {

    $l = $this->bootstrap->getLocalization();
    $this->controller->toSmarty['title'] = $l('channels', 'modify_title');

  }

  public function onComplete() {

    $values = $this->form->getElementValues( 0 );

    // mivel az accesstype disable-zve van ha van szulo ezert nem kuldi el nekunk
    // a browser az inputokat, csak akkor kezeljuk ha nincs szulo
    if (
         !$this->channelModel->row['parentid'] and
         isset( $values['accesstype'] ) and $values['accesstype']
       )
      $this->handleAccesstypeForModel( $this->channelModel, $values );

    unset( $values['departments'], $values['groups'] );
    $this->channelModel->updateRow( $values );
    $this->channelModel->updateModification();

    $this->controller->redirect(
      $this->application->getParameter('forward', 'channels/mychannels')
    );

  }

}
