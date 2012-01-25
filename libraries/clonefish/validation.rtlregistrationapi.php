<?php

class rtlregistrationapiValidation extends validation {
  var $usersObj;
  
  function rtlregistrationapiValidation() {
    
    $this->usersObj = getObject('users');
    
  }
  
  // -------------------------------------------------------------------------
  function isValid() {
    
    $results = array();
    
    return $results;
    
  }

}

?>