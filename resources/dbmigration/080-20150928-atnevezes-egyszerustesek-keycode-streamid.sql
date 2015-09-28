ALTER TABLE  `livefeed_streams` CHANGE  `keycode`  `streamid` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ,
CHANGE  `contentkeycode`  `contentstreamid` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;

ALTER TABLE  `livestream_profiles` ADD  `contentstreamid` TEXT NULL AFTER  `streamsuffix` ,
ADD  `contentstreamsuffix` TEXT NOT NULL AFTER  `contentstreamid`;

ALTER TABLE  `livestream_profiles` DROP  `type`;

ALTER TABLE  `livestream_profiles_groups` CHANGE  `livestreamprofilegroupid`  `livestreamgroupid` INT( 10 ) UNSIGNED NOT NULL;
