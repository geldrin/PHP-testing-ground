<?php
namespace Model;

class Mailqueue extends \Springboard\Model {

  public function getSendCount() {

    return $this->db->getOne("
      SELECT COUNT(*)
      FROM mailqueue
      WHERE
        timetosend < NOW() AND
        status IS NULL
    ");

  }

  public function remove( $values ) {

    $this->db->query("
      DELETE FROM mailqueue
      WHERE " . $this->getWhere( $values ) . "
      LIMIT " . $values['limit'] . "
    ");

    return $this->db->Affected_Rows();

  }

  public function change( $values ) {

    $this->db->query("
      UPDATE mailqueue
      SET status = " . $this->nullValue( $values['status_to'] ) . "
      WHERE " . $this->getWHere( $values ) . "
      LIMIT " . $values['limit'] . "
    ");

    return $this->db->Affected_Rows();

  }

  protected function getWhere( $values ) {

    if ( isset( $values['status_from'] ) )
      $status = $values['status_from'];
    else
      $status = $values['status'];

    return "
      status       " . $this->nullCondition( $status ) . " AND
      timestamp >= '" . $values['from'] . "' AND
      timestamp <= '" . $values['until'] . "'
    ";

  }

  protected function nullCondition( $value ) {

    if ( !$value )
      return 'IS NULL';
    else
      return ' = ' . $this->db->qstr( $value );

  }

  protected function nullValue( $value ) {

    if ( !$value )
      return 'NULL';
    else
      return $this->db->qstr( $value );

  }

}
