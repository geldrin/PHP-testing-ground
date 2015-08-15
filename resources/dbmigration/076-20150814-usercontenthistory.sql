CREATE TABLE `usercontenthistory` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userid` int(10) unsigned NOT NULL,
  `recordingid` int(10) unsigned,
  `livefeedid` int(10) unsigned,
  `timestamp` timestamp,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE `usercontenthistory_channels` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `contenthistoryid` int(10) unsigned NOT NULL,
  `channelid` int(10) unsigned NOT NULL,
  `timestamp` timestamp,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE `usercontenthistory_categories` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `contenthistoryid` int(10) unsigned NOT NULL,
  `categoryid` int(10) unsigned NOT NULL,
  `timestamp` timestamp,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE `usercontenthistory_genres` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `contenthistoryid` int(10) unsigned NOT NULL,
  `genreid` int(10) unsigned NOT NULL,
  `timestamp` timestamp,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
