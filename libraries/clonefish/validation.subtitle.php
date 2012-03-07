<?php

/**
 * Clonefish form generator class 
 * (c) phpformclass.com, Dots Amazing
 * All rights reserved.
 * 
 * @copyright  2010 Dots Amazing
 * @link       http://phpformclass.com
 * @package    clonefish
 * @subpackage validation
 */

/* 
 * Validation
 * @package clonefish
 * @subpackage validationTypes
 */
class subtitleValidation extends validation {

  var $settings = Array();

  // settings coming from the settings array

  var $form;      // form

  var $required = 1;
  
  // -------------------------------------------------------------------------
  function isValid() {

    $results = Array();

    if ( $this->checkDependencyPHP() ) {
      
      $name = $this->element->getName();

      if ( isset( $_FILES[ $name ] ) ) {

        if (
             !isset( $_FILES[ $name ] ) or
             ( $_FILES[ $name ]['tmp_name'] == 'none' ) or
             ( $_FILES[ $name ]['size'] == '0' )
           )
          $file['error'] = UPLOAD_ERR_NO_FILE;
        else
          $file = $_FILES[ $name ];

        switch ( $file['error'] ) {

          case UPLOAD_ERR_INI_SIZE:
            break;

          case UPLOAD_ERR_PARTIAL:
            break;

          case UPLOAD_ERR_NO_FILE:

            if ( $this->required && !$this->element->getValue( 0 ) ) {

              $message = sprintf( 
                $this->selecthelp( $this->element, CF_STR_FILE_REQUIRED ), 
                $this->element->getDisplayName()
              );
              $results[] = $message;
              $this->element->addMessage( $message );

            }
            
            break;

          case UPLOAD_ERR_OK:
            
            $this->element->_readContents();
            $converted = false;
            $boms      = array(
              'UTF-32BE' => chr(0x00) . chr(0x00) . chr(0xFE) . chr(0xFF),
              'UTF-32LE' => chr(0xFF) . chr(0xFE) . chr(0x00) . chr(0x00),
              'UTF-16BE' => chr(0xFE) . chr(0xFF),
              'UTF-16LE' => chr(0xFF) . chr(0xFE),
              'UTF-8' => chr(0xEF) . chr(0xBB) . chr(0xBF),
            );
            
            // REMOVE BYTE ORDER MARK
            foreach( $boms as $encoding => $bom ) {
              
              if ( substr( $this->element->value, 0, strlen( $bom ) ) == $bom ) {
                
                $this->element->value = substr( $this->element->value, strlen( $bom ) );
                
                if ( $encoding != 'UTF-8' ) {
                  
                  $this->element->value = mb_convert_encoding( $this->element->value, 'UTF-8', $encoding );
                  $converted            = true;
                  
                }
                
                break;
                
              }
              
            }
            
            if ( !$converted )
              $this->element->value = mb_convert_encoding( $this->element->value, 'UTF-8', $this->mbGenerateEncodingList() );
            
            // SRT VALIDITY
            $chunks = preg_split( '/\r\n\r\n|\r\r|\n\n/', $this->element->value, NULL, PREG_SPLIT_NO_EMPTY );
            
            foreach( $chunks as $key => $chunk ) {
              
              /*
              if ( $key == 0 and $chunk == 'WEBVTT FILE' )
                continue;
                websrt formatumba a milisec az elvalaszto
              */
              // nem nezzuk hogy legyen is content a timestamp utan
              if ( !preg_match('/^\d+[\r\n]{1,2}\d{1,2}:\d{1,2}:\d{1,2}[,\.]\d{1,3}[ \t]\-\->[ \t]\d{1,2}:\d{1,2}:\d{1,2}[,\.]\d{1,3}([\r\n].+)*/msS', $chunk ) ) {
                
                $message = sprintf(
                  $this->selecthelp( $this->element, CF_STR_SUBTITLE_INVALID ),
                  $this->element->getDisplayName()
                );
                $results[] = $message;
                $this->element->addMessage( $message );
                
                unset( $chunks );
                return $results;
                
              }
              
            }

        }

      }
      else {
        // $_FILES[ $name ] was not set

        if ( $this->required && !$this->element->getValue( 0 ) ) {
          $message = sprintf( 
            $this->selecthelp( $this->element, CF_STR_FILE_REQUIRED ), 
            $this->element->getDisplayName()
          );
          $results[] = $message;
          $this->element->addMessage( $message );

        }

      }

    }

    return $results;

  }
  
  function mbGenerateEncodingList() {
    
    $encodings      = mb_detect_order();
    $allencodings   = mb_list_encodings();
    $stripencodings = array(
      'pass', 'auto', 'wchar', 'byte2be', 'byte2le',
      'byte4be', 'byte4le', 'BASE64', 'UUENCODE', 'HTML-ENTITIES',
      'Quoted-Printable'
    );
    
    foreach( $allencodings as $key => $value ) {
      
      if ( in_array( $value, $stripencodings ) )
        unset( $allencodings[ $key ] );
      
    }
    
    array_unshift( $encodings, 'ISO-8859-2');
    $encodings = array_merge( $encodings, $allencodings );
    $encodings = array_unique( $encodings );
    return $encodings;
    
  }

}

?>