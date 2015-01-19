<?php
namespace Visitor\Groups\Form;

class Create extends \Visitor\HelpForm {
  public $configfile = 'Create.php';
  public $template   = 'Visitor/genericform.tpl';
  public $needdb     = true;
  
  public function postSetupForm() {
    
    $l = $this->bootstrap->getLocalization();
    $this->controller->toSmarty['title'] = $l('groups', 'create_title');
    
  }
  
  public function onComplete() {
    
    $values     = $this->form->getElementValues( 0 );
    $groupModel = $this->bootstrap->getModel('groups');
    $user       = $this->bootstrap->getSession('user');
    
    $values = $this->checkDirectory( $values );
    if ( !$values )
      return;

    $values['timestamp'] = date('Y-m-d H:i:s');
    $values['userid']    = $user['id'];
    $groupModel->insert( $values );
    $groupModel->addUsers( array( $user['id'] ) );
    
    $this->controller->redirect(
      $this->application->getParameter(
        'forward',
        'groups/users/' . $groupModel->id . ',' .
        \Springboard\Filesystem::filenameize( $groupModel->row['name'] )
      )
    );
    
  }
  
  public function checkDirectory( &$values, $skipname = false ) {
    $user = $this->bootstrap->getSession('user');
    if (
         $values['source'] === '' or // non-directory, skip
         ( !$user['isadmin'] and !$user['isclientadmin'] )
       )
      return $values;

    $dir = $this->bootstrap->getModel('organizations_directories');
    $dir->select( $values['organizationdirectoryid'] );
    if ( empty( $dir->row ) )
      throw new \Exception("No organization_directory found");

    $ldapconfig = array(
      'server'   => $dir->row['server'],
      'username' => $dir->row['user'],
      'password' => $dir->row['password']
    );

    $ldap = $this->bootstrap->getLDAP( $ldapconfig );
    $results = $ldap->search(
      $values['organizationdirectoryldapdn'],
      '(objectClass=group)',
      array('objectguid', 'dn', 'cn', 'whenchanged', )
    );

    // csak az elso dologra vagyunk kivancsiak
    $error = true;
    foreach( $results as $result ) {
      if ( empty( $result ) )
        break;

      $error = false;
      if ( $skipname )
        $values['name'] = $ldap::implodePossibleArray(' ', $result['cn'] );

      $values['organizationdirectoryldapwhenchanged'] = $ldap::getTimestamp(
        $ldap::implodePossibleArray(' ', $result['whenChanged'] )
      );
      break;
    }

    if ( $error ) {
      $l = $this->bootstrap->getLocalization();
      $this->form->addMessage( $l('groups', 'ldaperror') );
      $this->form->invalidate();
      return false;
    }

    return $values;

  }

}
