ALTER TABLE users ADD firstloggedin DATETIME NULL DEFAULT NULL AFTER timestampdisabledafter;
ALTER TABLE recordings ADD smilstatus TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL AFTER ocrstatus;
ALTER TABLE users ADD isusergenerated INT UNSIGNED NULL DEFAULT '0' AFTER validationcode;

CREATE TABLE `recordings_versions` (
  `id` int(10) unsigned not null auto_increment,
  `timestamp` datetime not null,
  `converternodeid` int(10) unsigned not null,
  `recordingid` int(10) unsigned not null,
  `encodingprofileid` int(10) unsigned not null,
  `encodingorder` int(10) unsigned not null default '100',
  `qualitytag` text,
  `filename` text,
  `iscontent` int(10) unsigned not null default '0',
  `status` text not null,
  `resolution` text,
  `bandwidth` int(10) unsigned default null,
  `isdesktopcompatible` int(10) unsigned default null,
  `ismobilecompatible` int(10) unsigned default null,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;
