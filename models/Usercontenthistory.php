<?php
namespace Model;

class Usercontenthistory extends \Springboard\Model {
  public function markLivefeed( $feedModel, $user ) {
    if ( !$user or !$user['id'] ) // nem belepett usernel nem erdekel minket
      return;

    $feedModel->ensureObjectLoaded();

    $history     = array(
      'userid'      => $userid,
      'livefeedid'  => $feedModel->id,
      'channelid'   => $feedModel->row['channelid'],
      'timestamp'   => date('Y-m-d H:i:s'),
    );
    $this->insert( $history );

  }

  public function markRecording( $recordingsModel, $user ) {
    if ( !$user or !$user['id'] ) // nem belepett usernel nem erdekel minket
      return;

    $recordingsModel->ensureID();
    $recordingid = $recordingsModel->id;
    $userid = $user['id'];

    // get the channels the user has access to and the recording is a member of
    // also contains the classifications, all-in-one query to cut down on
    // network access
    $classification = $this->db->getArray("
      (
        SELECT
          cr.channelid,
          NULL as categoryid,
          NULL as genreid
        FROM
          channels_recordings AS cr,
          users_invitations AS ui
        WHERE
          cr.recordingid      = '$recordingid' AND
          cr.channelid        = ui.channelid AND
          ui.registereduserid = '$userid' AND
          ui.status           <> 'deleted'
      ) UNION DISTINCT (
        SELECT
          cr.channelid,
          NULL as categoryid,
          NULL as genreid
        FROM
          channels_recordings AS cr,
          channels AS c
        WHERE
          cr.recordingid = '$recordingid' AND
          cr.channelid   = c.id AND
          c.accesstype   = 'public' AND
          c.isdeleted    = '0'
      ) UNION DISTINCT (
        SELECT
          NULL as channelid,
          NULL as categoryid,
          rg.genreid
        FROM recordings_genres AS rg
        WHERE rg.recordingid = '$recordingid'
      ) UNION DISTINCT (
        SELECT
          NULL as channelid,
          rc.categoryid,
          NULL as genreid
        FROM recordings_categories AS rc
        WHERE rc.recordingid = '$recordingid'
      )
    ");

    $history     = array(
      'userid'      => $userid,
      'recordingid' => $recordingid,
      'channelid'   => null,
      'categoryid'  => null,
      'genreid'     => null,
      'timestamp'   => date('Y-m-d H:i:s'),
    );

    foreach( $classification as $row )
      $this->insertBatchCollect( array_merge( $history, $row ) );

    $this->flushBatchCollect();
  }

}
