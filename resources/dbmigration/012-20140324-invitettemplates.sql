ALTER TABLE  `users_invitations` ADD  `templateid` INT UNSIGNED NULL AFTER  `organizationid`;

CREATE TABLE  `invite_templates` (
`id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`organizationid` INT UNSIGNED NOT NULL ,
`prefix` TEXT NOT NULL ,
`postfix` TEXT NOT NULL ,
`timestamp` DATETIME NOT NULL
) ENGINE = INNODB;
