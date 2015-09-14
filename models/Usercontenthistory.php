<?php
namespace Model;

class Usercontenthistory extends \Springboard\Model {
  public function markLivefeed( $feedModel, $user, $organization ) {
    if ( !$user or !$user['id'] ) // nem belepett usernel nem erdekel minket
      return;

    $feedModel->ensureObjectLoaded();

    // session "lock", if we have already inserted in a session, dont insert again
    $lock = $this->bootstrap->getSession('contenthistory-livefeedid');
    if ( $lock[ $feedModel->id ] )
      return;

    $this->startTrans();
    $history     = array(
      'userid'        => $user['id'],
      'livefeedid'    => $feedModel->id,
      'numberofviews' => $feedModel->row['numberofviews'],
      'timestamp'     => date('Y-m-d H:i:s'),
    );
    $this->insert( $history );
    $historyid = $this->id;

    $row = array(
      'contenthistoryid' => $historyid,
      'channelid'        => $feedModel->row['channelid'],
      'timestamp'        => $history['timestamp'],
    );
    $historyChanModel = $this->bootstrap->getModel('usercontenthistory_channels');
    $historyChanModel->insert( $row );

    $lock[ $feedModel->id ] = true;
    $this->endTrans();
  }

  public function markRecording( $recordingsModel, $user, $organization ) {
    if ( !$user or !$user['id'] ) // nem belepett usernel nem erdekel minket
      return;

    $recordingsModel->ensureObjectLoaded();
    $recordingid    = $recordingsModel->id;
    $userid         = $user['id'];
    $organizationid = $organization['id'];

    // session "lock", if we have already inserted in a session, dont insert again
    $lock = $this->bootstrap->getSession('contenthistory-recordingid');
    if ( $lock[ $recordingid ] )
      return;

    // get the channels the user has access to and the recording is a member of
    // also contains the classifications, all-in-one query to cut down on
    // network access
    $classification = $this->db->getArray("
      (
        SELECT
          cr.channelid,
          NULL as categoryid,
          NULL as genreid,
          NULL as contributorid
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
          NULL as genreid,
          NULL as contributorid
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
          rg.genreid,
          NULL as contributorid
        FROM recordings_genres AS rg
        WHERE rg.recordingid = '$recordingid'
      ) UNION DISTINCT (
        SELECT
          NULL as channelid,
          rc.categoryid,
          NULL as genreid,
          NULL as contributorid
        FROM recordings_categories AS rc
        WHERE rc.recordingid = '$recordingid'
      ) UNION DISTINCT (
        SELECT
          NULL as channelid,
          NULL as categoryid,
          NULL as genreid,
          cr.contributorid as contributorid
        FROM contributors_roles AS cr
        WHERE
          cr.recordingid = '$recordingid' AND
          cr.roleid IN(
            (
              SELECT r.id
              FROM roles AS r
              WHERE
                r.organizationid = '$organizationid' AND
                r.ispresenter    = '1'
            )
          )
      )
    ");

    $history     = array(
      'userid'             => $userid,
      'recordingid'        => $recordingid,
      'recordingskeywords' => $recordingsModel->row['keywords'],
      'numberofviews'      => $recordingsModel->row['numberofviews'],
      'timestamp'          => date('Y-m-d H:i:s'),
    );
    $this->startTrans();
    $this->insert( $history );
    $historyid = $this->id;

    $channels     = array();
    $categories   = array();
    $genres       = array();
    $contributors = array();
    foreach( $classification as $row ) {
      if ( $row['channelid'] )
        $channels[] = $row['channelid'];
      elseif ( $row['genreid'] )
        $genres[] = $row['genreid'];
      elseif ( $row['categoryid'] )
        $categories[] = $row['categoryid'];
      elseif ( $row['contributorid'] )
        $contributors[] = $row['contributorid'];
      else {
        $this->failTrans();
        throw new \Exception("not possible for all values to be null: " . var_export( $classification, true ) );
      }
    }

    $baserow = array(
      'contenthistoryid' => $historyid,
      'timestamp'        => $history['timestamp'],
    );

    // reset and save state
    $savedtable = $this->table;
    $this->batchrecords = array();

    $this->table = 'usercontenthistory_channels';
    foreach( $channels as $value ) {
      $row = $baserow;
      $row['channelid'] = $value;
      $this->insertBatchCollect( $row );
    }
    $this->flushBatchCollect();

    $this->table = 'usercontenthistory_categories';
    foreach( $categories as $value ) {
      $row = $baserow;
      $row['categoryid'] = $value;
      $this->insertBatchCollect( $row );
    }
    $this->flushBatchCollect();

    $this->table = 'usercontenthistory_genres';
    foreach( $genres as $value ) {
      $row = $baserow;
      $row['genreid'] = $value;
      $this->insertBatchCollect( $row );
    }
    $this->flushBatchCollect();

    $this->table = 'usercontenthistory_contributors';
    foreach( $contributors as $value ) {
      $row = $baserow;
      $row['contributorid'] = $value;
      $this->insertBatchCollect( $row );
    }
    $this->flushBatchCollect();

    // restore state
    $this->table = $savedtable;
    $this->batchrecords = array();
    $lock[ $recordingid ] = true;
    $this->endTrans();
  }

}
