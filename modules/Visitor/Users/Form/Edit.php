<?php
namespace Visitor\Users\Form;
class Edit extends \Visitor\HelpForm {
  public $configfile = 'Edit.php';
  public $template = 'Visitor/Users/Edit.tpl';
  public $needdb = true;
  public $userModel;
  public $user;
  public $basefields = array(
    'nickname', 'nameprefix', 'namefirst', 'namelast', 'nameformat',
    'password', 'confirmpassword', 'externalid', 'departments',
    'needtimestampdisabledafter', 'timestampdisabledafter',
    'organizationaffiliation',
  );

  // a non-directory csoportok az organizationtol, a config/Edit.php-bol toltodik
  // egy lookup table, a kulcsai group.id az ertekei boolean
  public $localGroups = array();
  private $groupHTML = '';

  public function init() {
    
    parent::init();
    $l               = $this->bootstrap->getLocalization();
    $this->userModel = $this->controller->modelOrganizationAndIDCheck(
      'users',
      $this->application->getNumericParameter('id')
    );
    $this->values    = $this->userModel->row;
    $this->values['lastloggedinipaddress'] = trim(
      str_replace( 'REMOTE_ADDR:', '', $this->values['lastloggedinipaddress'] )
    );
    unset( $this->values['password'] );
    
    $this->values['permissions'] = array();
    foreach( $l->getLov('permissions') as $k => $v ) {
      
      if ( $this->values[ $k ] )
        $this->values['permissions'][] = $k;

    }

    if ( $this->values['timestampdisabledafter'] ) {
      $this->values['needtimestampdisabledafter'] = 1;
      $this->values['timestampdisabledafter']     =
        substr( $this->values['timestampdisabledafter'], 0, 16 )
      ;
    }

    $this->controller->toSmarty['title'] = $l('users', 'modify_title');
    $this->controller->toSmarty['channels'] =
      $this->userModel->getRecordingsProgressWithChannels(
        $this->controller->organization['id']
      )
    ;
    
    $this->controller->toSmarty['invitations'] =
      $this->userModel->getInvitations(
        $this->controller->organization['id']
      )
    ;

  }
  
  public function onComplete() {
    
    $values = $this->form->getElementValues( 0 );
    $crypt  = $this->bootstrap->getEncryption();
    $l      = $this->bootstrap->getLocalization();

    // a kikapcsolt mezoket nem kuldi a browser, unset
    foreach( $this->config as $field => $conf ) {
      if ( !isset( $conf['html'] ) or $conf['html'] != 'disabled="disabled"' )
        continue;

      $field = trim( $field, '[]');
      unset( $values[ $field ] );
    }

    // ezeket a user sohase valtoztathatja
    unset(
      $values['email'],
      $values['lastloggedin'],
      $values['lastloggedinipaddress']
    );

    // nem localisan regisztralt usereknel nem engedunk permissiont allitani
    if ( $this->userModel->row['source'] and $this->userModel->row['source'] !== 'local' ) {

      foreach( $this->basefields as $field )
        unset( $values[ $field ] );

    } else {

      $this->userModel->clearDepartments();
      if ( isset( $_REQUEST['departments'] ) and !empty( $values['departments'] ) )
        $this->userModel->addDepartments( $values['departments'] );

    }

    $this->userModel->clearLocalGroups( array_keys( $this->localGroups ) );
    if ( isset( $_REQUEST['groups'] ) and !empty( $values['groups'] ) ) {
      foreach( $values['groups'] as $key => $groupid ) {
        if ( !isset( $this->localGroups[ $groupid ] ) )
          unset( $values['groups'][ $key ] );
      }

      // lekezeli ha ures a tomb
      $this->userModel->addGroups( $values['groups'] );
    }

    unset( $values['departments'], $values['groups'] );

    foreach( $l->getLov('permissions') as $k => $v ) {
      
      if ( isset( $_REQUEST['permissions'][ $k ] ) and in_array( $k, $values['permissions'] ) )
        $values[ $k ] = 1;
      else
        $values[ $k ] = 0;
      
    }

    if ( !@$values['password'] )
      unset( $values['password'] );
    else
      $values['password'] = $crypt->getPasswordHash( $values['password'] );
    
    if ( !@$values['needtimestampdisabledafter'] )
      $values['timestampdisabledafter'] = null;

    $this->userModel->updateRow( $values );
    
    $forward = $this->application->getParameter('forward', 'users/admin');
    $this->controller->redirectWithMessage( $forward, $l('users', 'usermodified') );
    
  }
  
  public function setupConfig( &$config ) {
    $groups = $this->userModel->getGroups( $this->controller->organization['id'] );

    if ( empty( $groups ) ) {
      unset( $config['groups[]'] );
      return $config;
    }

    $values     = array(); // ahol a user tag
    $groupAssoc = array(); // groupid -> groupnev hashmap
    $html       = array(); // checkboxok
    $usedids    = array(); // a checkbox random id-k

    foreach( $groups as $group ) {
      // disabled az adott checkbox ha non-lokalis a group (activedirectorybol jon)
      if ( $group['source'] == 'directory' )
        $disabled = 'disabled="disabled"';
      else {
        $this->localGroups[ $group['id'] ] = true;
        $disabled = '';
      }

      // ha tagja a csoportnak akkor jeloljuk
      if ( $group['memberid'] ) {
        $values[ $group['id'] ] = true;
        $checked = 'checked="checked"';
      } else
        $checked = '';

      // adjuk at clonefishnek a biztonsag kedveert, de manualisan generaljuk a htmlt
      $groupAssoc[ $group['id'] ] = $group['name'];
      while(true) {
        $randomid = mt_rand( 10000, 20000 ); // az intervallum nem fedi a CF-t
        if ( isset( $usedids[ $randomid ] ) )
          continue;

        $usedids[ $randomid ] = true;
        break;
      }

      $checkbox = strtr(
        '<input %disabled%%checked% id="checkbox%randomid%" type="checkbox" ' .
        'name="groups[%value%]" value="%value%"/>',
        array(
          '%disabled%' => $disabled,
          '%checked%'  => $checked,
          '%randomid%' => $randomid,
          '%value%'    =>
            // paranoidsagbol
            htmlspecialchars( $group['id'], ENT_QUOTES, 'UTF-8', true )
          ,
        )
      );
      $title = htmlspecialchars( $group['name'], ENT_QUOTES, 'UTF-8', true );
      $label = '<label for="checkbox' . $randomid . '">' . $title . '</label>';
      $html[] = strtr(
        $this->checkboxitemlayout,
        array(
          '%level%'           => '1', // ez egy szintu mindig
          '%indent%'          => '',  // ergo indent sincs
          '%checkbox%'        => $checkbox,
          '%valuehtmlescape%' => $title,
          '%label%'           => $label,
        )
      );
    }

    $this->groupHTML = implode( "\n", $html );
    $config['groups[]']['values'] = $groupAssoc;
    $config['groups[]']['value']  = array_keys( $values );

    return $config;
  }

  private function postProcessForm( $html ) {
    return str_replace('%GROUPHTML%', $this->groupHTML, $html );
  }

  // override
  public function displayForm( $submitted ) {
    $this->assignHelp();
    if ( strtolower( $this->exportmethod ) == 'getvars' )
      $this->controller->toSmarty['form'] = $this->form->getVars();
    else
      $this->controller->toSmarty['form'] = $this->form->getHTML();

    $this->controller->toSmarty['form'] =
      $this->postProcessForm( $this->controller->toSmarty['form'] )
    ;

    $this->controller->smartyoutput( $this->template );

  }

}
