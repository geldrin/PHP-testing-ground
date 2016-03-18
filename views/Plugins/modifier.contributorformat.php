<?php
include_once("modifier.nameformat.php");

function smarty_modifier_contributorformat( $presenters, $withjobs = true ) {
  
  if ( empty( $presenters ) )
    return '';

  $names    = array();
  $language = \Springboard\Language::get();

  foreach( $presenters as $presenter ) {
    
    $name = smarty_modifier_nameformat( $presenter, true );
    
    if ( $withjobs and !empty( $presenter['jobs'] ) ) {
      
      $jobs = array();
      foreach( $presenter['jobs'] as $job ) {
        
        if ( strlen( trim( $job['nameshort'] ) ) )
          $joborganization = $job['nameshort'];
        elseif( strlen( trim( $job['name'] ) ) )
          $joborganization = $job['name'];
        else
          $joborganization = '';
        
        $jobname         = trim( $job['job'] );
        $joborganization = trim( $joborganization );
        
        if ( strlen( $jobname ) and strlen( $joborganization ) )
          $jobs[] = $jobname . ' - ' . $joborganization;
        elseif( strlen( $jobname ) )
          $jobs[] = $jobname;
        elseif ( strlen( $joborganization ) )
          $jobs[] = $joborganization;
        
      }
      
      $name .= ' (' . implode(', ', $jobs ) . ')';
      
    }
    
    $names[] = $name;
    
  }
  
  return implode(', ', $names );
  
}
