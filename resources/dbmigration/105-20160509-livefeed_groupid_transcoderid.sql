ALTER TABLE  `livefeeds` ADD  `livestreamgroupid` INT( 10 ) NULL DEFAULT NULL AFTER  `livefeedrecordingid` ,
ADD  `transcoderid` INT( 10 ) NULL DEFAULT NULL AFTER  `livestreamgroupid`;
