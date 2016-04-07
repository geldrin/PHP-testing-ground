ALTER TABLE  `recording_links` ADD  `type` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL AFTER  `organizationid`;

UPDATE recording_links SET TYPE = 'ciscotcs' WHERE id > 0;

ALTER TABLE  `recording_links`
ADD  `apiserver` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL AFTER  `aliassecure`,
ADD  `apiport` INT( 10 ) UNSIGNED NOT NULL AFTER  `apiserver` ,
ADD  `apiuser` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL AFTER  `apiport` ,
ADD  `apipassword` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL AFTER  `apiuser` ,
ADD  `apiishttpsenabled` INT( 10 ) UNSIGNED NOT NULL DEFAULT  '0' AFTER  `apipassword`;

ALTER TABLE  `recording_links`
ADD  `pexiplocation` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL AFTER  `apiishttpsenabled`,
ADD  `livestreamgroupid` INT( 10 ) UNSIGNED NOT NULL AFTER  `pexiplocation`;
