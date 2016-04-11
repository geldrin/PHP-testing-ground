ALTER TABLE  `livefeeds` ADD  `recordinglinkid` INT( 10 ) UNSIGNED NULL DEFAULT NULL AFTER  `needrecording` ,
ADD  `vcrconferenceid` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL AFTER  `recordinglinkid` ,
ADD  `vcrparticipantid` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL AFTER  `vcrconferenceid`;
