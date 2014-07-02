ALTER TABLE  `livefeed_streams` ADD  `indexphotofilename` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL AFTER  `status`;

ALTER TABLE  `encoding_groups` ADD  `description` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL AFTER  `default`;

ALTER TABLE  `encoding_groups` ADD  `islegacy` INT(10) UNSIGNED NOT NULL DEFAULT  '0' AFTER  `default`;
