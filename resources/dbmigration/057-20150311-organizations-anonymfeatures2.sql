ALTER TABLE  `comments` ADD  `anonymoususerid` INT UNSIGNED NULL AFTER  `userid`;
ALTER TABLE  `comments` CHANGE  `userid`  `userid` INT( 10 ) UNSIGNED NULL;

CREATE TABLE  `anonymous_users` (
`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
`token` VARCHAR( 32 ) CHARACTER SET ASCII COLLATE ascii_general_ci NOT NULL,
`timestamp` TIMESTAMP NULL,
PRIMARY KEY (  `id` ) ,
UNIQUE ( `token` )
) ENGINE = INNODB;
