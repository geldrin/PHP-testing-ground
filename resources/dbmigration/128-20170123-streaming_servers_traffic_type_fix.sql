ALTER TABLE  `cdn_streaming_servers` CHANGE  `network_traffic_out`  `network_traffic_out` INT( 20 ) UNSIGNED NOT NULL DEFAULT  '0';

ALTER TABLE  `cdn_streaming_servers` CHANGE  `network_traffic_in`  `network_traffic_in` INT( 20 ) UNSIGNED NOT NULL DEFAULT  '0';
