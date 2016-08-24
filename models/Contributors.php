<?php
namespace Model;

class Contributors extends \Springboard\Model {

  public function update( &$rs, $values, $keepresultset = false ) {

    $this->updateRecordingsMetadataTimestamp();
    return parent::update( $rs, $values, $keepresultset );

  }

  public function updateRecordingsMetadataTimestamp( $id = null ) {

    if ( !$id ) {

      $this->ensureID();
      $id = $this->id;

    }

    $recordingids = $this->db->getCol("
      SELECT DISTINCT recordingid
      FROM contributors_roles
      WHERE contributorid = '" . $id . "'
    ");

    $recordingModel = $this->bootstrap->getModel('recordings');
    $recordingModel->updateMetadataTimestamps( $recordingids );

  }

  public function getJobsWithOrganizations() {

    $this->ensureID();

    return $this->db->getArray("
      SELECT
        cj.id,
        cj.jobgroupid,
        cj.job,
        cj.userid,
        org.name,
        org.nameshort
      FROM
        contributors_jobs AS cj
        LEFT JOIN organizations AS org ON cj.organizationid = org.id
      WHERE
        cj.contributorid = '" . $this->id . "'
      ORDER BY cj.jobgroupid ASC
    ");

  }

  public function getJobGroups( $isaward = null ) {

    $this->ensureID();

    $jobs = $this->db->getArray("
      SELECT
        cj.id,
        cj.jobgroupid,
        cj.job,
        org.name,
        org.nameshort,
        org.url
      FROM
        contributors_jobs AS cj
        LEFT JOIN organizations AS org ON org.id = cj.organizationid
      WHERE
        contributorid = '" . $this->id . "'" .
        ( $isaward !== null ? " AND isaward = " . $this->db->qstr( $isaward ) : '' )
    );

    $jobgroups = array();

    foreach( $jobs as $job ) {

      $jobline = $this->getJobLine( $job );

      if ( @$jobgroups[ $job['jobgroupid'] ] )
        $jobgroups[ $job['jobgroupid'] ] .= ' / ' . $jobline;
      else
        $jobgroups[ $job['jobgroupid'] ] = $jobline;

    }

    return $jobgroups;

  }

  public function getJobLine( $job ) {

    $orgname = trim( $job['name'] );

    if ( !$orgname )
      $orgname = trim( $job['nameshort'] );

    $jobname = trim( $job['job'] );

    if ( $orgname and $jobname )
      return $orgname . ' - ' . $jobname;
    elseif ( $jobname )
      return $jobname;
    elseif ( $orgname )
      return $orgname;
    else
      return '';

  }

  public function addJobsToArray( &$array ) {

    if ( !$array )
      return;

    foreach( $array as $key => $value ) {

      if ( !$value['jobgroupid'] )
        $array[ $key ]['jobs'] = array();
      else {

        $jobgroupid = $this->db->qstr( $value['jobgroupid'] );
        $array[ $key ]['jobs'] = $this->db->getArray("
          SELECT
            cj.*,
            org.name,
            org.nameshort,
            org.url
          FROM
            contributors_jobs AS cj
            LEFT JOIN organizations AS org ON cj.organizationid = org.id
          WHERE
            cj.jobgroupid = $jobgroupid AND
            cj.contributorid = '" . $value['contributorid'] . "'
        ");

      }

    }

    return $array;

  }

  public function search( $term, $organizationid ) {

    $term    = $this->db->qstr( '%' . $term . '%' );
    $results = $this->db->getArray("
      SELECT
        c.*,
        ci.indexphotofilename
      FROM
        contributors AS c LEFT JOIN contributor_images AS ci ON
        ci.id = c.contributorimageid
      WHERE
        c.organizationid = '$organizationid' AND
        IF( c.nameformat = 'straight',
          CONCAT_WS(' ', c.nameprefix, c.namelast, c.namefirst ),
          CONCAT_WS(' ', c.nameprefix, c.namefirst, c.namelast )) LIKE $term
      LIMIT 20
    ");

    return $results;

  }

  public function insertAndUpdateIndexPhoto( $filename ) {

    $this->ensureID();
    $imageModel = $this->bootstrap->getModel('contributor_images');
    $imageModel->addFilter('contributorid', $this->id );
    $imageModel->addFilter('indexphotofilename', $filename, false, false );

    $image = $imageModel->getRow();
    if ( !empty( $image ) )
      $imageModel->id = $image['id'];
    else
      $imageModel->insert( array(
          'contributorid'      => $this->id,
          'indexphotofilename' => $filename,
        )
      );

    $this->updateRow( array(
        'contributorimageid' => $imageModel->id,
      )
    );

    return $this->row['contributorimageid'];

  }

  public function getCurrentIndexPhoto() {

    $this->ensureObjectLoaded();
    if ( !$this->row['contributorimageid'] )
      return '';

    return $this->db->getOne("
      SELECT indexphotofilename
      FROM contributor_images
      WHERE id = '" . $this->row['contributorimageid'] . "'
      LIMIT 1
    ");

  }

  public function getIndexPhotos() {

    $this->ensureID();
    return $this->db->getArray("
      SELECT *
      FROM contributor_images
      WHERE contributorid = '" . $this->id . "'
    ");

  }

}
