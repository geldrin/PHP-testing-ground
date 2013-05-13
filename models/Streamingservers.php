<?php
namespace Model;

class Streamingservers extends \Springboard\Model {
  public $table = 'cdn_streaming_servers';
  
  public function getServerByClientIP( $ip, $types ) {
    // TODO organizationid?
    if ( empty( $types ) )
      throw new \Exception("No types specified for the streaming servers!");
      
    $hostname = $this->bootstrap->getSession('hostname');
    if ( $hostname['value'] === null ) {
      
      $hostname['value'] = gethostbyaddr( $ip );
      if ( !$hostname['value'] or $hostname['value'] == $ip )
        $hostname['tld'] = $hostname['value'] = false;
      else {
        
        $pos = strrpos( $hostname['value'], '.' );
        $hostname['tld'] = substr( $hostname['value'], $pos );
        
      }
      
    }
    
    $where = array(
      "cdns.servicetype IN('" . implode("', '", $types ) . "')",
      "cdns.disabled = '0'",
    );
    
    if ( $hostname['tld'] )
      $where[] = "cdns.country = " . $this->db->qstr( $hostname['tld'] );
    else
      $where[] = "cdns.default = '1'";
    
    
  }
  
}