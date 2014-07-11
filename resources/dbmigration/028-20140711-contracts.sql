CREATE TABLE IF NOT EXISTS `organizations_contracts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `organizationid` int(10) unsigned NOT NULL,
  `identifier` text,
  `title` text,
  `description` text,
  `startdate` date DEFAULT NULL,
  `enddate` date DEFAULT NULL,
  `currency` text,
  `disabled` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

