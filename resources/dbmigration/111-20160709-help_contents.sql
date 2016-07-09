ALTER TABLE `help_contents`
ADD `organizationid` int(10) unsigned NOT NULL DEFAULT '0' AFTER `id`;

ALTER TABLE `help_contents`
ADD UNIQUE `uq_organizationid_shortname` (`organizationid`, `shortname`(80)),
DROP INDEX `uix_shortname`;
