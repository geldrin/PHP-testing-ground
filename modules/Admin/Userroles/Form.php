<?php
namespace Admin\Userroles;

class Form extends \Springboard\Controller\Admin\Form {

  protected function updateAction() {
    $model  = $this->bootstrap->getModel( $this->controller->module );
    $values = $this->form->getElementValues( false );
    $model->select( $values['id'] );
    $model->updateRow( $values );
    $model->setPrivileges( @$_REQUEST['privileges'] );
    $this->runHandlers( $model );

    $this->controller->redirect('userroles/index');
  }

  protected function insertAction() {
    $roleModel  = $this->bootstrap->getModel('userroles');
    $values = $this->form->getElementValues( false );
    $roleModel->insert( $values );
    $roleModel->setPrivileges( @$_REQUEST['privileges'] );

    $this->runHandlers( $roleModel );
    $this->controller->redirect('userroles/index');
  }

}
