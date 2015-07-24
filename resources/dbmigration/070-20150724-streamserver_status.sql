    ALTER TABLE  `cdn_streaming_servers`
    ADD  `lastreportid` INT NOT NULL DEFAULT  '0',
    ADD  `salt` text,
    ADD  `load_cpu_min5` DECIMAL( 4, 4 ) NOT NULL DEFAULT  '0',
    ADD  `load_clients_http` INT NOT NULL DEFAULT  '0',
    ADD  `load_clients_https` INT NOT NULL DEFAULT  '0',
    ADD  `load_clients_rtmp` INT NOT NULL DEFAULT  '0',
    ADD  `network_traffick_in` INT NOT NULL DEFAULT  '0',
    ADD  `network_traffick_out` INT NOT NULL DEFAULT  '0',
    ADD  `features_live_rtmp` INT NOT NULL DEFAULT  '0',
    ADD  `features_live_rtmpt` INT NOT NULL DEFAULT  '0',
    ADD  `features_live_rtmps` INT NOT NULL DEFAULT  '0',
    ADD  `features_live_hls` INT NOT NULL DEFAULT  '0',
    ADD  `features_live_hlss` INT NOT NULL DEFAULT  '0',
    ADD  `features_live_hds` INT NOT NULL DEFAULT  '0',
    ADD  `features_live_hdss` INT NOT NULL DEFAULT  '0',
    ADD  `features_ondemand_rtmp` INT NOT NULL DEFAULT  '0',
    ADD  `features_ondemand_rtmpt` INT NOT NULL DEFAULT  '0',
    ADD  `features_ondemand_rtmps` INT NOT NULL DEFAULT  '0',
    ADD  `features_ondemand_hls` INT NOT NULL DEFAULT  '0',
    ADD  `features_ondemand_hlss` INT NOT NULL DEFAULT  '0',
    ADD  `features_ondemand_hds` INT NOT NULL DEFAULT  '0',
    ADD  `features_ondemand_hdss` INT NOT NULL DEFAULT  '0';
