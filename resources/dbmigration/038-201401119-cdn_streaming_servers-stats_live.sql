ALTER TABLE  `cdn_streaming_servers` ADD  `description` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL AFTER  `shortname`;
ALTER TABLE  `cdn_streaming_servers` ADD  `location` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL AFTER  `country`;
