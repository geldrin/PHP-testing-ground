<?php
$this->navigation[] = Array(
  'link'  => 'newsletter',
  'key'   => 'Vissza a hírlevelekhez',
  'icon'  => 'images/clearbits/arrow1_w.gif',
  'style' => 'display: block; width: 200px; text-align: left;',
);
$this->navigation[] = Array(
  'link'  => 'mailqueue',
  'key'   => 'Küldési sor',
  'icon'  => 'images/clearbits/zoomin.gif',
);
$this->navigation[] = Array(
  'link'  => 'mailqueue/runqueue',
  'key'   => 'Küldés indítása',
  'icon'  => 'images/clearbits/play.gif',
);
$this->navigation[] = Array(
  'link'  => 'mailqueue/changeform',
  'key'   => 'Leállítás/újraküldés',
  'icon'  => 'images/clearbits/loop.gif',
);
$this->navigation[] = Array(
  'link'  => 'mailqueue/removeform',
  'key'   => 'Törlés',
  'icon'  => 'images/clearbits/trash.gif',
);

$GLOBALS['viewbutton'] =
  $this->newWindow('Részletek', 'mailqueue/view?id=%id%')
;

$listconfig = Array(

  'table'      => 'mailqueue',
//  'delete'     => 'id',
  'order'      => Array('id DESC' ),
  
  'fields' => Array(
    
    Array(
      'field'       => 'id',
      'displayname' => 'Sorszám',
    ),
    
    Array(
      'field'       => 'timestamp',
      'displayname' => 'Feladás ideje',
      'layout'      => '<td><small>%s</small></td>',
    ),
    
    Array(
      'field'       => 'toemail',
      'displayname' => 'Címzett',
      'layout'      => '<td><a href="mailto:%1$s">%1$s</a></td>',
    ),
    
    Array(
      'field'       => 'status',
      'displayname' => 'Állapot',
      'lov'         => $l->getLov('mailqueueerrors'),
    ),
    
    Array(
      'field'       => 'subject',
      'displayname' => 'Téma',
    ),
    
    Array(
      'field'       => 'id',
      'displayname' => '',
      'phptrigger'  => '
        str_replace("%id%", "<VALUE>", $GLOBALS["viewbutton"] )
      ',
    ),
  ),
);
