CREATE TABLE  `recording_view_sessions` (
`id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`recordingid` INT UNSIGNED NOT NULL ,
`userid` INT UNSIGNED NOT NULL ,
`sessionid` TEXT NOT NULL ,
`timestampfrom` TIMESTAMP NULL ,
`timestampuntil` TIMESTAMP NULL ,
UNIQUE  `uq-user-recording-session` (  `userid` ,  `recordingid` ,  `sessionid` ( 50 ) )
) ENGINE = INNODB;

ALTER TABLE  `recording_view_sessions` ADD  `positionfrom` INT NULL ,
ADD  `positionuntil` INT NULL;
