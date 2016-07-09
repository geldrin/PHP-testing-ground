<?php
namespace Model;

class Help_contents extends \Springboard\Model\Multilingual {
  public $multistringfields = array( 'title', 'body' );

  public function selectByShortname( $shortname, $language, $organizationid ) {
    $shortname = $this->db->qstr( $shortname );
    $ret = $this->db->getArray("
      SELECT
        hc.id,
        hc.organizationid,
        hc.shortname,
        st.value AS title,
        sb.value AS body
      FROM help_contents AS hc
      LEFT JOIN strings AS st ON(
        st.language = '$language' AND
        st.translationof = hc.title_stringid
      )
      LEFT JOIN strings AS sb ON(
        sb.language = '$language' AND
        sb.translationof = hc.body_stringid
      )
      WHERE
        hc.shortname = $shortname AND
        hc.organizationid IN('0', '$organizationid')
      ORDER BY hc.organizationid DESC
      LIMIT 2
    ");

    if ( empty( $ret ) )
      return $ret;

    return reset( $ret );
  }
}
