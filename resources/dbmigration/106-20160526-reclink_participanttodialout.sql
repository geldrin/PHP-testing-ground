ALTER TABLE  `recording_links` ADD  `participanttodialout` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL AFTER  `conferenceid`;

ALTER TABLE  `recording_links` ADD  `participanttodialoutprotocol` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL AFTER  `participanttodialout` ,
ADD  `participanttodialoutdisplayname` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL AFTER  `participanttodialoutprotocol`;
