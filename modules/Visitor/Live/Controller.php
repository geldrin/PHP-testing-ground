<?php
namespace Visitor\Live;

class Controller extends \Visitor\Controller {
  public $permissions = array(
    'index'                => 'public',
    'details'              => 'public',
    'view'                 => 'public',
    'myrecordings'         => 'uploader',
    'modifybasics'         => 'uploader',
    'modifyclassification' => 'uploader',
    'modifydescription'    => 'uploader',
    'modifysharing'        => 'uploader',
    'delete'               => 'uploader',
    'create'               => 'liveadmin',
    'modify'               => 'liveadmin',
    'createfeed'           => 'liveadmin',
    'modifyfeed'           => 'liveadmin',
    'createstream'         => 'liveadmin',
    'modifystream'         => 'liveadmin',
  );
  
  public $forms = array(
    'create'               => 'Visitor\\Live\\Form\\Create',
    'modify'               => 'Visitor\\Live\\Form\\Modify',
    'createfeed'           => 'Visitor\\Live\\Form\\Createfeed',
    'modifyfeed'           => 'Visitor\\Live\\Form\\Modifyfeed',
    'createstream'         => 'Visitor\\Live\\Form\\Createstream',
    'modifystream'         => 'Visitor\\Live\\Form\\Modifystream',
    'modifybasics'         => 'Visitor\\Live\\Form\\Modifybasics',
    'modifyclassification' => 'Visitor\\Recordings\\Form\\Modifyclassification',
    'modifydescription'    => 'Visitor\\Recordings\\Form\\Modifydescription',
    'modifysharing'        => 'Visitor\\Recordings\\Form\\Modifysharing',
  );
  
  public $paging = array(
    'index'   => 'Visitor\\Live\\Paging\\Index',
    'details' => 'Visitor\\Live\\Paging\\Details',
  );
  
  public function viewAction() {
    
    $feedModel = $this->modelIDCheck(
      'livefeeds',
      $this->application->getNumericParameter('id')
    );
    
    $channelModel = $this->modelIDCheck('channels', $feedModel->row['channelid'] );
    $streamid     = $this->application->getNumericParameter('streamid');
    $streams      = $feedModel->getStreams();
    
    if ( $streamid and isset( $streams[ $streamid ] ) )
      $currentstream = $streams[ $streamid ];
    else
      $currentstream = reset( $streams );
    
    if ( $currentstream['feedtype'] == 'flash' ) {
      
      $flashdata = array(
        'language'           => \Springboard\Language::get(),
        'media_servers'      => array( $currentstream['streamurl'] ),
        'media_streams'      => array( $currentstream['keycode'] ),
        'recording_title'    => $feedModel->row['name'],
        'recording_duration' => '9999',
      );
      
      if ( $feedModel->row['numberofstreams'] == 2 ) {
        
        $flashdata['media_secondaryServers'] = array( $currentstream['contentstreamurl'] );
        $flashdata['media_secondaryStreams'] = array( $currentstream['contentkeycode'] );
        
      }
      
      if ( !$feedModel->row['slideonright'] )
        $flashdata['layout_videoOrientation'] = 'right';
      
      if ( $flashdata['language'] != 'en' )
        $flashdata['locale'] =
          $this->toSmarty['STATIC_URI'] .
          'js/flash_locale_' . $flashdata['language'] . '.json'
        ;
      
      $this->toSmarty['flashdata'] = $flashdata;
      
    }
    
    $this->toSmarty['channel']       = $channelModel->row;
    $this->toSmarty['streams']       = $streams;
    $this->toSmarty['feed']          = $feedModel->row;
    $this->toSmarty['currentstream'] = $currentstream;
    $this->toSmarty['liveurl']       = $this->bootstrap->config['wowza']['liveurl'];
    
    $this->smartyoutput('Visitor/Live/View.tpl');
    
  }
  
  public function deleteAction() {
    
    $recordingModel = $this->modelOrganizationAndUserIDCheck(
      'recordings',
      $this->application->getNumericParameter('id')
    );
    
    if ( preg_match( '/^onstorage$|^failed.*$/', $recordingModel->row['status'] ) )
      $recordingModel->markAsDeleted();
    
    $this->redirect(
      $this->application->getParameter('forward', 'recordings/myrecordings')
    );
    
  }
  
}
