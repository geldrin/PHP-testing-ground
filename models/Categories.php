<?php
namespace Model;

class Categories extends \Springboard\Model\Multilingual {
  public $multistringfields = array( 'name', 'namehyphenated' );
  private $treecache = array();

  public function updateVideoCounters( $clearCache = true ) {

    $this->ensureObjectLoaded();
    if ( $clearCache )
      $this->expireCategoryTreeCache( $this->row['organizationid'] );

    $childrenids   = $this->cachedFindChildrenIDs( $this->id );
    $childrenids[] = $this->id;

    $this->db->query("
      UPDATE categories
      SET numberofrecordings = (
        -- az adott kategoriahoz rendelt felvetelek szama
        SELECT COUNT( DISTINCT r.id )
        FROM
          recordings r,
          recordings_categories rc
        WHERE
          rc.categoryid IN('" . implode("', '", $childrenids ) . "') AND
          r.id             = rc.recordingid AND
          r.status         = 'onstorage' AND
          r.approvalstatus = 'approved' AND
          (
            r.visiblefrom IS NULL OR
            r.visibleuntil IS NULL OR
            (
              r.visiblefrom  <= CURRENT_DATE() AND
              r.visibleuntil >= CURRENT_DATE()
            )
          )
      )
      WHERE id = '" . $this->id . "'
    ");

    $row = $this->row;
    if ( $row['parentid'] ) {

      //felfele is bejarjuk
      $parent = $this->bootstrap->getModel('categories');
      while ( $row['parentid'] ) {
        $parent->select( $row['parentid'] );
        $parent->updateVideoCounters( false );
        $row = $parent->row;
      }

    }

  }

  public function getCategoryTree( $organizationid, $parentid = 0, $maxlevel = 2, $currentlevel = 0 ) {
    
    if ( $currentlevel >= $maxlevel and $maxlevel > 0 )
      return array();
    
    $currentlevel++;
    $this->clearFilter();
    $this->addFilter('parentid',       $parentid );
    $this->addFilter('organizationid', $organizationid );
    
    $items = $this->getArray( false, false, false, 'weight, s1.value');
    
    foreach( $items as $key => $value )
      $items[ $key ]['children'] = $this->getCategoryTree(
        $organizationid,
        $value['id'],
        $maxlevel,
        $currentlevel
      );
    
    return $items;
    
  }
  
  public function findChildrenIDs( $parentid = null ) {
    
    if ( !$parentid ) {
      
      $this->ensureID();
      $parentid = $this->id;
      
    }
    
    $children = $this->db->getCol("
      SELECT id
      FROM categories
      WHERE 
        parentid = " . $this->db->qstr( $parentid )
    );
    
    foreach( $children as $child )
      $children = array_merge( $children, $this->findChildrenIDs( $child ) );
    
    return $children;
   
  }
  
  // --------------------------------------------------------------------------
  public function delete( $id, $magic_quotes_gpc = 0 ) {

    $this->db->query("
      DELETE FROM recordings_categories
      WHERE categoryid = " . $this->db->qstr( $id )
    );

    return parent::delete( $id, $magic_quotes_gpc );

  }

  public function cachedGetCategoryTree( $organizationid ) {
    if ( isset( $this->treecache[ $organizationid ] ) )
      return $this->treecache[ $organizationid ];

    // 1 week expiration, org specific, language specific
    $cache = $this->bootstrap->getCache(
      'categorytree-orgid' . $organizationid,
      7* 24 * 60 * 60
    );

    if ( !$cache->expired() )
      return $cache->get();

    $category = $this->getCategoryTree(
      $organizationid,
      0,
      0 // no maxlevel
    );

    $cache->put( $category );
    return $this->treecache[ $organizationid ] = $category;
  }

  public function expireCategoryTreeCache( $organizationid ) {
    foreach( $this->bootstrap->config['languages'] as $lang ) {
      $cache = $this->bootstrap->getCache(
        $lang . '-categorytree-orgid' . $organizationid,
        7* 24 * 60 * 60,
        true
      );
      $cache->expire();
    }
  }

  public function markCategoryTreeActive( $organizationid, $categoryid, &$categories = null ) {
    if ( $categories === null )
      $categories = $this->cachedGetCategoryTree( $organizationid );

    foreach( $categories as $key => $value ) {
      $categories[ $key ]['isactive'] = ( $value['id'] == $categoryid );
      $categories[ $key ]['children'] = $this->markCategoryTreeActive(
        $organizationid,
        $categoryid,
        $categories[ $key ]['children']
      );
    }

    return $categories;
  }

  public function getCategoryTreeBreadcrumb( $organizationid, $categoryid ) {
    $categories = $this->cachedGetCategoryTree( $organizationid );
    $ret = array();
    // a faban egy elt keresunk, az elen vegigjarva belerakjuk a tombbe
    // forditott sorrendben a node-okat
    $this->assembleCategoryTreeBreadcrumb( $categories, $ret, $categoryid );

    return array_reverse( $ret );
  }

  private function assembleCategoryTreeBreadcrumb( &$categories, &$edge, $categoryid ) {
    foreach( $categories as $category ) {
      if (
           $category['id'] == $categoryid or
           $this->assembleCategoryTreeBreadcrumb(
            $category['children'], $edge, $categoryid
           )
         ) {
        unset( $category['children'] );
        $edge[] = $category;
        return true;
      }
    }

    return false;
  }

  public function getChildrenIDsFromCategoryTree( $category, &$ids = null ) {
    if ( $ids === null )
      $ids = array( $category['id'] );

    foreach( $category['children'] as $category ) {
      $ids[] = $category['id'];
      $this->getChildrenIDsFromCategoryTree( $category, $ids );
    }

    return $ids;
  }

  public function searchCategoryTree( $organizationid, $categoryid, &$categories = null ) {
    if ( $categories === null)
      $categories = $this->cachedGetCategoryTree( $organizationid );

    $ret = array();
    foreach( $categories as $category ) {
      if ( $category['id'] == $categoryid )
        return $category;

      $ret = $this->searchCategoryTree(
        $organizationid, $categoryid, $category['children']
      );

      if ( $ret )
        return $ret;
    }

    return $ret;
  }

}
