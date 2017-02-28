<?php
namespace Visitor\Index;

class Controller extends \Visitor\Controller {
  public $permissions = array(
    'index'  => 'public',
  );

  private $maxRecordings = 4;
  private $blocksToTypes = array(
    'legujabb'           => 'newest',
    'legnezettebb'       => 'mostviewed',
    'legjobb'            => 'best',
    'csatornafelvetelek' => 'subscriptions',
  );
  private $blocks = array(
    'eloadas', 'kiemelt', 'ujranezes',
    'legujabb', 'legnezettebb', 'legjobb', 'csatornafelvetelek',
  );

  public function indexAction() {
    $this->recordingsModel = $this->bootstrap->getModel('recordings');
    $user = $this->bootstrap->getSession('user');

    $this->toSmarty['defaultimage'] =
      $this->bootstrap->staticuri . 'images/header_logo.png'
    ;
    $this->toSmarty['welcome'] = true;

    $l = $this->bootstrap->getLocalization();
    $labels = array();
    $blocks = array();
    foreach( $this->blocks as $block ) {
      $labels[ $block ] = $l('index', 'block_' . $block );

      if ( isset( $this->blocksToTypes[ $block ] ) ) {
        $type = $this->blocksToTypes[ $block ];
        $blocks[ $block ] = \Visitor\Recordings\Paging\Featured::getRecItems(
          $this->organization['id'],
          $user,
          $type,
          0,
          $this->maxRecordings
        );
      } else {
        $method = 'getBlock' . ucfirst( $block );
        if ( method_exists( $this, $method ) ) {
          $blocks[ $block ] = $this->$method( $user );
          $blocks[ $block ] = $this->recordingsModel->addPresentersToArray(
            $blocks[ $block ],
            true,
            $this->organization['id']
          );
        }
      }
    }

    $this->toSmarty['blocksToTypes'] = $this->blocksToTypes;
    $this->toSmarty['labels'] = $labels;
    $this->toSmarty['blocks'] = $blocks;

    $smarty = $this->bootstrap->getSmarty();
    $this->fetchSmarty('Visitor/Index/index_blocks.tpl');
    foreach( $blocks as $block => $v ) {
      $key   = 'ajanlo_' . $block;
      $value = $smarty->get_template_vars( $key );
      $this->toSmarty[ $key ] = trim( $value );
    }

    unset(
      $this->toSmarty['blocksToTypes'],
      $this->toSmarty['labels'],
      $this->toSmarty['blocks'],
      $labels,
      $blocks
    );

    $content = $this->fetchSmarty( $this->organization['indextemplate'] );
    $this->toSmarty['content'] = $content;

    $this->smartyoutput('Visitor/Index/index.tpl');
  }

  private function getBlockKiemelt( $user ) {
    return $this->recordingsModel->getRandomRecordings(
      4,
      $this->organization['id'],
      $user
    );
  }

  private function getBlockEloadas( $user ) {
    $channelModel = $this->bootstrap->getModel('channels');
    $channels = $channelModel->getFeatured(
      $this->organization['id'],
      \Springboard\Language::get(),
      4
    );

    $feedModel = $this->bootstrap->getModel('livefeeds');
    $user      = $this->bootstrap->getSession('user');
    foreach( $channels as $key => $channel ) {
      $feed = array(
        'id'              => $channel['livefeedid'],
        'accesstype'      => $channel['accesstype'],
        'istokenrequired' => $channel['istokenrequired'],
      );

      $feedModel->row = $feed['id'];
      $feedModel->row = $feed;

      // van hozzaferese a usernek (vagy publikus)
      if ( $feedModel->isAccessible( $user, $this->organization, null, null ) )
        continue;

      unset( $channels[ $key ] );
    }

    return $channels;
  }

  private function getBlockUjranezes( $user ) {
    if ( !$user or !$user['id'] )
      return array();

    return $this->recordingsModel->getUsersHistory(
      $user,
      $this->organization['id'],
      0, 4, "contenthistorytimestamp DESC"
    );
  }
}
