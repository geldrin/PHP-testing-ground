CREATE TABLE `view_statistics_live` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userid` int(10) unsigned DEFAULT NULL,
  `livefeedid` int(10) unsigned NOT NULL,
  `sessionid` text NOT NULL,
  `viewsessionid` text NOT NULL,
  `action` text NOT NULL,
  `streamscheme` text,
  `streamserver` text,
  `streamurl` text,
  `ipaddress` text NOT NULL,
  `useragent` text,
  `timestampfrom` datetime,
  `timestampuntil` datetime,
  PRIMARY KEY (`id`),
  INDEX  `ix-viewsession` (  `viewsessionid` ( 50 ) )
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE `view_statistics_ondemand` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userid` int(10) unsigned DEFAULT NULL,
  `recordingid` int(10) unsigned NOT NULL,
  `recordingversionid` int(10) unsigned NOT NULL,
  `sessionid` text NOT NULL,
  `viewsessionid` text NOT NULL,
  `action` text NOT NULL,
  `streamscheme` text,
  `streamserver` text,
  `streamurl` text,
  `ipaddress` text NOT NULL,
  `useragent` text,
  `positionfrom` int,
  `positionuntil` int,
  `timestamp` datetime NOT NULL,
  PRIMARY KEY (`id`),
  INDEX  `ix-viewsession` (  `viewsessionid` ( 50 ) )
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
