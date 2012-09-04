<?php
namespace Visitor\Recordings;

class Controller extends \Visitor\Controller {
  public $permissions = array(
    'index'                => 'public',
    'details'              => 'public',
    'getplayerconfig'      => 'public',
    'getcomments'          => 'public',
    'getsubtitle'          => 'public',
    'newcomment'           => 'member',
    'rate'                 => 'member',
    'track'                => 'public',
    'upload'               => 'uploader',
    'uploadcontent'        => 'uploader',
    'uploadsubtitle'       => 'uploader',
    'uploadattachment'     => 'uploader',
    'myrecordings'         => 'uploader',
    'modifybasics'         => 'uploader',
    'modifyclassification' => 'uploader',
    'modifydescription'    => 'uploader',
    'modifycontributors'   => 'uploader',
    'modifysharing'        => 'uploader',
    'modifyattachment'     => 'uploader',
    'deleteattachment'     => 'uploader',
    'deletesubtitle'       => 'uploader',
    'delete'               => 'uploader',
    'checkstreamaccess'    => 'public',
    'securecheckstreamaccess' => 'public',
    'progress'             => 'member',
    'getprogress'          => 'member',
    'embed'                => 'public',
    'featured'             => 'public',
    'searchcontributor'    => 'uploader',
    'newcontributor'       => 'uploader',
    'linkcontributor'      => 'uploader',
  );
  
  public $forms = array(
    'upload'               => 'Visitor\\Recordings\\Form\\Upload',
    'uploadcontent'        => 'Visitor\\Recordings\\Form\\Uploadcontent',
    'uploadsubtitle'       => 'Visitor\\Recordings\\Form\\Uploadsubtitle',
    'uploadattachment'     => 'Visitor\\Recordings\\Form\\Uploadattachment',
    'modifybasics'         => 'Visitor\\Recordings\\Form\\Modifybasics',
    'modifyclassification' => 'Visitor\\Recordings\\Form\\Modifyclassification',
    'modifydescription'    => 'Visitor\\Recordings\\Form\\Modifydescription',
    'modifycontributors'   => 'Visitor\\Recordings\\Form\\Modifycontributors',
    'modifysharing'        => 'Visitor\\Recordings\\Form\\Modifysharing',
    'newcomment'           => 'Visitor\\Recordings\\Form\\Newcomment',
    'modifyattachment'     => 'Visitor\\Recordings\\Form\\Modifyattachment',
    'newcontributor'       => 'Visitor\\Recordings\\Form\\Newcontributor',
    'linkcontributor'      => 'Visitor\\Recordings\\Form\\Linkcontributor',
  );
  
  public $paging = array(
    'myrecordings' => 'Visitor\\Recordings\\Paging\\Myrecordings',
    'featured'     => 'Visitor\\Recordings\\Paging\\Featured',
  );
  
  public $apisignature = array(
    'modifyrecording' => array(
      'id' => array(
        'type' => 'id',
      ),
    ),
    'apiupload' => array(
      'file' => array(
        'type' => 'file',
      ),
      'language' => array(
        'type' => 'string',
      ),
    ),
    'apiuploadcontent' => array(
      'id' => array(
        'type' => 'id',
      ),
      'file' => array(
        'type' => 'file',
      ),
    ),
  );
  
  public function init() {
    
    $action = str_replace('submit', '', $this->action );
    
    if ( $action == 'upload' or $action == 'uploadcontent' )
      $this->bootstrap->setupSession( true );
    
    parent::init();
    
  }
  
  public function indexAction() {
    $this->redirect('recordings/myrecordings');
  }
  
  public function rateAction() {
    
    $recordingid = $this->application->getNumericParameter('id');
    $rating      = $this->application->getNumericParameter('rating');
    $result      = array('status' => 'error');
    
    if ( !$recordingid or $rating < 1 or $rating > 5 ) {
      
      $result['reason'] = 'invalidparameters';
      $this->jsonoutput( $result );
      
    }
    
    $session = $this->bootstrap->getSession('rating');
    if ( $session[ $recordingid ] ) {
      
      $result['reason'] = 'alreadyvoted';
      $this->jsonoutput( $result );
      
    }
    
    $recordingsModel = $this->bootstrap->getModel('recordings');
    $recordingsModel->id = $recordingid;
    
    if ( !$recordingsModel->addRating( $rating ) )
      $this->jsonoutput( $result );
    
    $session[ $recordingid ] = true;
    $result = array(
      'status'          => 'success',
      'rating'          => $recordingsModel->row['rating'],
      'numberofratings' => $recordingsModel->row['numberofratings'],
    );
    
    $this->jsonoutput( $result );
    
  }
  
  public function detailsAction() {
    
    $recordingsModel = $this->modelIDCheck(
      'recordings',
      $this->application->getNumericParameter('id')
    );
    
    $user      = $this->bootstrap->getSession('user');
    $rating    = $this->bootstrap->getSession('rating');
    $access    = $this->bootstrap->getSession('recordingaccess');
    $accesskey = $recordingsModel->id . '-' . (int)$recordingsModel->row['issecurestreamingforced'];
    
    $access[ $accesskey ] = $recordingsModel->userHasAccess( $user );
    
    if ( $access[ $accesskey ] !== true )
      $this->redirectToController('contents', $access[ $accesskey ] );
    
    include_once(
      $this->bootstrap->config['templatepath'] .
      'Plugins/modifier.indexphoto.php'
    );
    
    $this->toSmarty['height']        = $this->getPlayerHeight( $recordingsModel );
    $this->toSmarty['recording']     = $recordingsModel->row;
    $this->toSmarty['flashdata']     = $recordingsModel->getFlashData( $this->toSmarty, session_id() );
    $this->toSmarty['comments']      = $recordingsModel->getComments();
    $this->toSmarty['commentcount']  = $recordingsModel->getCommentsCount();
    $this->toSmarty['author']        = $recordingsModel->getAuthor();
    $this->toSmarty['attachments']   = $recordingsModel->getAttachments();
    $this->toSmarty['canrate']       = ( $user['id'] and $rating[ $recordingsModel->id ] );
    $this->toSmarty['relatedvideos'] = $recordingsModel->getRelatedVideos(
      $this->application->config['relatedrecordingcount']
    );
    $this->toSmarty['opengraph']     = array(
      'type'        => 'video',
      'image'       => smarty_modifier_indexphoto( $recordingsModel->row, 'player' ),
      'description' => $recordingsModel->row['description'],
      'title'       => $recordingsModel->row['title'],
      'subtitle'    => $recordingsModel->row['subtitle'],
      'width'       => 398,
      'height'      => 303,
      'video'       =>
        $this->toSmarty['BASE_URI'] . 'flash/TCSharedPlayer.swf?media_json=' .
        rawurlencode(
          $this->toSmarty['BASE_URI'] . \Springboard\Language::get() .
          '/recordings/getplayerconfig/' . $recordingsModel->id
        )
      ,
    );
    $mobilehq = false;
    if ( $recordingsModel->row['mobilevideoreshq'] ) {
      
      $browserinfo = $this->bootstrap->getBrowserInfo();
      if ( $browserinfo['tablet'] )
        $mobilehq = true;
      
    }
    
    $quality = $this->application->getParameter('quality');
    if ( $quality and in_array( $quality, array('lq', 'hq') ) ) {
      
      if ( $quality == 'hq' and $recordingsModel->row['mobilevideoreshq'] )
        $mobilehq = true;
      elseif ( $quality == 'lq' )
        $mobilehq = false;
      
    }
    
    $this->toSmarty['mobilehq']      = $mobilehq;
    $this->toSmarty['mobilehttpurl'] = $recordingsModel->getMediaUrl(
      'mobilehttp',
      $mobilehq,
      $this->toSmarty['organization']['domain'],
      session_id()
    );
    $this->toSmarty['mobilertspurl'] = $recordingsModel->getMediaUrl(
      'mobilertsp',
      $mobilehq,
      $this->toSmarty['organization']['domain'],
      session_id()
    );
    $this->toSmarty['audiofileurl']  = $recordingsModel->getMediaUrl(
      'direct',
      false, // non-hq
      $this->toSmarty['organization']['domain'],
      session_id(),
      $this->toSmarty['STATIC_URI']
    );
    
    $this->smartyoutput('Visitor/Recordings/Details.tpl');
    
  }
  
  public function getplayerconfigAction() {
    
    $recordingsModel = $this->modelIDCheck(
      'recordings',
      $this->application->getNumericParameter('id')
    );
    
    $user      = $this->bootstrap->getSession('user');
    $access    = $this->bootstrap->getSession('recordingaccess');
    $accesskey = $recordingsModel->id . '-' . (int)$recordingsModel->row['issecurestreamingforced'];
    $needauth  = false;
    
    $access[ $accesskey ] = $recordingsModel->userHasAccess( $user );
    
    if ( $access[ $accesskey ] === 'registrationrestricted' )
      $needauth = true;
    
    $flashdata = $recordingsModel->getStructuredFlashData( $this->toSmarty, session_id() );
    
    if ( $needauth ) {
      
      $flashdata['authorization']            = array();
      $flashdata['authorization']['need']    = true;
      $flashdata['authorization']['gateway'] = $this->bootstrap->baseuri . 'hu/api';
      
    }
    
    $flashdata['share']                 = array();
    $flashdata['share']['quickEmbed']   =
      '<iframe width="480" height="303" src="' .
      $this->bootstrap->baseuri . \Springboard\Language::get() . '/recordings/embed/' .
      $recordingsModel->id . '" frameborder="0" allowfullscreen="allowfullscreen"></iframe>'
    ;
    $flashdata['share']['recordingURL'] =
      $this->bootstrap->baseuri . \Springboard\Language::get() . '/recordings/details/' .
      $recordingsModel->id . ',' . \Springboard\Filesystem::filenameize( $recordingsModel->row['title'] )
    ;
    
    $this->jsonOutput( $flashdata );
    
  }
  
  public function getcommentsAction() {
    
    $recordingid = $this->application->getNumericParameter('id');
    $start       = $this->application->getNumericParameter('start');
    
    if ( $recordingid <= 0 )
      $this->redirect('index');
    
    if ( $start < 0 )
      $start = 0;
    
    $l               = $this->bootstrap->getLocalization();
    $recordingsModel = $this->bootstrap->getModel('recordings');
    $recordingsModel->id = $recordingid;
    
    $comments     = $recordingsModel->getComments( $start );
    $commentcount = $recordingsModel->getCommentsCount();
    
    $this->jsonoutput( array(
        'comments'     => $comments,
        'nocomments'   => $l('recordings', 'nocomments'),
        'commentcount' => $commentcount,
      )
    );
    
  }
  
  public function deleteattachmentAction() {
    
    $attachmentModel = $this->modelIDCheck(
      'attached_documents',
      $this->application->getNumericParameter('id')
    );
    
    $recordingsModel = $this->modelOrganizationAndUserIDCheck(
      'recordings',
      $attachmentModel->row['recordingid']
    );
    
    $attachmentModel->updateRow( array(
        'status' => 'markedfordeletion',
      )
    );
    
    $this->redirect(
      $this->application->getParameter('forward', 'recordings/myrecordings')
    );
    
  }
  
  public function deletesubtitleAction() {
    
    $subtitleModel   = $this->modelIDCheck(
      'subtitles',
      $this->application->getNumericParameter('id')
    );
    
    $recordingsModel = $this->modelOrganizationAndUserIDCheck(
      'recordings',
      $subtitleModel->row['recordingid']
    );
    
    $subtitleModel->delete( $subtitleModel->id );
    $this->redirect(
      $this->application->getParameter('forward', 'recordings/myrecordings')
    );
    
  }
  
  public function getsubtitleAction() {
    
    header('Content-Type: text/plain; charset=UTF-8');
    $subtitleid = $this->application->getNumericParameter('id');
    $cache      = $this->bootstrap->getCache('subtitle_' . $subtitleid, null, true );
    
    if ( $cache->expired() ) {
      
      $subtitleModel = $this->modelIDCheck('subtitles', $subtitleid );
      $subtitle      = $subtitleModel->row['subtitle'];
      $cache->put( $subtitle );
      unset( $subtitleModel );
      
    } else
      $subtitle = $cache->get();
    
    $this->sendheaders = false;
    $this->output( $subtitle, true, true );
    
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
  
  public function trackAction() {
    
    $views          = $this->bootstrap->getSession('views');
    $recordingModel = $this->modelIDCheck(
      'recordings',
      $this->application->getNumericParameter('id')
    );
    
    if ( !$views[ $recordingModel->id ] ) {
      
      $recordingModel->incrementViewCounters();
      $views[ $recordingModel->id ] = true;
      
    }
    
  }
  
  public function checkstreamaccessAction( $secure = false ) {
    
    \Springboard\Debug::getInstance()->log( false, false, "SECURE: $secure\n" . var_export( $_SERVER, true ) );
    $param   = $this->application->getParameter('sessionid');
    $result  = '0';
    $matched =
      preg_match(
        '/(?P<domain>[a-z\.]+)_' .
        '(?P<sessionid>[a-z0-9]{32})_' .
        '(?P<recordingid>\d+)/',
        $param,
        $matches
      )
    ;
    
    if ( $matched ) {
      
      $this->bootstrap->setupSession( true, $matches['sessionid'], $matches['domain'] );
      $access    = $this->bootstrap->getSession('recordingaccess');
      $accesskey = $matches['recordingid'] . '-' . (int)$secure;
      
      if ( $access[ $accesskey ] !== true ) {
        
        $user            = $this->bootstrap->getSession('user');
        $recordingsModel = $this->modelIDCheck('recordings', $matches['recordingid'], false );
        
        if ( $recordingsModel ) {
          
          $access[ $accesskey ] = $recordingsModel->userHasAccess( $user, $secure );
          
          if ( $access[ $accesskey ] === true )
            $result = '1';
          
        }
        
      } else
        $result = '1';
      
    }
    
    echo
      '<?xml version="1.0"?>
      <result>
        <success>' . $result . '</success>
      </result>'
    ;
    
    die();
    
  }
  
  public function securecheckstreamaccessAction() {
    return $this->checkstreamaccessAction( true );
  }
  
  public function modifyrecordingAction( $id ) {
    
    $recordingModel = $this->modelUserAndIDCheck('recordings', $id, false );
    
    if ( !$recordingModel )
      throw new \Exception('No recording found with that ID');
    
    $values = $this->application->getParameters();
    unset( // TODO inkabb whitelistet mint blacklistet
      $values['id'],
      $values['format'],
      $values['layer'],
      $values['method'],
      $values['module'],
      $values['language'],
      $values['_module']
    );
    
    $recordingModel->updateRow( $values );
    return $recordingModel->row;
    
  }
  
  public function apiuploadAction( $file, $language ) {
    
    set_time_limit(0);
    $recordingModel = $this->bootstrap->getModel('recordings');
    $languageModel  = $this->bootstrap->getModel('languages');
    $user           = $this->bootstrap->getSession('user');
    
    $languageModel->addFilter('shortname', $language, false, false );
    $values         = array(
      'userid'     => $user['id'],
      'languageid' => $languageModel->getOne('id'),
    );
    
    if ( !$values['languageid'] )
      throw new \Exception('Invalid language: ' . $language );
    
    // throws exception, nothing done to the DB yet
    $recordingModel->analyze(
      $file['tmp_name'],
      $file['name']
    );
    
    $recordingModel->insertUploadingRecording(
      $user['id'],
      $user['organizationid'],
      $values['languageid'],
      $file['name'],
      $this->bootstrap->config['node_sourceip']
    );
    
    try {
      
      $recordingModel->handleFile( $file['tmp_name'] );
      $recordingModel->updateRow( array(
          'masterstatus' => 'uploaded',
          'status'       => 'uploaded',
        )
      );
      
    } catch( Exception $e ) {
      
      $recordingModel->updateRow( array(
          'masterstatus' => 'failedmovinguploadedfile',
        )
      );
      
      // rethrow
      throw $e;
      
    }
    
    return $recordingModel->row;
    
  }
  
  public function apiuploadcontentAction( $recordingid, $file ) {
    
    set_time_limit(0);
    $recordingModel = $this->modelOrganizationAndUserIDCheck(
      'recordings',
      $recordingid,
      false
    );
    
    if ( !$recordingModel )
      throw new \Exception('No recording found with that ID');
    
    if ( !$recordingModel->canUploadContentVideo() )
      throw new \Exception(
        'Uploading a content video is denied at this point: ' .
        var_export( $recordingModel->row, true )
      );
    
    $recordingModel->analyze(
      $file['tmp_name'],
      $file['name']
    );
    
    $recordingModel->addContentRecording(
      0, // TODO interlaced?
      $this->bootstrap->config['node_sourceip']
    );
    
    try {
      
      $recordingModel->handleFile(
        $file['tmp_name'],
        'upload',
        '_content'
      );
      
      $recordingModel->markContentRecordingUploaded();
      
    } catch( Exception $e ) {
      
      $recordingModel->updateRow( array(
          'contentstatus' => 'failedmovinguploadedfile',
        )
      );
      
      throw $e;
      
    }
    
    return $recordingModel->row;
    
  }
  
  public function progressAction() {
    $this->smartyOutput('Visitor/Recordings/Progress.tpl');
  }
  
  public function getprogressAction() {
    
    $l        = $this->bootstrap->getLocalization();
    $uploadid = $this->application->getParameter('uploadid');
    $data     = array('status' => 'OK');
    
    if ( !$uploadid ) {
      
      $data['status']  = 'ERR';
      $data['message'] = $l('recordings', 'upload_noid');
      $this->jsonOutput( $data );
      
    }
    
    $status = apc_fetch( 'upload_' . $uploadid );
    
    $data['data'] = $status;
    $this->jsonOutput( $data );
    
  }
  
  protected function getPlayerHeight( $recordingsModel ) {
    
    if ( $recordingsModel->row['mastermediatype'] == 'audio' and $recordingsModel->hasSubtitle() )
      $height = '120';
    elseif ( $recordingsModel->row['mastermediatype'] == 'audio' )
      $height = '60';
    else
      $height = '385';
    
    return $height;
    
  }
  
  public function embedAction() {
    
    $recordingsModel = $this->modelIDCheck(
      'recordings',
      $this->application->getNumericParameter('id')
    );
    
    $user      = $this->bootstrap->getSession('user');
    $access    = $this->bootstrap->getSession('recordingaccess');
    $accesskey = $recordingsModel->id . '-' . (int)$recordingsModel->row['issecurestreamingforced'];
    $needauth  = false;
    
    $access[ $accesskey ] = $recordingsModel->userHasAccess( $user );
    
    if ( $access[ $accesskey ] === 'registrationrestricted' )
      $needauth = true;
    elseif ( $access[ $accesskey ] !== true )
      $this->redirectToController('contents', $access[ $accesskey ] );
    
    $mobilehq = false;
    if ( $recordingsModel->row['mobilevideoreshq'] ) {
      
      $browserinfo = $this->bootstrap->getBrowserInfo();
      if ( $browserinfo['tablet'] )
        $mobilehq = true;
      
    }
    
    $quality = $this->application->getParameter('quality');
    if ( $quality and in_array( $quality, array('lq', 'hq') ) ) {
      
      if ( $quality == 'hq' and $recordingsModel->row['mobilevideoreshq'] )
        $mobilehq = true;
      elseif ( $quality == 'lq' )
        $mobilehq = false;
      
    }
    
    $this->toSmarty['mobilehq']      = $mobilehq;
    $this->toSmarty['mobilehttpurl'] = $recordingsModel->getMediaUrl(
      'mobilehttp',
      $mobilehq,
      $this->toSmarty['organization']['domain'],
      session_id()
    );
    $this->toSmarty['mobilertspurl'] = $recordingsModel->getMediaUrl(
      'mobilertsp',
      $mobilehq,
      $this->toSmarty['organization']['domain'],
      session_id()
    );
    $this->toSmarty['audiofileurl']  = $recordingsModel->getMediaUrl(
      'direct',
      false, // non-hq
      $this->toSmarty['organization']['domain'],
      session_id(),
      $this->toSmarty['STATIC_URI']
    );
    
    $flashdata = $recordingsModel->getFlashData( $this->toSmarty, session_id() );
    $autoplay  = $this->application->getParameter('autoplay');
    $start     = $this->application->getParameter('start');
    if ( preg_match( '/^\d{1,2}h\d{1,2}m\d{1,2}s$/', $start ) )
      $flashdata['timeline_startPosition'] = $start;
    
    if ( $autoplay )
      $flashdata['timeline_autoPlay'] = true;
    
    if ( $needauth ) {
      
      $flashdata['authorization_need']    = true;
      $flashdata['authorization_gateway'] = $this->bootstrap->baseuri . 'hu/api';
      
    }
    
    $this->toSmarty['width']       = '480';
    $this->toSmarty['height']      = $this->getPlayerHeight( $recordingsModel );
    $this->toSmarty['containerid'] = 'vsq_' . rand();
    $this->toSmarty['recording']   = $recordingsModel->row;
    $this->toSmarty['flashdata']   = $flashdata;
    
    $this->smartyoutput('Visitor/Recordings/Embed.tpl');
    
  }
  
}
