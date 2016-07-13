<?php
namespace Admin\Privileges;

class Form extends \Springboard\Controller\Admin\Form {

  protected function updateAction() {
    $model  = $this->bootstrap->getModel( $this->controller->module );
    $values = $this->form->getElementValues( false );
    $model->select( $values['id'] );
    $model->updateRow( $values );
    $model->setRoles( @$_REQUEST['roles'] );
    $this->runHandlers( $model );

    $this->controller->redirect('privileges/index');
  }

  protected function insertAction() {
    $model  = $this->bootstrap->getModel( $this->controller->module );
    $values = $this->form->getElementValues( false );
    $model->insert( $values );
    $model->setRoles( @$_REQUEST['roles'] );

    $this->runHandlers( $model );
    $this->controller->redirect('privileges/index');
  }

}
