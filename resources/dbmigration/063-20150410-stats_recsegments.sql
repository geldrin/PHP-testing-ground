CREATE TABLE IF NOT EXISTS `statistics_recordings_segments_users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `recordingid` int(10) unsigned NOT NULL,
  `userid` int(10) unsigned DEFAULT NULL,
  `recordingsegment` int(10) unsigned NOT NULL,
  `viewcounter` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_recording` (`recordingid`),
  KEY `ix_user` (`userid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
