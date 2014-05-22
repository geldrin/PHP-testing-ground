<?php
namespace Admin\Organizations;

class Form extends \Springboard\Controller\Admin\Form {
  
  public function preAddElements( $action, $data = null ) {
    
    if ( $action == 'modify' and isset( $data['languages'] ) ) {
      
      $this->config['languages[]']['value'] = explode(',', $data['languages'] );
      unset( $data['languages'] );
      
    }
    
    return $data;
    
  }
  
  protected function updateAction() {
    
    $model  = $this->bootstrap->getModel( $this->controller->module );
    $values = $this->form->getElementValues( false );
    $values['languages'] = implode(',', $values['languages'] );
    
    $model->select( $values['id'] );
    $model->updateRow( $values );
    $this->runHandlers( $model );

    $cachekeys = array(
      'organizations-' . $model->row['domain'],
      'organizations-' . $model->row['staticdomain'],
      'organizations-' . $model->row['id'],
    );
    foreach( $cachekeys as $cachekey ) {
      $cache = $this->bootstrap->getCache( $cachekey, null );
      $cache->expire();
    }

    $this->controller->redirect('organizations/index');

  }
  
  protected function insertAction() {
    
    $orgModel  = $this->bootstrap->getModel('organizations');
    $values = $this->form->getElementValues( false );
    $values['languages'] = implode(',', $values['languages'] );
    
    $orgModel->insert( $values );
    
    if ( $values['parentid'] == 0 )
      $orgModel->setup();
    
    $this->runHandlers( $orgModel );
    $this->controller->redirect('organizations/index');
    
  }
  
}
