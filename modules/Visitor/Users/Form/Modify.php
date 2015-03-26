<?php
namespace Visitor\Users\Form;
class Modify extends \Visitor\HelpForm {
  public $configfile = 'Modify.php';
  public $template = 'Visitor/genericform.tpl';
  public $needdb = true;
  public $userModel;
  public $user;
  public $basefields = array(
    'nickname', 'nameprefix', 'namefirst', 'namelast', 'nameformat',
    'password', 'confirmpassword',
  );

  public function init() {
    
    parent::init();
    $l               = $this->bootstrap->getLocalization();
    $this->user      = $this->bootstrap->getSession('user');
    $this->userModel = $this->controller->modelIDCheck('users', $this->user['id'] );
    $this->values    = $this->userModel->row;
    unset( $this->values['password'] );
    
    $this->values['permissions'] = array();
    foreach( $l->getLov('permissions') as $k => $v ) {
      
      if ( $this->values[ $k ] )
        $this->values['permissions'][] = $k;
      
    }
    
  }
  
  public function onComplete() {
    
    $values = $this->form->getElementValues( 0 );
    $crypt  = $this->bootstrap->getEncryption();
    $l      = $this->bootstrap->getLocalization();
    
    // nem itt valtoztatjuk a jogosultsagokat, csak mutatjuk oket a usernek
    unset(
      $values['permissions'],
      $values['groups'],
      $values['departments']
    );

    // a kikapcsolt mezoket nem kuldi a browser, unset
    foreach( $this->config as $field => $conf ) {
      if ( !isset( $conf['html'] ) or $conf['html'] != 'disabled="disabled"' )
        continue;

      $field = trim( $field, '[]');
      unset( $values[ $field ] );
    }

    if ( !@$values['password'] )
      unset( $values['password'] );
    else
      $values['password'] = $crypt->getPasswordHash( $values['password'] );
    
    if (
         isset( $_FILES['avatarfilename'] ) and
         $_FILES['avatarfilename']['error'] == 0 and
         $this->userModel->canUploadAvatar()
       ) {
      
      $values['avatarfilename'] = $_FILES['avatarfilename']['name'];
      $dest =
        $this->bootstrap->config['useravatarpath'] .
        $this->userModel->id . '.' .
        \Springboard\Filesystem::getExtension( $values['avatarfilename'] )
      ;
      
      if ( !move_uploaded_file( $_FILES['avatarfilename']['tmp_name'], $dest ) )
        throw new \Exception("Failed moving avatarfile: " . var_export( $_FILES, true ) );
      
      $values['avatarstatus']   = 'uploaded';
      $values['avatarsourceip'] = $this->bootstrap->config['node_sourceip'];
      
    }

    if ( !empty( $values ) )
      $this->userModel->updateRow( $values );

    $this->userModel->registerForSession();

    $this->controller->redirectWithMessage('users/modify', $l('users', 'usermodified') );
    
  }
  
}
