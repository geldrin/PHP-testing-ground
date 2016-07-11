<?php
namespace Model;

class Organizations extends \Springboard\Model\Multilingual {
  public $multistringfields = array( 'introduction', 'signupvalidationemailsubject', );

  public function checkDomain( $domain, $isstatic = false ) {

    $this->clearFilter();
    if ( $isstatic )
      $this->addFilter('staticdomain', $domain, false, false );
    else
      $this->addFilter('domain', $domain, false, false );

    $this->addFilter('disabled', 0 );
    $organization = $this->getRow();

    if ( !$organization )
      return false;

    $this->id  = $organization['id'];
    $this->row = $organization;

    return true;

  }

  public function findChildrenIDs( $parentid = null ) {

    if ( $parentid === null )
      $this->ensureID();

    if ( !$parentid )
      $parentid = $this->db->qstr( $this->id );
    else
      $parentid = $this->db->qstr( $parentid );

    $children = $this->db->getCol("
      SELECT id
      FROM organizations
      WHERE parentid = " . $parentid
    );

    foreach( $children as $parentid )
      $children = array_merge( $children, $this->findChildrenIDs( $parentid ) );

    return $children;

  }

  public function setup() {

    $this->ensureObjectLoaded();
    $this->updateRow( array('organizationid' => $this->id ) );

  }

  public function search( $term, $organizationid ) {

    $term     = $this->db->qstr( '%' . $term . '%' );
    $language = \Springboard\Language::get();
    $results  = $this->db->getArray("
      SELECT
        o.*,
        sname.value AS name,
        snameshort.value AS nameshort
      FROM
        organizations AS o,
        strings AS sname,
        strings AS snameshort
      WHERE
        o.organizationid         = '$organizationid' AND
        o.disabled               = '0' AND
        sname.translationof      = o.name_stringid AND
        sname.language           = '$language' AND
        snameshort.translationof = o.nameshort_stringid AND
        snameshort.language      = '$language' AND
        (
          sname.value      LIKE $term OR
          snameshort.value LIKE $term
        )
      LIMIT 20
    ");

    return $results;

  }

  public function getName( $organization = null ) {

    if ( !$organization ) {

      $this->ensureObjectLoaded();
      $organization = $this->row;

    }

    $name = trim( $organization['name'] );
    $nameshort = trim( $organization['nameshort'] );

    if ( $name and $nameshort )
      return $name . ' (' . $nameshort . ')';
    elseif ( $name )
      return $name;
    elseif ( $nameshort )
      return $nameshort;

  }

  public function addExtraData() {

    $this->ensureObjectLoaded();

    $organization = $this->row;
    $l            = $this->bootstrap->getLocalization();
    $languages    = $l->getLov('languages');
    $languagekeys = explode(',', $this->row['languages'] );
    $organization['languages'] = array();

    foreach( $languagekeys as $language )
      $organization['languages'][ $language ] = $languages[ $language ];

    $organization['authtypes'] = $this->db->getArray("
      SELECT *
      FROM organizations_authtypes
      WHERE
        organizationid = '" . $this->id . "' AND
        disabled       = 0
    ");
    $organization['authdirectories'] = $this->db->getArray("
      SELECT *
      FROM organizations_directories
      WHERE
        organizationid = '" . $this->id . "' AND
        disabled       = 0
    ");

    // smarty cachelest igenybe akarjuk venni, ez azt jelenti hogy a dinamikus
    // layoutheader/footer-t kiirjuk egy fileba es a filenevet adjuk at a smartynak
    // sporolunk igy memoriat is, es cachelhetove is valik
    foreach( array('layoutheader', 'layoutfooter') as $layout ) {
      if ( !$organization[ $layout ] )
        continue;

      $templatefile =
        $this->bootstrap->config['cachepath'] .
        'organization_' . $layout . '_' . $organization['id'] . '.tpl'
      ;

      file_put_contents( $templatefile, $organization[ $layout ] );
      $organization[ $layout ] = $templatefile;
    }

    // szemelyre szabott fooldal
    if ( $organization['indextemplate'] ) {
      $templatefile =
        $this->bootstrap->config['cachepath'] .
        'organization_' . $organization['id'] . '_indextemplate.tpl'
      ;
      file_put_contents( $templatefile, $organization['indextemplate'] );
      $organization['indextemplate'] = $templatefile;
    } else // alapertelmezett fooldal
      $organization['indextemplate'] =
        $this->bootstrap->config['templatepath'] .
        'Visitor/Index/default_index.tpl'
      ;

    return $organization;
  }

  public function getOrganizationByDomain( $domain, $isstatic = false ) {

    $cache = $this->bootstrap->getCache( 'organizations-' . $domain, null );
    if ( $cache->expired() ) {

      if ( !$this->checkDomain( $domain, $isstatic ) )
        return false;

      $organization = $this->addExtraData();
      $cache->put( $organization );

      $cachekeys = array(
        'organizationsbyid-' . $organization['id'],
      );

      if ( !$isstatic )
        $cachekeys[] = 'organizations-' . $organization['domain'];
      else
        $cachekeys[] = 'organizations-' . $organization['staticdomain'];

      foreach( $cachekeys as $cachekey ) {
        $alternatecache = $this->bootstrap->getCache( $cachekey, null );
        $alternatecache->put( $organization );
      }

      return $organization;

    }

    return $cache->get();

  }

  public function getOrganizationByID( $id ) {

    $cache = $this->bootstrap->getCache('organizationsbyid-' . $id, null );
    if ( $cache->expired() ) {

      $this->select( $id );
      if ( !$this->row )
        return false;

      $organization = $this->addExtraData();
      $cache->put( $organization );

      $cachekeys = array(
        'organizations-' . $organization['domain'],
        'organizations-' . $organization['staticdomain'],
      );

      foreach( $cachekeys as $cachekey ) {
        $alternatecache = $this->bootstrap->getCache( $cachekey, null );
        $alternatecache->put( $organization );
      }

      return $organization;

    }

    return $cache->get();

  }

  public function getUserCount() {
    $this->ensureID();
    $ret = array();
    $ret['active'] = $this->db->getOne("
      SELECT COUNT(*)
      FROM users
      WHERE
        organizationid = '" . $this->id . "' AND
        disabled       = '" . \Model\Users::USER_VALIDATED . "'
      LIMIT 1
    ");
    $ret['inactive'] = $this->db->getOne("
      SELECT COUNT(*)
      FROM users
      WHERE
        organizationid = '" . $this->id . "' AND
        disabled       = '" . \Model\Users::USER_BANNED . "'
      LIMIT 1
    ");

    return $ret;

  }

  public function getRecordingStats() {
    $this->ensureID();
    return $this->db->getRow("
      SELECT
        SUM( recordingdatasize / ( 1024 * 1024 ) ) AS recordingdatasizemb,
        SUM( masterdatasize / ( 1024 * 1024 ) )    AS masterdatasizemb,
        ROUND( SUM( masterlength ) )        AS masterlength,
        ROUND( SUM( contentmasterlength ) ) AS contentmasterlength
      FROM recordings
      WHERE
        organizationid = '" . $this->id . "' AND
        status         = 'onstorage'
      LIMIT 1
    ");
  }

  public function getDepartmentCount() {
    return $this->db->getOne("
      SELECT COUNT(*)
      FROM departments
      WHERE
        organizationid = '" . $this->id . "'
      LIMIT 1
    ");
  }

  public function getGroupCount() {
    return $this->db->getOne("
      SELECT COUNT(*)
      FROM groups
      WHERE
        organizationid = '" . $this->id . "'
      LIMIT 1
    ");
  }

  public function getContract() {
    $this->ensureID();
    return $this->db->getRow("
      SELECT *
      FROM organizations_contracts
      WHERE
        organizationid = '" . $this->id . "' AND
        disabled       = '0' AND
        startdate     <= NOW() AND
        (
          enddate     >= NOW() OR
          enddate IS NULL
        )
      ORDER BY id DESC
      LIMIT 1
    ");
  }
}
