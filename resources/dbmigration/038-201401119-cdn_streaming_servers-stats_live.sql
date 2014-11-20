ALTER TABLE  `cdn_streaming_servers` ADD  `description` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL AFTER  `shortname`;
ALTER TABLE  `cdn_streaming_servers` ADD  `location` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL AFTER  `country`;
ALTER TABLE  `statistics_live_5min` ADD  `streamingserverid` INT(11) UNSIGNED NOT NULL AFTER  `livefeedstreamid`;
ALTER TABLE  `statistics_live_hourly` ADD  `streamingserverid` INT(11) UNSIGNED NOT NULL AFTER  `livefeedstreamid`;
ALTER TABLE  `statistics_live_daily` ADD  `streamingserverid` INT(11) UNSIGNED NOT NULL AFTER  `livefeedstreamid`;