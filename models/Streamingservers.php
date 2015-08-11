<?php
namespace Model;

class Streamingservers extends \Springboard\Model {
  public $cachetimeoutseconds = 300;
  public $defaultservers      = array();
  public $table = 'cdn_streaming_servers';

  public function getServerByClientIP( $ip, $type  ) {
    
    // TODO organizationid?
    if ( !$type )
      throw new \Exception("No type specified for the streaming servers!");
    
    $extrawhere = '';
    $duration = $this->bootstrap->config['streamingserver_max_report_duration_minutes'];
    if ( $duration ) {
      $extrawhere = "AND
        ss.lastreporttimestamp IS NOT NULL AND
        ss.lastreporttimestamp >= DATE_SUB(NOW(), INTERVAL $duration MINUTE)
      ";
    }

    $types   = array('live|ondemand');
    $types[] =  $type;
    $ip      = $this->db->qstr( $ip );
    // csak ipv4-et supportolunk!
    $query = "
      SELECT
        ss.id,
        ss.server,
        ss.serverip,
        ss.servicetype,
        LOWER( ss.type ) AS `type`
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
        ss.serverstatus              = 'ok'
        ss.disabled                  = 0 AND
        ss.servicetype IN('" . implode("', '", $types ) . "')
        $extrawhere
      GROUP BY ss.server
      ORDER BY
        ROUND( ( network_traffick_out / network_interface_speed ) * 100 ) ASC,
        load_cpu_min5 ASC,
        RAND()
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
    
    return $serverselected;
    
  }
  
  protected function getRandomArrayValue( &$array ) {
    return $array[ array_rand( $array ) ];
  }
  
  public function getDefaultServer( $types ) {
    
    $defaultcacheindex = implode('-', $types );
    if ( isset( $this->defaultservers[ $defaultcacheindex ] ) )
      return $this->getRandomArrayValue(
        $this->defaultservers[ $defaultcacheindex ]
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
          ss.default,
          LOWER( ss.type ) AS `type`
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
        $defaultservers = array( $this->bootstrap->config['fallbackstreamingserver'] );
      }
      
      if ( empty( $defaultservers ) ) {
        
        $d = \Springboard\Debug::getInstance();
        $d->log(
          false,
          false,
          'No default streaming servers for types: ' . $defaultcacheindex,
          $this->bootstrap->production
        );
        
        $defaultservers = array( $this->bootstrap->config['fallbackstreamingserver'] );
        
      }
      
      $cache->put( $defaultservers );
      
    } else
      $defaultservers = $cache->get();
    
    $this->defaultservers[ $defaultcacheindex ] = $defaultservers;
    return $this->getRandomArrayValue(
      $this->defaultservers[ $defaultcacheindex ]
    );
    
  }
  
  public function getServerByHost( $server ) {
    $server = $this->db->qstr( $server );
    $ret    = $this->db->getRow("
      SELECT *
      FROM cdn_streaming_servers
      WHERE server = $server
      LIMIT 1
    ");

    if ( !empty( $ret ) ) {
      $this->id = $ret['id'];
      $this->row = $ret;
    }

    return $ret;
  }
}
