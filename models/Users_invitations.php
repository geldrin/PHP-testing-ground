<?php
namespace Model;

class Users_invitations extends \Springboard\Model {

  public function getSearchCount( $searchterm, $organizationid ) {
    $searchterm = str_replace( ' ', '%', $searchterm );
    $searchterm = $this->db->qstr( '%' . $searchterm . '%' );
    return $this->db->getOne("
      SELECT COUNT(*)
      FROM users_invitations
      WHERE
        email LIKE $searchterm AND
        organizationid  = '$organizationid' AND
        status         <> 'deleted'
      LIMIT 1
    ");
  }

  public function getSearchArray( $originalterm, $organizationid, $start, $limit, $order ) {
    $term        = $this->db->qstr( $originalterm );
    $searchterm  = str_replace( ' ', '%', $originalterm );
    $searchterm  = $this->db->qstr( '%' . $searchterm . '%' );

    return $this->db->getArray("
      SELECT
        *,
        (
          1 +
          IF( email = $term, 3, 0 )
        ) AS relevancy
      FROM users_invitations
      WHERE
        email LIKE $searchterm AND
        organizationid  = '$organizationid' AND
        status         <> 'deleted'
      ORDER BY $order
      LIMIT $start, $limit
    ");
  }

  public function isExpired() {
    $this->ensureObjectLoaded();
    return !$this->db->getOne("
      SELECT COUNT(*)
      FROM users_invitations AS ui
      WHERE
        ui.id = '" . $this->id . "' AND
        ui.invitationvaliduntil >= NOW()
      LIMIT 1
    ");
  }
}
