ALTER TABLE  `channel_types` ADD INDEX  `ix_parentid` (  `parentid` );
ALTER TABLE  `channel_types` ADD INDEX  `ix_organizationid` (  `organizationid` );

ALTER TABLE  `strings` ADD INDEX  `ix_translationof_language` (  `translationof` ,  `language` );

ALTER TABLE  `users_invitations` ADD INDEX  `ix_channelid` (  `channelid` );
ALTER TABLE  `users_invitations` ADD INDEX  `ix_recordingid` (  `recordingid` );
ALTER TABLE  `users_invitations` ADD INDEX  `ix_livefeedid` (  `livefeedid` );

ALTER TABLE  `uploads` ADD INDEX  `ix_userid_recordingid` (  `userid` ,  `recordingid` );

ALTER TABLE  `channels` ADD INDEX  `ix_deleted_organizationid_type` (  `isdeleted` ,  `organizationid`, `channeltypeid` );

ALTER TABLE  `channels_recordings` ADD INDEX  `ix_user_channel_record` (  `userid` ,  `channelid` ,  `recordingid` );

ALTER TABLE  `departments` ADD INDEX  `ix_organization_parent` (  `organizationid` ,  `parentid` );

ALTER TABLE  `groups` ADD INDEX  `ix_organization` (  `organizationid` );

ALTER TABLE  `groups_members` ADD INDEX  `ix_group` (  `groupid` );
ALTER TABLE  `groups_members` ADD INDEX  `ix_userid` (  `userid` );

ALTER TABLE  `recordings` ADD INDEX  `ix_organization` (  `organizationid` );

ALTER TABLE  `recordings_versions` ADD INDEX  `ix_recording_status` (  `recordingid` ,  `status` ( 20 ) );

ALTER TABLE  `roles` ADD INDEX  `ix_organization` (  `organizationid` );

