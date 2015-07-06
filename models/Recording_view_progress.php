<?php
namespace Model;

class Recording_view_progress extends \Springboard\Model {
  public function getAccreditedDataCursor( $organization, $filter ) {
    $where = $this->assembleAccreditedDataWhere( $organization, $filter );
    $needpercent = $organization['elearningcoursecriteria'];

    return $this->db->query("
      SELECT
        rvp.userid,
        u.email,
        rvs.recordingid,
        r.title,
        ROUND( GREATEST(r.masterlength, IFNULL(r.contentmasterlength, 0)) ) AS recordinglength,
        ROUND(
          (
            rvp.position / GREATEST(r.masterlength, IFNULL(r.contentmasterlength, 0))
          ) * 100
        ) AS totalwatchedpercent,
        IF(
          ROUND(
            (
              rvp.position / GREATEST(r.masterlength, IFNULL(r.contentmasterlength, 0))
            ) * 100
          ) >= $needpercent,
          1,
          0
        ) AS totalcompleted,
        (rvs.positionuntil - rvs.positionfrom) AS sessionwatchedduration,
        ROUND(
          (
            (rvs.positionuntil - rvs.positionfrom) /
            GREATEST(r.masterlength, IFNULL(r.contentmasterlength, 0))
          ) * 100
        ) AS sessionwatchedpercent,
        rvs.positionfrom AS sessionwatchedfrom,
        rvs.positionuntil AS sessionwatcheduntil,
        rvs.timestampfrom AS sessionwatchedtimestampfrom,
        rvs.timestampuntil AS sessionwatchedtimestampuntil
      FROM
        recording_view_sessions AS rvs,
        recording_view_progress AS rvp,
        users AS u,
        recordings AS r
      WHERE
        u.id = rvp.userid AND
        u.id = rvs.userid AND
        r.id = rvp.recordingid AND
        r.id = rvs.recordingid AND
        rvp.position > 0 -- resetelt a progress mert tul sok kimaradas volt, nem erdekes
        $where
      ORDER BY
        rvs.recordingid,
        rvs.userid,
        rvs.id
    ");
  }

  private function assembleAccreditedDataWhere( $organization, $filter ) {
    if ( empty( $filter ) )
      return '';

    $where = array();
    if ( isset( $filter['email'] ) and $filter['email'] )
      $where[] = "u.email = " . $this->db->qstr( $filter['email'] );

    if ( isset( $filter['completed'] ) ) {
      $needpercent = $organization['elearningcoursecriteria'];
      $inequality  = $filter['completed']? ">=": "<";
      $where[]     = "
        ROUND(
          (
            rvp.position / GREATEST(r.masterlength, IFNULL(r.contentmasterlength, 0))
          ) * 100
        ) $inequality $needpercent
      ";
    }

    if ( empty( $where ) )
      return '';

    return 'AND ' . implode(" AND \n", $where );
  }

}
