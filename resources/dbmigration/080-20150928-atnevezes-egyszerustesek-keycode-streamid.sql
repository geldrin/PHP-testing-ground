ALTER TABLE  `livestream_profiles` ADD  `contentstreamid` TEXT NULL AFTER  `streamsuffix` ,
ADD  `contentstreamsuffix` TEXT NOT NULL AFTER  `contentstreamid`;

ALTER TABLE  `livestream_profiles` DROP  `type`;

ALTER TABLE  `livestream_profiles_groups` CHANGE  `livestreamprofilegroupid`  `livestreamgroupid` INT( 10 ) UNSIGNED NOT NULL;
