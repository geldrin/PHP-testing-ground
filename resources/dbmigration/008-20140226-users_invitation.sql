ALTER TABLE  `users_invitations` ADD  `recordingid` INT UNSIGNED NULL AFTER  `groups` ,
ADD  `livefeedid` INT UNSIGNED NULL AFTER  `recordingid` ,
ADD  `channelid` INT UNSIGNED NULL AFTER  `livefeedid` ,
ADD  `registereduserid` INT UNSIGNED NULL AFTER  `channelid` ,
ADD  `status` TEXT NULL AFTER  `registereduserid`;

ALTER TABLE  `users_invitations` ADD INDEX  `ix_registereduser` (  `registereduserid` );
UPDATE users_invitations SET status = 'invited';
