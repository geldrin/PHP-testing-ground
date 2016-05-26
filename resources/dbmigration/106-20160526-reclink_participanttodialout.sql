ALTER TABLE  `recording_links` ADD  `participanttodialout` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL AFTER  `conferenceid` ,
ADD  `participanttodialoutid` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL AFTER  `participanttodialout`;

ALTER TABLE  `recording_links` ADD  `participanttodialoutprotocol` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL AFTER  `participanttodialoutid` ,
ADD  `participanttodialoutdisplayname` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL AFTER  `participanttodialoutprotocol`;
