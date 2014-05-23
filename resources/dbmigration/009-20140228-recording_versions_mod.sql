CREATE TABLE IF NOT EXISTS `converter_nodes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `server` text NOT NULL,
  `serverip` text NOT NULL,
  `shortname` text NOT NULL,
  `default` int(10) unsigned NOT NULL DEFAULT '0',
  `disabled` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

ALTER TABLE `recordings_versions` CHANGE `qualitytag` `qualitytag` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT 'short name';
ALTER TABLE `recordings_versions` CHANGE `filename` `filename` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;
