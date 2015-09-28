ALTER TABLE  `livestream_profiles` ADD  `contentkeycode` TEXT NULL AFTER  `streamsuffix` ,
ADD  `contentstreamsuffix` TEXT NOT NULL AFTER  `contentkeycode`;

ALTER TABLE  `livestream_profiles` DROP  `type`;

ALTER TABLE  `livestream_profiles_groups` CHANGE  `livestreamprofilegroupid`  `livestreamgroupid` INT( 10 ) UNSIGNED NOT NULL;
