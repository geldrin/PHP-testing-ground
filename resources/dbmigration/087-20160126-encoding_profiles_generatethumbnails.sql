ALTER TABLE  `encoding_profiles` ADD  `generatethumbnails` INT( 10 ) UNSIGNED NOT NULL DEFAULT  '0' AFTER  `mediatype`;
UPDATE `encoding_profiles` SET `generatethumbnails` = 1 WHERE `shortname` LIKE "%360p%" AND type = 'recording';
