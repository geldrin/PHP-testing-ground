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
        rvp.recordingid,
        r.title,
        GREATEST(r.masterlength, r.contentmasterlength) AS recordinglength,
        ROUND(
          (
            rvp.position / GREATEST(r.masterlength, r.contentmasterlength)
          ) * 100
        ) AS watchedpercent,
        IF(
          ROUND(
            (
              rvp.position / GREATEST(r.masterlength, r.contentmasterlength)
            ) * 100
          ) >= $needpercent,
          1,
          0
        ) AS completed,
        rvp.position
      FROM
        recording_view_progress AS rvp,
        users AS u,
        recordings AS r
      WHERE
        u.id = rvp.userid AND
        r.id = rvp.recordingid
        $where
      ORDER BY rvp.recordingid, rvp.timestamp
    ");
  }

  private function assembleAccreditedDataWhere( $organization, $filter ) {
    if ( empty( $filter ) )
      return '';

    $where = array();
    if ( isset( $filter['email'] ) )
      $where[] = "u.email = " . $this->db->qstr( $filter['email'] );

    if ( isset( $filter['completed'] ) ) {
      $needpercent = $organization['elearningcoursecriteria'];
      $inequality  = $filter['completed']? ">=": "<";
      $where[]     = "
        ROUND(
          (
            rvp.position / GREATEST(r.masterlength, r.contentmasterlength)
          ) * 100
        ) $inequality $needpercent
      ";
    }

    if ( empty( $where ) )
      return '';

    return 'AND ' . implode(" AND \n", $where );
  }

}
