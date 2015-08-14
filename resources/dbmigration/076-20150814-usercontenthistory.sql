CREATE TABLE `usercontenthistory` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userid` int(10) unsigned NOT NULL,
  `recordingid` int(10) unsigned,
  `livefeedid` int(10) unsigned,
  `channelid` int(10) unsigned,
  `categoryid` int(10) unsigned,
  `genreid` int(10) unsigned,
  `timestamp` timestamp,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
