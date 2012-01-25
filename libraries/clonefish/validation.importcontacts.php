<?php

class importcontactsValidation extends validation {
 
  var $form; // form 
  
  // -------------------------------------------------------------------------
  function isValid() {

    $results = Array();
    
    if ( $this->checkDependencyPHP() ) {

      $values  = $this->form->getElementValues( 0 );
      
      switch ( $values['provider'] ) {

        case 'csv':
          
          $f = @fopen( $_FILES['file']['tmp_name'], 'r' );
          
          if ( is_resource( $f ) ) {
    
            $names           = Array();
            $emails          = Array();
            $returned_emails = Array();
            
            while ( $row = fgetcsv( $f, 4096, ';', '"' ) ) {
              $names[]  = $row[1];
              $emails[] = $row[0];
            }
            
            fclose( $f );

            if ( !count( $names ) || !count( $emails ) )
              $returned_emails = 3;
            else
              $returned_emails = Array( $names, $emails );
          
          }
          else
            $returned_emails = 3;

          $returned = $returned_emails;
          break;
            
        case 'msn':

          include( LIBPATH . 'import/dcl/scripts/importMsnm.class.php');
          $msn2 = new msn;
          $returned_emails = $msn2->qGrab( $values['email'], $values['password'] );
          if ( is_array( $returned_emails ) ) {
            $names  = Array();
            $emails = Array();
            foreach ( $returned_emails as $row ) {
              $names[]  = $row[1];
              $emails[] = $row[0];
            }
            $returned_emails = Array( $names, $emails );
          }
          else
            $returned_emails = 3;

          $returned = $returned_emails;
          break;

        case 'gmail':
        case 'freemail':
        case 'citromail':
        case 'indamail':
        case 'iwiw':
          include( LIBPATH . 'import/' . $values['provider'] . '.php');
          $_SESSION['iwiwlogin']    = $values['email'];
          $_SESSION['iwiwpassword'] = $values['password'];
          $returned = get_contacts( $values['email'] , $values['password'] );
          break;

        default:
          tools::log( null, null,
            'usersinviteexternal: unsupported provider: ' . $values['provider'], true
          );
          tools::go();
          break;
      }

      $_SESSION['importprovider'] = $values['provider'];

      switch ( $returned ) {

        case 1:
          $message =
            sprintf(
              $this->selecthelp( 
                $this->element, l('users','inviteexternal_loginfailed')
              ),
              $this->element->getdisplayname()
            );
          $results[] = $message;
          $this->element->addmessage( $message );
          break;
    
        case 2:
          $message =
            sprintf(
              $this->selecthelp( 
                $this->element, l('users','inviteexternal_missingcredentials')
              ),
              $this->element->getdisplayname()
            );
          $results[] = $message;
          $this->element->addmessage( $message );
          break;

        case 3:
          $message =
            sprintf(
              $this->selecthelp( 
                $this->element, l('users','inviteexternal_networkproblem')
              ),
              $this->element->getdisplayname()
            );
          $results[] = $message;
          $this->element->addmessage( $message );
          break;
      
        default:
          
          if ( !is_array( $returned ) ) {
            // library problem
            tools::log( null, null,
              'usersinviteexternal: not an array received: ' . $values['provider']
            );
            tools::go();
          }
          else {

            if ( !count( $returned ) ) {
              // empty addressbook
              $message =
                sprintf(
                  $this->selecthelp( 
                    $this->element, l('users','inviteexternal_emptyaddresbook')
                  ),
                  $this->element->getdisplayname()
                );
              $results[] = $message;
              $this->element->addmessage( $message );

            }
            else 
              // addressbook OK
              $_SESSION['importcontacts'] = $returned;

          }
          
          break;

      }

      return $results;

    }

  } 

}

?>