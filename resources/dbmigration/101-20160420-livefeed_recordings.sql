CREATE TABLE `livefeed_recordings` (
 `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
 `livefeedid` int(10) unsigned DEFAULT NULL,
 `userid` int(10) unsigned NOT NULL,
 `livestreamtranscoderid` int(10) unsigned NOT NULL,
 `starttimestamp` datetime NOT NULL,
 `endtimestamp` datetime DEFAULT NULL,
 `vcrconferenceid` text,
 `status` text,
 PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

