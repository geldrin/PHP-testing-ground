ALTER TABLE  `recording_links` CHANGE  `number`  `number` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;

ALTER TABLE  `recording_links` CHANGE  `bitrate`  `bitrate` INT( 11 ) NULL DEFAULT NULL COMMENT 'Values: 64, 128, 192, 256, 384, 512, 768, 1024, 1280, 1536, 1920 and 2048 kbps (as well as 2560, 3072 and 4000 kbps for Content Servers equipped with the Premium Resolution option)';

ALTER TABLE  `recording_links` CHANGE  `alias`  `alias` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;

ALTER TABLE  `recording_links` CHANGE  `aliassecure`  `aliassecure` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;

ALTER TABLE  `recording_links` CHANGE  `apiserver`  `apiserver` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;

ALTER TABLE  `recording_links` CHANGE  `apiport`  `apiport` INT( 10 ) UNSIGNED NULL DEFAULT NULL;

ALTER TABLE  `recording_links` CHANGE  `apiuser`  `apiuser` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;

ALTER TABLE  `recording_links` CHANGE  `apipassword`  `apipassword` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;

ALTER TABLE  `recording_links` CHANGE  `pexiplocation`  `pexiplocation` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;

ALTER TABLE  `recording_links` CHANGE  `livestreamgroupid`  `livestreamgroupid` INT( 10 ) UNSIGNED NULL DEFAULT NULL;
