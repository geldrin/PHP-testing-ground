ALTER TABLE  `livefeed_streams` ADD  `indexphotofilename` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL AFTER  `status`;

CREATE TABLE IF NOT EXISTS `encoding_groups` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `timestamp` datetime NOT NULL,
  `name` text NOT NULL,
  `default` int(10) unsigned NOT NULL DEFAULT '0',
  `islegacy` int(10) unsigned NOT NULL DEFAULT '0',
  `description` text,
  `disabled` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `encoding_profiles_groups` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `encodingprofilegroupid` int(10) unsigned NOT NULL,
  `encodingprofileid` int(10) unsigned NOT NULL,
  `encodingorder` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `encoding_profiles` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `parentid` int(10) unsigned DEFAULT NULL,
  `name` text NOT NULL,
  `shortname` text NOT NULL,
  `type` text NOT NULL,
  `mediatype` text NOT NULL,
  `isdesktopcompatible` int(10) unsigned NOT NULL,
  `isioscompatible` int(10) unsigned NOT NULL,
  `isandroidcompatible` int(10) unsigned NOT NULL,
  `filenamesuffix` text NOT NULL,
  `filecontainerformat` text NOT NULL,
  `videocodec` text,
  `videopasses` int(10) unsigned DEFAULT NULL,
  `videobboxsizex` int(10) unsigned DEFAULT NULL,
  `videobboxsizey` int(10) unsigned DEFAULT NULL,
  `videomaxfps` int(10) unsigned DEFAULT NULL,
  `videobpp` double unsigned DEFAULT NULL,
  `ffmpegh264profile` text,
  `ffmpegh264preset` text,
  `audiocodec` text NOT NULL,
  `audiomaxchannels` int(10) unsigned NOT NULL,
  `audiobitrateperchannel` int(10) unsigned NOT NULL,
  `audiomode` text,
  `pipenabled` int(10) unsigned NOT NULL,
  `pipcodecprofile` text,
  `pipposx` text,
  `pipposy` text,
  `pipalign` double unsigned DEFAULT NULL,
  `pipsize` double unsigned DEFAULT NULL,
  `disabled` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

ALTER TABLE  `organizations` ADD  `defaultencodingprofilegroupid` INT( 10 ) UNSIGNED NULL DEFAULT  '1' AFTER  `iselearningcoursesessionbound`;

UPDATE organizations SET defaultencodingprofilegroupid = 1 WHERE issubscriber = 1;

ALTER TABLE  `recordings` ADD  `encodinggroupid` INT UNSIGNED NULL DEFAULT NULL AFTER  `notifyaboutcomments`;
