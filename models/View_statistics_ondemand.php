<?php
namespace Model;

class View_statistics_ondemand extends \Model\View_statistics {
  
  public function log( $values ) {
    $values              = $this->populateStreamInfo( $values );
    $values['timestamp'] = date('Y-m-d H:i:s');

    if ( $values['action'] == 'PLAYING' ) {

      $fields = array();
      foreach( $values as $field => $value )
        $fields []= "$field = " . $this->db->qstr( $value );

      $this->db->execute("
        UPDATE view_statistics_ondemand
        SET " . implode(", ", $fields ) . "
        WHERE
          viewsessionid = " . $this->db->qstr( $values['viewsessionid'] ) . " AND
          action        = '" . $values['action'] . "'
      ");

      if ( $this->db->affected_rows()  <= 0 )
        $this->insert( $values );

    } else
      $this->insert( $values );

  }

}
