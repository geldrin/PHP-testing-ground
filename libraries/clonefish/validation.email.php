<?php

class emailValidation extends validation {
 
  var $form;         // form 

  var $domaincheck = true;
  var $verify = true;
  var $helo;
  var $probe;
  var $dns;
  var $debug = false;

  // -------------------------------------------------------------------------
  function isValid() {

    $results = Array();
    
    if ( $this->checkDependencyPHP() ) {
     
      include( CLONEFISH_DIR . 'VerifyEmailAddress.php' );

      if ( 
           ( $this->domaincheck || $this->helo || $this->probe ) &&
           !count( $this->dns )
         )
        die( 
          sprintf( 
            STR_CLONEFISH_CONFIG_EMAIL_VALIDATION_DNS_MISSING, 
            $this->element->getname() 
          )
        );

      if ( $this->debug )
        $GLOBALS['debug'] = 1;

      $errors = validateEmail( 
        $this->element->getValue( 0 ),
        $this->domaincheck, $this->verify, $this->helo, $this->probe, true,
        $this->dns
      );

      if ( $errors !== false ) {
        $message = 
          sprintf(
            $this->selecthelp( $this->element, STR_EMAIL_VALIDATION_FAILED ),
            $this->element->getdisplayname(),
            trim( $errors )
          );
        $results[] = $message;
        $this->element->addmessage( $message );
      }

    }

    return $results;

  } 

}

?>