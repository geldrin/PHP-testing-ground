CREATE TABLE IF NOT EXISTS `statistics_live_5min` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `timestamp` datetime NOT NULL,
  `livefeedid` int(11) NOT NULL,
  `livefeedstreamid` int(11) NOT NULL,
  `iscontent` int(10) unsigned NOT NULL DEFAULT '0',
  `country` text NOT NULL,
  `numberofflashwin` int(10) unsigned NOT NULL DEFAULT '0',
  `numberofflashmac` int(10) unsigned NOT NULL DEFAULT '0',
  `numberofflashlinux` int(10) unsigned NOT NULL DEFAULT '0',
  `numberofandroid` int(10) unsigned NOT NULL DEFAULT '0',
  `numberofiphone` int(10) unsigned NOT NULL DEFAULT '0',
  `numberofipad` int(10) unsigned NOT NULL DEFAULT '0',
  `numberofunknown` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `statistics_live_hourly` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `timestamp` datetime NOT NULL,
  `livefeedid` int(11) NOT NULL,
  `livefeedstreamid` int(11) NOT NULL,
  `iscontent` int(10) unsigned NOT NULL DEFAULT '0',
  `country` text NOT NULL,
  `numberofflashwin` int(10) unsigned NOT NULL DEFAULT '0',
  `numberofflashmac` int(10) unsigned NOT NULL DEFAULT '0',
  `numberofflashlinux` int(10) unsigned NOT NULL DEFAULT '0',
  `numberofandroid` int(10) unsigned NOT NULL DEFAULT '0',
  `numberofiphone` int(10) unsigned NOT NULL DEFAULT '0',
  `numberofipad` int(10) unsigned NOT NULL DEFAULT '0',
  `numberofunknown` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `statistics_live_daily` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `timestamp` datetime NOT NULL,
  `livefeedid` int(11) NOT NULL,
  `livefeedstreamid` int(11) NOT NULL,
  `iscontent` int(10) unsigned NOT NULL DEFAULT '0',
  `country` text NOT NULL,
  `numberofflashwin` int(10) unsigned NOT NULL DEFAULT '0',
  `numberofflashmac` int(10) unsigned NOT NULL DEFAULT '0',
  `numberofflashlinux` int(10) unsigned NOT NULL DEFAULT '0',
  `numberofandroid` int(10) unsigned NOT NULL DEFAULT '0',
  `numberofiphone` int(10) unsigned NOT NULL DEFAULT '0',
  `numberofipad` int(10) unsigned NOT NULL DEFAULT '0',
  `numberofunknown` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
