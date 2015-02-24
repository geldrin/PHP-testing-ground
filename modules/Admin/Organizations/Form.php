<?php
namespace Admin\Organizations;

class Form extends \Springboard\Controller\Admin\Form {
  
  public function preAddElements( $action, $data = null ) {

    $this->bootstrap->config['version'] = '_v' . md5(
      $this->bootstrap->config['version'] . time()
    );

    if ( $action == 'modify' and isset( $data['languages'] ) ) {
      
      $this->config['languages[]']['value'] = explode(',', $data['languages'] );
      unset( $data['languages'] );

    }
    
    if ( $action == 'modify' ) {
      // default ertekeket toltjuk hogy megjelenjen
      foreach( array('header', 'footer') as $type ) {
        $key = 'layout' . $type;

        if ( !$data or $data[ $key ] )
          continue;

        $default = file_get_contents(
          $this->bootstrap->config['templatepath'] . 'Visitor/' .
          '_layout_' . $type . '.tpl'
        );

        $data[ $key ] = $default;
      }
    }

    return $data;
    
  }
  
  protected function updateAction() {
    
    $model  = $this->bootstrap->getModel( $this->controller->module );
    $values = $this->form->getElementValues( false );
    $values['languages'] = implode(',', $values['languages'] );
    $values = $this->clearDefaultLayouts( $values );

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
    $values = $this->clearDefaultLayouts( $values );
    $orgModel->insert( $values );
    
    if ( $values['parentid'] == 0 )
      $orgModel->setup();
    
    $this->runHandlers( $orgModel );
    $this->controller->redirect('organizations/index');
    
  }

  private function clearDefaultLayouts( &$values ) {
    foreach( array('header', 'footer') as $type ) {
      $default = trim( $this->getLayoutDefault( $type ) );
      $key     = 'layout' . $type;
      // normalize line endings
      $val     = str_replace( "\r\n", "\n", trim( $values[ $key ] ) );
      if ( $val == $default )
        unset( $values[ $key ] );
    }

    return $values;
  }

  private function getLayoutDefault( $type ) {
    return file_get_contents(
      $this->bootstrap->config['templatepath'] . 'Visitor/' .
      '_layout_' . $type . '.tpl'
    );
  }

}
