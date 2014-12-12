ALTER TABLE  `users` ADD  `source` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL AFTER  `organizationid`;

ALTER TABLE  `groups` ADD  `source` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL AFTER  `organizationid` ,
ADD  `directoryid` INT( 10 ) NULL DEFAULT NULL AFTER  `source` ,
ADD  `directorygroupobjectname` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL AFTER  `directoryid` ,
ADD  `directorygroupwhenchanged` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL AFTER  `directorygroupobjectname`;

CREATE TABLE IF NOT EXISTS `organization_authtypes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `type` text NOT NULL,
  `domains` text NOT NULL,
  `name` text,
  `description` text,
  `disabled` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `organization_directories` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `type` text NOT NULL,
  `server` text NOT NULL,
  `user` text NOT NULL,
  `password` int(11) NOT NULL,
  `domains` text NOT NULL,
  `name` text NOT NULL,
  `ldapgroupaccess` text NOT NULL,
  `ldapgroupadmin` text NOT NULL,
  `description` text NOT NULL,
  `disabled` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;




