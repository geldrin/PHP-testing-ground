    ALTER TABLE  `cdn_streaming_servers`
    ADD  `lastreportid` INT NOT NULL DEFAULT  '0',
    ADD  `salt` text,
    ADD  `cpuload` DECIMAL( 4, 4 ) NOT NULL DEFAULT  '0',
    ADD  `clientshttp` INT NOT NULL DEFAULT  '0',
    ADD  `clientshttps` INT NOT NULL DEFAULT  '0',
    ADD  `clientsrtmp` INT NOT NULL DEFAULT  '0',
    ADD  `networktraffickin` INT NOT NULL DEFAULT  '0',
    ADD  `networktraffickout` INT NOT NULL DEFAULT  '0',
    ADD  `networkping` INT NOT NULL DEFAULT  '0';
