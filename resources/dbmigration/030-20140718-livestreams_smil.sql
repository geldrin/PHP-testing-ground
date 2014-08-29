ALTER TABLE  `livefeeds` ADD  `smilstatus` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL AFTER  `needrecording` ,
ADD  `contentsmilstatus` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL AFTER  `smilstatus`;

