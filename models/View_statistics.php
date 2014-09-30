<?php
namespace Model;

class View_statistics extends \Springboard\Model {
  
  public function populateStreamInfo( &$values ) {
    if ( !isset( $values['url'] ) )
      return $values;

    $streamdata = parse_url( $values['url'] );
    unset( $values['url'] );

    $values['streamscheme'] = $streamdata['scheme'];
    $values['streamserver'] = $streamdata['host'];
    $values['streamurl']    = $streamdata['path'];

    return $values;
  }

}
