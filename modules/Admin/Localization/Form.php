<?php
namespace Admin\Localization;

class Form extends \Springboard\Controller\Admin\Form {
  public $modules = array();
  
  public function init() {
    $this->modules = $this->controller->getModulesWithLocales();
    return parent::init();
  }
  
  public function route() {
    
    switch( $this->action ) {
      
      case 'update':
      case 'modify':
        
        if ( !$this->validateID() )
          $this->handleNotFound();
        
        $this->loadConfig();
        $this->prepareConfig('update', 'modify');
        $this->preSetupForm();
        
        $submit     = $this->action == 'update';
        $action     = $this->action . 'Action';
        $this->form = $this->getForm( $this->action );
        
        if ( $submit )
          $values = $this->application->getParameters();
        else
          $values = array();
        
        $this->form->addElements( $this->config, $values, false );
        $this->postSetupForm( $this->action );
        
        if ( $submit and $this->form->validate() )
          $this->$action();
        
        $this->displayForm( $submit );
        break;
      
      default:
        $this->handleNotFound();
        break;
      
    }
    
  }
  
  public function validateID() {
    
    return in_array(
      $this->application->getParameter('id'),
      $this->modules,
      true
    );
    
  }
  
  public function loadConfig() {
    
    $config = $this->generateConfigForModule( $this->application->getParameter('id') );
    if ( $this->xsrfprotect )
      $config = $this->handleXSRFConfig( $config );
    
    $this->config = $config;
    
    if ( $this->propagate )
      $this->controller->propagate = array_merge( $this->controller->propagate, $this->propagate );
    
    if ( isset( $this->hidesidebar ) )
      $this->controller->hidesidebar = $this->hidesidebar;
    
    if ( isset( $this->hidenavigation ) )
      $this->controller->hidenavigation = $this->hidenavigation;
    
    if ( isset( $this->hideheading ) )
      $this->controller->hideheading = $this->hideheading;
    
    $this->controller->hidecopypaste = $this->hidecopypaste;
    
  }
  
  public function generateConfigForModule( $module ) {
    
    if ( isset( $locale[ $module ] ) )
      return $locale[ $module ];
    
    $config = array(
      'id' => array(
        'type'   => 'inputHidden',
        'value'  => $module,
      ),
    );
    
    $localepath   = '%sVisitor/%s/Locale/%s.ini';
    $missingfiles = array(); // what files to create
    $presentfile  = null; // what file to use when creating
    $mainlanguage = reset( $this->bootstrap->config['languages'] );
    $languages    = $this->bootstrap->config['languages']; // to type less
    $data         = array();
    
    // make sure we have a file for every language and its writable
    foreach( $languages as $language ) {
      
      $file = sprintf( $localepath, $this->bootstrap->config['modulepath'], $module, $language );
      
      if ( !file_exists( $file ) ) {
        
        $missingfiles[] = $file;
        break;
        
      }
      
      if ( !$presentfile )
        $presentfile = $file;
      
      if ( !is_writable( $file ) )
        throw new \Exception("The file is not writable: $file");
      
    }
    
    if ( !$presentfile ) // cannot happen
      throw new \Exception("Failed to find at least one locale file for module: $module");
    
    umask(0);
    foreach( $missingfiles as $file ) {
      
      copy( $presentfile, $file );
      chmod( $file, 0664 );
      
    }
    
    // load the localizations
    foreach( $languages as $language ) {
      
      $file = sprintf( $localepath, $this->bootstrap->config['modulepath'], $module, $language );
      $data[ $language ] = \Springboard\Localization::loadConfig( $file );
      
    }
    
    // and make sure every key is present in every language
    foreach( $languages as $language ) {
      
      foreach( $data[ $language ] as $k => $v ) {
        
        if ( is_array( $v ) )
          throw new \Exception("Handling locales with subsections is not supported at this time");
          
        foreach( $languages as $lang ) {
          
          if ( !isset( $data[ $lang ][ $k ] ) )
            $data[ $lang ][ $k ] = '';
          
        }
        
      }
      
    }
    
    // build the form
    foreach( $data[ $mainlanguage ] as $key => $unused ) {
      
      foreach( $languages as $language ) {
        
        $value     = $data[ $language ][ $key ];
        $configkey = sprintf("localization[%s][%s]", $language, $key );
        $config[ $configkey ] = array(
          'displayname' => ( $language == $mainlanguage )? $key: '', // the name only once
          'prefix'      => $language . ' ',
          'value'       => $value,
          'html'        => 'class="localefield"',
          'type'        => ( strpos( $value, "\n" ) !== false )? 'textarea': 'inputText',
        );
        
      }
      
    }
    
    return $config;
    
  }
  
  protected function updateAction() {
    
    $data = $this->application->getParameter('localization');
    
    foreach( $this->bootstrap->config['languages'] as $language ) {
      
      if ( !isset( $data[ $language ] ) )
        throw new \Exception("Sanity check failed to find data for language: $language");
        
      foreach( $data[ $language ] as $k => $v ) {
        
        $key = sprintf('localization[%s][%s]', $language, $k );
        if ( !isset( $this->config[ $key ] ) )
          throw new \Exception("Sanity check for form element failed: $key");
        
        if ( !$this->writeLocaleFile( $language, $data[ $language ] ) )
          throw new \Exception("Failed to write localization file for language: $language");
        
      }
      
    }
    
    $this->controller->redirect(
      $this->module . '/modify?id=' . $this->application->getParameter('id') ,
      $this->controller->propagate
    );
    
  }
  
  protected function writeLocaleFile( $language, &$data ) {
    
    $module = $this->application->getParameter('id');
    $blob   = ';generated by the Localization Admin module at ' . strftime('%c') . "\n";
    foreach( $data as $k => $v )
      $blob .= sprintf("%s=\"%s\"\n", $k, $v );
    
    $file = sprintf(
      "%sVisitor/%s/Locale/%s.ini",
      $this->bootstrap->config['modulepath'],
      $module,
      $language
    );
    
    return file_put_contents( $file, $blob );
    
  }
  
}
