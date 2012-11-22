<?php

if ( !empty( $this->uploads ) ) {
  
  $availableuploads = array (
    'availableuploads' => array(
      'type' => 'text',
      'rowlayout' => '
        <td colspan="2" id="availableuploads" class="formrow">
          <h3>' . $l('recordings', 'availableuploads') . '</h3>
          <div class="element">
            %element%
          </div>
        </td>
      ',
      'value' => ''
    )
  );
  
  $html = array();
  $url  = \Springboard\Language::get() .
    '/recordings/cancelupload/%uploadid%?forward=' .
    rawurlencode( $this->bootstrap->scheme . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'] )
  ;
  
  foreach( $this->uploads as $upload ) {
    
    $filename = htmlspecialchars( $upload['filename'], ENT_QUOTES, 'UTF-8', true );
    
    if ( mb_strlen( $filename ) > 60 )
      $filename = mb_strcut( $filename, 0, 60, 'UTF-8' ) . '...';
    
    $html[] = '
      <div class="actions">
        <a class="cancelupload confirm" title="' . $l('recordings', 'cancelupload') . '" ' .
          'href="' . str_replace( '%uploadid%', $upload['id'], $url ) . '">' .
          '<span></span>' . $l('recordings', 'cancelupload') .
        '</a>
      </div>
      <div class="filename">' . $filename . '</div>
    ';
    
  }
  
  $availableuploads['availableuploads']['value'] =
    '<ul><li>' . implode( $html, '</li><li>' ) . '</li></ul>'
  ;
  
  $config = \Springboard\Tools::insertAfterKey( $config, $availableuploads, 'fs1' );
  
}
