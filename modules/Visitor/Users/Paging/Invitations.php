<?php
namespace Visitor\Users\Paging;

class Invitations extends \Visitor\Paging {
  protected $orderkey = 'default';
  protected $sort = array(
    'default'   => 'timestamp DESC, email',
    'relevancy' => 'relevancy, timestamp DESC, email',
  );
  protected $insertbeforepager = Array( 'Visitor/Users/Paging/InvitationsBeforepager.tpl' );
  protected $template = 'Visitor/Users/Paging/Invitations.tpl';
  protected $perpage  = 10;
  protected $invModel;
  protected $pagestoshow = 3;
  protected $searchterm;

  public function init() {
    
    $l                 = $this->bootstrap->getLocalization();
    $this->foreachelse = $l('users', 'invitation_foreachelse' );
    $this->title       = $l('users', 'invitations_title');
    $term = trim( $this->application->getParameter('term') );
    if ( mb_strlen( $term ) >= 2 ) {
      $this->searchterm = $term;
      $this->passparams['term'] = $term;
    }

  }
  
  protected function setupCount() {
    
    $this->invModel = $this->bootstrap->getModel('users_invitations');

    if ( $this->searchterm ) {
      return $this->itemcount = $this->invModel->getSearchCount(
        $this->searchterm,
        $this->controller->organization['id']
      );
    }

    $this->invModel->addFilter('organizationid', $this->controller->organization['id'] );
    return $this->itemcount = $this->invModel->getCount();
    
  }
  
  protected function getItems( $start, $limit, $orderby ) {

    if ( $this->searchterm )
      $data = $this->invModel->getSearchArray(
        $this->searchterm,
        $this->controller->organization['id'],
        $start, $limit, $this->sort['relevancy']
      );
    else
      $data = $this->invModel->getArray( $start, $limit, false, $orderby );

    return $data;

  }
  
}
