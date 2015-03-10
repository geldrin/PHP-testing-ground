ALTER TABLE  `groups` ADD  `organizationdirectoryuserslastsynchronized` DATETIME NULL DEFAULT NULL AFTER  `organizationdirectoryldapwhenchanged`,
    ADD  `ispermanent` INT( 10 ) NOT NULL DEFAULT  '0' AFTER  `organizationdirectoryuserslastsynchronized`;
    
ALTER TABLE  `groups_members` ADD  `userexternalid` VARCHAR( 255 ) NULL DEFAULT NULL AFTER  `userid`;

ALTER TABLE  `organizations_directories` ADD  `ldapgroupaccessid` INT( 10 ) UNSIGNED NULL DEFAULT NULL AFTER  `ldapusertreedn`;
ALTER TABLE  `organizations_directories` ADD  `ldapgroupadminid` INT( 10 ) UNSIGNED NULL DEFAULT NULL AFTER  `ldapgroupaccess`;

ALTER TABLE  `groups_members` CHANGE  `userid`  `userid` INT( 10 ) UNSIGNED NULL DEFAULT NULL;

ALTER TABLE  `groups_members` ADD INDEX  `ix_userexternalid` (  `userexternalid` );