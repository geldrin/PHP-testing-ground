ALTER TABLE  `recording_links` ADD  `autodialparticipant` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL AFTER  `conferenceid`;

ALTER TABLE  `recording_links` ADD  `autodialparticipantprotocol` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL AFTER  `autodialparticipant` ,
ADD  `autodialparticipantdisplayname` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL AFTER  `autodialparticipantprotocol`;

ALTER TABLE  `livefeeds` ADD  `autodialparticipantid` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL AFTER  `vcrparticipantid`;