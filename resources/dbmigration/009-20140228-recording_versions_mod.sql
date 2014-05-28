CREATE TABLE IF NOT EXISTS `converter_nodes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `server` text NOT NULL,
  `serverip` text NOT NULL,
  `shortname` text NOT NULL,
  `default` int(10) unsigned NOT NULL DEFAULT '0',
  `disabled` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;
