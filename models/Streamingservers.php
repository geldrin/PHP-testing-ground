<?php
namespace Model;

class Streamingservers extends \Springboard\Model {
  public $cachetimeoutseconds = 300;
  public $defaultservers      = array();
  
  public function getServerByClientIP( $ip, $type  ) {
    
    // TODO organizationid?
    if ( !$type )
      throw new \Exception("No type specified for the streaming servers!");
    
    $types   = array('live|ondemand');
    $types[] =  $type;
    $ip      = $this->db->qstr( $ip );
    // csak ipv4-et supportolunk!
    $query = "
      SELECT
        ss.id,
        ss.server,
        ss.serverip,
        ss.servicetype
      FROM
        cdn_streaming_servers AS ss,
        cdn_client_networks   AS cn,
        cdn_servers_networks  AS sn
      WHERE
        INET_ATON(cn.ipaddressstart) <= INET_ATON($ip) AND
        INET_ATON(cn.ipaddressend)   >= INET_ATON($ip) AND
        cn.id                        = sn.clientnetworkid AND
        sn.streamingserverid         = ss.id AND
        cn.disabled                  = 0 AND
        ss.disabled                  = 0 AND
        ss.servicetype IN('" . implode("', '", $types ) . "')
      GROUP BY ss.server
      ORDER BY RAND()
      LIMIT 1
    ";
    
    try {
      $serverselected = $this->db->getRow( $query );
    } catch ( \Exception $e ) {
      return $this->getDefaultServer( $types );
    }
    
    // No specific streaming server was found for source IP. Return default server
    if ( empty( $serverselected ) )
      return $this->getDefaultServer( $types );
    
    return $serverselected['server'];
    
  }
  
  protected function getRandomArrayValue( &$array, $index ) {
    return $array[ array_rand( $array ) ][ $index ];
  }
  
  public function getDefaultServer( $types ) {
    
    $defaultcacheindex = implode('-', $types );
    if ( isset( $this->defaultservers[ $defaultcacheindex ] ) )
      return $this->getRandomArrayValue(
        $this->defaultservers[ $defaultcacheindex ],
        'server'
      );
    
    $cache = $this->bootstrap->getCache(
      'defaultstreamingservers-' . $defaultcacheindex,
      $this->cachetimeoutseconds,
      true
    );
    
    if ( $cache->expired() ) {
      
      $query = "
        SELECT
          ss.id,
          ss.server,
          ss.serverip,
          ss.servicetype,
          ss.default
        FROM
          cdn_streaming_servers AS ss
        WHERE
          ss.default  = 1 AND
          ss.disabled = 0 AND
          ss.servicetype IN('" . implode("', '", $types ) . "')
      ";
      
      try {
        $defaultservers = $this->db->getArray( $query );
      } catch ( \Exception $e ) {
        return $this->bootstrap->config['fallbackstreamingserver'];
      }
      
      if ( empty( $defaultservers ) ) {
        
        $d = \Springboard\Debug::getInstance();
        $d->log(
          false,
          false,
          'No default streaming servers for types: ' . $defaultcacheindex,
          $this->bootstrap->production
        );
        
        return $this->bootstrap->config['fallbackstreamingserver'];
        
      }
      
      $cache->put( $defaultservers );
      
    } else
      $defaultservers = $cache->get();
    
    $this->defaultservers[ $defaultcacheindex ] = $defaultservers;
    return $this->getRandomArrayValue(
      $this->defaultservers[ $defaultcacheindex ],
      'server'
    );
    
  }
  
}