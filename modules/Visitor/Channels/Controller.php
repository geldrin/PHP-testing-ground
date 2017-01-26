<?php
namespace Visitor\Channels;

class Controller extends \Visitor\Controller {
  public $permissions = array(
    'index'               => 'public',
    'details'             => 'public',
    'create'              => 'uploader|moderateduploader|editor|clientadmin',
    'modify'              => 'uploader|moderateduploader|editor|clientadmin',
    'delete'              => 'uploader|moderateduploader|editor|clientadmin',
    'mychannels'          => 'member',
    'addrecording'        => 'member',
    'deleterecording'     => 'member',
    'search'              => 'member',
    'orderrecordings'     => 'uploader|moderateduploader|editor|clientadmin',
    'setorder'            => 'uploader|moderateduploader|editor|clientadmin',
  );

  public $forms = array(
    'create' => 'Visitor\\Channels\\Form\\Create',
    'modify' => 'Visitor\\Channels\\Form\\Modify',
  );

  public $paging = array(
    'index'          => 'Visitor\\Channels\\Paging\\Index',
    'details'        => 'Visitor\\Channels\\Paging\\Details',
    'mychannels'     => 'Visitor\\Channels\\Paging\\Mychannels',
  );

  public $apisignature = array(
    'getdetails' => array(
      'id' => array(
        'type' => 'id',
      ),
      'liveembedwithchat' => array(
        'type'        => 'id',
        'required'    => false,
        'shouldemail' => false,
      ),
      'liveembedfullwidth' => array(
        'type'        => 'id',
        'required'    => false,
        'shouldemail' => false,
      ),
      'recordingembedfullwidth' => array(
        'type'        => 'id',
        'required'    => false,
        'shouldemail' => false,
      ),
      'recordingembedautoplay' => array(
        'type'        => 'id',
        'required'    => false,
        'shouldemail' => false,
      ),
      'recordingembedstart' => array(
        'type'        => 'string',
        'required'    => false,
        'shouldemail' => false,
      ),
    ),
    'list' => array(
      'start' => array(
        'type'        => 'id',
        'required'    => false,
        'shouldemail' => false,
      ),
      'limit' => array(
        'type'        => 'id',
        'required'    => false,
        'shouldemail' => false,
      ),
    ),
  );

  public function listAction( $start, $limit ) {
    if ( $start < 0 )
      $start = 0;
    if ( $limit <= 0 )
      $limit = 30;

    $chanModel = $this->bootstrap->getModel('channels');

    $ret = array(
      'total' => (float)$chanModel->getListCount( $this->organization['id'] ),
      'start' => $start,
      'limit' => $limit,
      'data'  => $chanModel->getList(
        $this->organization['id'],
        \Springboard\Language::get(),
        $start,
        $limit
      ),
    );

    $this->bootstrap->includeTemplatePlugin('indexphoto');
    foreach( $ret['data'] as $key => $row )
      $ret['data'][ $key ]['indexphotourl'] =
        smarty_modifier_indexphoto( $row )
      ;

    return $ret;
  }

  public function getdetailsAction( $id, $liveembedwithchat, $liveembedfullwidth, $recordingembedfullwidth, $recordingembedautoplay, $recordingembedstart ) {
    $user = $this->bootstrap->getSession('user');
    $channelModel = $this->checkOrganizationAndIDWithApi(
      true,
      'channels',
      $id
    );

    $this->bootstrap->includeTemplatePlugin('indexphoto');
    $recModel = $this->bootstrap->getModel('recordings');
    $ret = $channelModel->row;

    $channelids = array_merge(
      array( $channelModel->id ),
      $channelModel->findChildrenIDs()
    );

    $ret['recordings'] = array(
      'count' => $recModel->getChannelRecordingsCount(
        $user,
        $channelids
      ),
      'data' => $recModel->getChannelRecordings(
        $user,
        $channelids,
        false,
        false,
        false
      )
    );
    $ret['recordings']['data'] = $recModel->addPresentersToArray(
      $ret['recordings']['data'],
      true,
      $this->organization['id']
    );

    $ret['recordings']['data'] = $this->addEmbedToRecordings(
      $ret['recordings']['data'],
      $recordingembedfullwidth,
      $recordingembedautoplay,
      $recordingembedstart
    );


    $filters = array(
      'organizationid' => $this->organization['id'],
      'showall' => '1',
    );
    $ret['livefeeds'] = array(
      'count' => 0,
      'data'  => $channelModel->getFeedsWithStreams(),
    );
    $ret['livefeeds']['count'] = count( $ret['livefeeds']['data'] );

    $ret['livefeeds']['data'] = $this->addEmbedToLive(
      $ret['livefeeds']['data'],
      $liveembedwithchat,
      $liveembedfullwidth
    );

    return $ret;
  }

  private function addEmbedToRecordings( &$data, $recordingembedfullwidth, $recordingembedautoplay, $recordingembedstart ) {
    $params = array(
      'autoplay' => $recordingembedautoplay? 'yes': 'no',
      'fullscale' => $recordingembedfullwidth? 'yes': 'no',
    );

    if ( $recordingembedfullwidth )
      $width = '950';
    else
      $width = '480';

    if ( preg_match( '/^\d{1,2}h\d{1,2}m\d{1,2}s$|^\d+$/', $recordingembedstart ) )
      $params['start'] = $recordingembedstart;

    $query = htmlspecialchars(
      http_build_query( $params ),
      ENT_QUOTES,
      'UTF-8'
    );

    $embed =
      '<iframe width="' . $width . '" height="*HEIGHT*" src="' .
      $this->bootstrap->baseuri . 'recordings/' .
      'embed/*RECORDINGID*?' . $query . '&amp;token=***TOKEN***" frameborder="0"' .
      ' allowfullscreen="allowfullscreen"></iframe>'
    ;

    $recModel = $this->bootstrap->getModel('recordings');
    foreach( $data as $key => $row ) {
      $recModel->row = $row;
      $recModel->id  = $row['id'];

      $player = $recModel->getPlayer();
      $data[ $key ]['embed'] = strtr( $embed, array(
          '*HEIGHT*'      => $player->getPlayerHeight( $recordingembedfullwidth ),
          '*RECORDINGID*' => $row['id'],
        )
      );
      $data[ $key ]['indexphotourl'] = smarty_modifier_indexphoto(
        $row, 'player'
      );
    }

    return $data;
  }

  private function addEmbedToLive( &$data, $liveembedwithchat, $liveembedfullwidth ) {
    $this->bootstrap->includeTemplatePlugin('filenameize');
    $params = array(
      'chromeless' => 'true',
      'chat' => '-NEEDCHAT-', // mert a * htmlspecialchar es escapelodik
    );

    if ( !$liveembedfullwidth )
      $params['fullplayer'] = 'false';

    if ( $liveembedfullwidth )
      $width = '980';
    else
      $width = '480';

    $embed =
      '<iframe width="' . $width . '" height="*HEIGHT*" src="' .
      $this->bootstrap->baseuri . 'live/view/*FEEDID*,*FEEDNAMEIZED*?*QUERY*&amp;token=***TOKEN***"' .
      ' frameborder="0" allowfullscreen="allowfullscreen"></iframe>'
    ;

    $query = htmlspecialchars(
      http_build_query( $params ),
      ENT_QUOTES,
      'UTF-8'
    );

    $embed = str_replace('*QUERY*', $query, $embed );

    foreach( $data as $key => $row ) {
      $needchat = $liveembedwithchat;
      if ( $row['moderationtype'] == 'nochat' )
        $needchat = false;

      if ( $liveembedfullwidth ) {

        if ( $needchat )
          $height = '1000';
        else
          $height = '670';

      } else {

        if ( $needchat )
          $height = '670';
        else
          $height = '550';
      }

      $data[ $key ]['embed'] = strtr( $embed, array(
          '*HEIGHT*'       => $height,
          '*FEEDID*'       => $row['id'],
          '*FEEDNAMEIZED*' => smarty_modifier_filenameize( $row['name'] ),
          '-NEEDCHAT-'     => $needchat? 'true': 'false',
        )
      );
      $data[ $key ]['indexphotourl'] = smarty_modifier_indexphoto(
        $row, 'live'
      );
    }

    return $data;
  }

  public function deleteAction() {

    $channelModel = $this->modelOrganizationAndIDCheck(
      'channels',
      $this->application->getNumericParameter('id')
    );
    $l        = $this->bootstrap->getLocalization();
    $children = $channelModel->findChildrenIDs();

    // nem engedunk torolni csatornat aminek vannak gyerekei vagy kulonleges csatorna
    if ( empty( $children ) ) {
      $channelModel->markAsDeleted();
      $message = $l('channels', 'channels_deleted');
    } else
      $message = $l('channels', 'channels_deletefailed');

    $this->redirectWithMessage(
      $this->application->getParameter('forward', 'channels/mychannels'),
      $message
    );

  }

  public function searchAction() {

    $term   = $this->application->getParameter('term');
    $output = array(
    );

    if ( !$term )
      $this->jsonoutput( $output );

    $user         = $this->bootstrap->getSession('user');
    $channelModel = $this->bootstrap->getModel('channels');
    $results      = $channelModel->search( $term, $user['id'], $this->organization['id'] );

    if ( empty( $results ) )
      $this->jsonoutput( $output );

    foreach( $results as $result ) {

      $title = $result['title'];
      if ( strlen( trim( $result['subtitle'] ) ) )
        $title .= '<br/>' . $result['subtitle'];

      $data = array(
        'value' => $result['id'],
        'label' => $title,
        'img'   => $this->bootstrap->staticuri,
      );

      if ( $result['indexphotofilename'] )
        $data['img'] .= 'files/' . $result['indexphotofilename'];
      else
        $data['img'] .= 'images/videothumb_audio_placeholder.png';

      $output[] = $data;

    }

    $this->jsonoutput( $output );

  }

  public function orderrecordingsAction() {

    $channelModel = $this->modelOrganizationAndUserIDCheck(
      'channels',
      $this->application->getNumericParameter('id')
    );

    $items = $channelModel->getRecordings(
      $this->organization['id']
    );

    $items = $this->bootstrap->getModel('recordings')->addPresentersToArray(
      $items, true, $this->organization['id']
    );

    $this->toSmarty['help']    = $this->getHelp('channels_orderrecordings');
    $this->toSmarty['items']   = $items;
    $this->toSmarty['channel'] = $channelModel->row;
    $this->toSmarty['forward'] = $this->application->getParameter(
      'forward', \Springboard\Language::get() . '/channels/mychannels'
    );
    $this->smartyOutput('Visitor/Channels/Orderrecordings.tpl');

  }

  public function setorderAction() {

    $neworder = $this->application->getParameter('order');
    if ( empty( $neworder ) )
      $this->jsonOutput( array('status' => 'error', 'message' => 'nothingprovided') );

    $channelModel = $this->modelOrganizationAndUserIDCheck(
      'channels',
      $this->application->getNumericParameter('id')
    );

    $channelModel->startTrans();
    /* Get the current order of weights, exchange them for the new ones
     * the current order is simply an array of weights
     * the new order is an array of channelrecordingids
     */
    $currentorder = $channelModel->getRecordingWeights( $this->organization['id'] );

    if ( count( $neworder ) != count( $currentorder ) ) {

      $this->jsonOutput( array(
          'status' => 'error',
          'error'  => 'received order count does not equal server-side count',
        ), true
      );

    }

    foreach( $neworder as $key => $crid )
      $channelModel->setRecordingOrder( $crid, $currentorder[ $key ] );

    $channelModel->endTrans();
    $this->jsonoutput( array(
        'status' => 'success',
      )
    );

  }

}
