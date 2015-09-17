<?php

$config = array(
    // Node basics
    'server'                => 'stream.vsq.streamnet.hu',
    'interface'             => 'eth0',
    // Capacity
    'max_streaming_load'    => 1000,     // Max number of clients
    'max_network_load'      => 1000,     // Max permitted network load
    // Node
//    'node_type'             => "nginx",
//    'nginx_status_url'      => "http://localhost/nginx_status",
//    'nginx_status_port'     => 80,
    'node_type'             => "wowza",
    'wowza_status_url'      => "https://%s:8086/connectioncounts",
    'wowza_status_port'     => 8086,
    'wowza_user'            => "monitor",
    'wowza_password'        => "UMjjR7RtHY6Qynbh",
    'wowza_app_live'        => "devvsqlive",
    'wowza_app_ondemand'    => "devvsq",
    // Upstream servers
    'upstream_servers'      => array(),
    // Security
    'hash_salt'             => "aC3hRBCDq9BFQ6fUjFyAKv33p9admZnH",
    // Features: supported protocols
    'features' => array(
        'live' => array(
            'rtmp'  => true,
            'rtmpt' => true,
            'rtmps' => false,
            'hls'   => true,
            'hlss'  => false,
            'hds'   => true,
            'hdss'  => false
        ),
        'ondemand' => array(
            'rtmp'  => true,
            'rtmpt' => true,
            'rtmps' => false,
            'hls'   => true,
            'hlss'  => false,
            'hds'   => true,
            'hdss'  => false
        )
    ),

    // Other
    'api_url'               => "https://vsqdev.streamnet.hu/hu/api",    // Videosquare API URL
    'log_directory'         => "/var/www/dev.videosquare.eu/data/logs/jobs",                  // Log directory
    
);

?>
