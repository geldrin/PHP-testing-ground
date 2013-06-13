<?php
namespace Admin;
class Mailqueue extends \Springboard\Controller\Admin {
  public $sendcount = 0;
  
  public function init() {
    
    $this->permissions['send']           =
    $this->permissions['runqueue']       =
    $this->permissions['view']           =
    $this->permissions['changeform']     =
    $this->permissions['changemultiple'] =
    $this->permissions['removeform']     =
    $this->permissions['removemultiple'] =
      'admin'
    ;
    
    $this->formactions[] = 'changeform';
    $this->formactions[] = 'changemultiple';
    
    $this->formactions[] = 'removeform';
    $this->formactions[] = 'removemultiple';
    
    parent::init();
    
  }
  
  public function viewAction() {
    
    $queueModel = $this->modelIDCheck(
      'mailqueue',
      $this->application->getNumericParameter('id')
    );
    
    $this->toSmarty['hidesidebar'] = true;
    $this->toSmarty['hideheading'] = true;
    $this->toSmarty['mail']        = $queueModel->row;
    
    if ( $this->toSmarty['mail'] )
      $this->toSmarty['mail']['headers'] = unserialize( $this->toSmarty['mail']['headers'] );
    
    $this->smartyOutput('Admin/mailqueue_view.tpl');
    
  }
  
  public function sendAction() {
    
    $l          = $this->bootstrap->getLocalization();
    $queueModel = $this->bootstrap->getModel('mailqueue');
    $this->statuses  = $l->getLov('mailqueueerrors');
    $this->sendcount = $queueModel->getSendCount();
    
    if ( !$this->sendcount ) {
      
      $this->toSmarty['hidesidebar'] = true;
      $this->toSmarty['hideheading'] = true;
      $this->toSmarty['listing']     = $l('admin', 'emptymailqueue');
      $this->smartyOutput('Admin/listing.tpl');
      
    }
    
    $queue = $this->bootstrap->getMailqueue();
    $queue->observer = $this;
    echo '<html><body onload="location.reload( true );"><pre>';
    $queue->send();
    echo "</pre></body></html>";
    
  }
  
  // mailqueue->observer->observe hivja meg
  public function observe( $counter, $email, $status, $errormessage ) {
    
    $percent = 100 - round( $counter / $this->sendcount * 100 );
    
    echo
      str_pad( $counter, 6, ' ', STR_PAD_LEFT ), '. ',
      '[', str_pad( $percent, 3, ' ', STR_PAD_LEFT ) . '%] ',
      str_pad( $email,   40 ), ' ',
      strip_tags( $this->statuses[ $status ] ), ' ', $errormessage,
      "<br />"
    ;
    flush();
    
  }
  
  public function runqueueAction() {
    
    $this->loadConfigFile( $this->configfile );
    $this->preparePage();
    
    $this->toSmarty['listing'] = '
      <iframe width="1000" height="380" '.
        'src="mailqueue/send?tstamp=' . time() . '"></iframe>'
    ;
    $this->smartyOutput('Admin/listing.tpl');
    
  }
  
  public function preparePage() {
    
    parent::preparePage();
    
    if ( $this->toSmarty['navigation'] )
      array_shift( $this->toSmarty['navigation'] ); // az uj tetel felvitele feliratot szuntetjuk meg ezzel
    
  }
  
}
