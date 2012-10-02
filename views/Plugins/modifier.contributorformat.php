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
        
        if ( $language == 'en' and strlen( trim( $job['jobenglish'] ) ) )
          $jobname = $job['jobenglish'];
        else
          $jobname = $job['joboriginal'];
        
        if ( strlen( trim( $job['nameshort'] ) ) )
          $jobname .= ' - ' . $job['nameshort'];
        elseif( strlen( trim( $job['name'] ) ) )
          $jobname .= ' - ' . $job['name'];
        
        $jobs[] = $jobname;
        
      }
      
      $name .= ' (' . implode(', ', $jobs ) . ')';
      
    }
    
    $names[] = $name;
    
  }
  
  return implode(', ', $names );
  
}
