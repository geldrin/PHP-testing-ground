ALTER TABLE  `comments` ADD  `anonymoususerid` INT UNSIGNED NULL AFTER  `userid`;
ALTER TABLE  `comments` CHANGE  `userid`  `userid` INT( 10 ) UNSIGNED NULL;

CREATE TABLE  `anonymous_users` (
`id` INT UNSIGNED NOT NULL ,
`token` VARCHAR( 50 ) NOT NULL ,
`timestamp` TIMESTAMP NOT NULL ,
PRIMARY KEY (  `id` ) ,
INDEX (  `token` )
) ENGINE = INNODB;
