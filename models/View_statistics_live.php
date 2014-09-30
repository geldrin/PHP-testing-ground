<?php
namespace Model;

class View_statistics_live extends \Model\View_statistics {
  
  public function log( $values ) {
    $values              = $this->populateStreamInfo( $values );
    $values['timestampuntil'] = date('Y-m-d H:i:s');

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

      if ( $this->db->affected_rows()  <= 0 ) {
        $values['timestampfrom'] = $values['timestampuntil'];
        $this->insert( $values );
      }

    } else {
      $values['timestampfrom'] = $values['timestampuntil'];
      $this->insert( $values );
    }

  }

}
