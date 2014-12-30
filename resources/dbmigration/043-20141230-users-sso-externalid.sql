ALTER TABLE  `users` ADD  `externalid` VARCHAR( 255 ) NULL AFTER  `id` ,
ADD INDEX `ix-externalid` ( externalid );
