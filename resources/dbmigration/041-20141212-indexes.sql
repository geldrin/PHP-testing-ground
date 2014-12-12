ALTER TABLE `recordings` ADD INDEX  `ix_status` (  `status` ( 10 ) ,  `masterstatus` ( 10 ) ,  `contentstatus` ( 10 ) ,  `contentmasterstatus` ( 10 ) );
ALTER TABLE  `cdn_streaming_stats` ADD INDEX  `ix_time` (  `starttime` ,  `endtime` );
ALTER TABLE  `cdn_streaming_stats` ADD INDEX  `ix_stream` (  `wowzaappid` ( 10 ) ,  `wowzalocalstreamname` ( 10 ) );
ALTER TABLE  `view_statistics_live` ADD INDEX  `ix_time` (  `timestampfrom` ,  `timestampuntil` );
ALTER TABLE  `view_statistics_ondemand` ADD INDEX  `ix_time` (  `timestamp` );
ALTER TABLE  `view_statistics_ondemand` ADD INDEX  `ix_position` (  `positionfrom` ,  `positionuntil` );
