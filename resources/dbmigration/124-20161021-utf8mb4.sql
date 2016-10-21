ALTER TABLE `access`
COLLATE 'utf8mb4_unicode_ci';

ALTER TABLE `anonymous_users`
COLLATE 'utf8mb4_unicode_ci';

ALTER TABLE `attached_documents` CONVERT TO CHARSET utf8mb4 COLLATE 'utf8mb4_unicode_ci';
ALTER TABLE `categories` CONVERT TO CHARSET utf8mb4 COLLATE 'utf8mb4_unicode_ci';
ALTER TABLE `cdn_client_networks` CONVERT TO CHARSET utf8mb4 COLLATE 'utf8mb4_unicode_ci';
ALTER TABLE `cdn_servers_networks` CONVERT TO CHARSET utf8mb4 COLLATE 'utf8mb4_unicode_ci';
ALTER TABLE `cdn_streaming_servers` CONVERT TO CHARSET utf8mb4 COLLATE 'utf8mb4_unicode_ci';
ALTER TABLE `cdn_streaming_stats` CONVERT TO CHARSET utf8mb4 COLLATE 'utf8mb4_unicode_ci';
ALTER TABLE `channels` CONVERT TO CHARSET utf8mb4 COLLATE 'utf8mb4_unicode_ci';
ALTER TABLE `channels_recordings` CONVERT TO CHARSET utf8mb4 COLLATE 'utf8mb4_unicode_ci';
ALTER TABLE `channel_types` CONVERT TO CHARSET utf8mb4 COLLATE 'utf8mb4_unicode_ci';
ALTER TABLE `comments` CONVERT TO CHARSET utf8mb4 COLLATE 'utf8mb4_unicode_ci';
ALTER TABLE `contents` CONVERT TO CHARSET utf8mb4 COLLATE 'utf8mb4_unicode_ci';
ALTER TABLE `contributors` CONVERT TO CHARSET utf8mb4 COLLATE 'utf8mb4_unicode_ci';
ALTER TABLE `contributors_jobs` CONVERT TO CHARSET utf8mb4 COLLATE 'utf8mb4_unicode_ci';
ALTER TABLE `contributors_roles` CONVERT TO CHARSET utf8mb4 COLLATE 'utf8mb4_unicode_ci';
ALTER TABLE `contributor_images` CONVERT TO CHARSET utf8mb4 COLLATE 'utf8mb4_unicode_ci';
ALTER TABLE `departments` CONVERT TO CHARSET utf8mb4 COLLATE 'utf8mb4_unicode_ci';
ALTER TABLE `encoding_groups` CONVERT TO CHARSET utf8mb4 COLLATE 'utf8mb4_unicode_ci';
ALTER TABLE `encoding_profiles` CONVERT TO CHARSET utf8mb4 COLLATE 'utf8mb4_unicode_ci';
ALTER TABLE `encoding_profiles_groups` CONVERT TO CHARSET utf8mb4 COLLATE 'utf8mb4_unicode_ci';
ALTER TABLE `genres` CONVERT TO CHARSET utf8mb4 COLLATE 'utf8mb4_unicode_ci';
ALTER TABLE `groups` CONVERT TO CHARSET utf8mb4 COLLATE 'utf8mb4_unicode_ci';

-- index miatt nem tudjuk modositani a collationt (4byte-al szamol, kifutunk az
-- index meretbol), de mivel nincs mas oszlop aminel szamitana igy csak
-- atbillentjuk a tabla default collationt es modositjuk az egyetlen text
-- oszlopot ascii-ra
ALTER TABLE `groups_members`
COLLATE 'utf8mb4_unicode_ci';
ALTER TABLE `groups_members`
CHANGE `userexternalid` `userexternalid` varchar(255) COLLATE 'ascii_general_ci' NULL AFTER `userid`;

ALTER TABLE `help_contents` CONVERT TO CHARSET utf8mb4 COLLATE 'utf8mb4_unicode_ci';
ALTER TABLE `infrastructure_nodes` CONVERT TO CHARSET utf8mb4 COLLATE 'utf8mb4_unicode_ci';
ALTER TABLE `invite_templates` CONVERT TO CHARSET utf8mb4 COLLATE 'utf8mb4_unicode_ci';
ALTER TABLE `languages`
COLLATE 'utf8mb4_unicode_ci';

ALTER TABLE `languages` CONVERT TO CHARSET utf8mb4 COLLATE 'utf8mb4_unicode_ci';
ALTER TABLE `languages`
CHANGE `shortname` `shortname` char(3) COLLATE 'ascii_general_ci' NOT NULL AFTER `id`;

ALTER TABLE `livefeeds` CONVERT TO CHARSET utf8mb4 COLLATE 'utf8mb4_unicode_ci';
ALTER TABLE `livefeed_chat` CONVERT TO CHARSET utf8mb4 COLLATE 'utf8mb4_unicode_ci';
ALTER TABLE `livefeed_recordings` CONVERT TO CHARSET utf8mb4 COLLATE 'utf8mb4_unicode_ci';
ALTER TABLE `livefeed_streams` CONVERT TO CHARSET utf8mb4 COLLATE 'utf8mb4_unicode_ci';
ALTER TABLE `livefeed_teacherinvites` CONVERT TO CHARSET utf8mb4 COLLATE 'utf8mb4_unicode_ci';
ALTER TABLE `livestream_groups` CONVERT TO CHARSET utf8mb4 COLLATE 'utf8mb4_unicode_ci';
ALTER TABLE `livestream_profiles` CONVERT TO CHARSET utf8mb4 COLLATE 'utf8mb4_unicode_ci';
ALTER TABLE `livestream_profiles_groups` CONVERT TO CHARSET utf8mb4 COLLATE 'utf8mb4_unicode_ci';
ALTER TABLE `livestream_transcoders` CONVERT TO CHARSET utf8mb4 COLLATE 'utf8mb4_unicode_ci';
ALTER TABLE `mailqueue` CONVERT TO CHARSET utf8mb4 COLLATE 'utf8mb4_unicode_ci';
ALTER TABLE `ocr_frames` CONVERT TO CHARSET utf8mb4 COLLATE 'utf8mb4_unicode_ci';
ALTER TABLE `organizations` CONVERT TO CHARSET utf8mb4 COLLATE 'utf8mb4_unicode_ci';
ALTER TABLE `organizations_authtypes` CONVERT TO CHARSET utf8mb4 COLLATE 'utf8mb4_unicode_ci';
ALTER TABLE `organizations_contracts` CONVERT TO CHARSET utf8mb4 COLLATE 'utf8mb4_unicode_ci';
ALTER TABLE `organizations_directories` CONVERT TO CHARSET utf8mb4 COLLATE 'utf8mb4_unicode_ci';
ALTER TABLE `organizations_news` CONVERT TO CHARSET utf8mb4 COLLATE 'utf8mb4_unicode_ci';
ALTER TABLE `privileges` CONVERT TO CHARSET utf8mb4 COLLATE 'utf8mb4_unicode_ci';
ALTER TABLE `recordings` CONVERT TO CHARSET utf8mb4 COLLATE 'utf8mb4_unicode_ci';
ALTER TABLE `recordings_categories` CONVERT TO CHARSET utf8mb4 COLLATE 'utf8mb4_unicode_ci';
ALTER TABLE `recordings_genres` CONVERT TO CHARSET utf8mb4 COLLATE 'utf8mb4_unicode_ci';
ALTER TABLE `recordings_versions` CONVERT TO CHARSET utf8mb4 COLLATE 'utf8mb4_unicode_ci';
ALTER TABLE `recording_links` CONVERT TO CHARSET utf8mb4 COLLATE 'utf8mb4_unicode_ci';
ALTER TABLE `recording_logs` CONVERT TO CHARSET utf8mb4 COLLATE 'utf8mb4_unicode_ci';
ALTER TABLE `recording_view_progress` CONVERT TO CHARSET utf8mb4 COLLATE 'utf8mb4_unicode_ci';
ALTER TABLE `recording_view_sessions` CONVERT TO CHARSET utf8mb4 COLLATE 'utf8mb4_unicode_ci';
ALTER TABLE `roles` CONVERT TO CHARSET utf8mb4 COLLATE 'utf8mb4_unicode_ci';
ALTER TABLE `springboardconfiguration` CONVERT TO CHARSET utf8mb4 COLLATE 'utf8mb4_unicode_ci';
ALTER TABLE `statistics_live_5min` CONVERT TO CHARSET utf8mb4 COLLATE 'utf8mb4_unicode_ci';
ALTER TABLE `statistics_live_daily` CONVERT TO CHARSET utf8mb4 COLLATE 'utf8mb4_unicode_ci';
ALTER TABLE `statistics_live_hourly` CONVERT TO CHARSET utf8mb4 COLLATE 'utf8mb4_unicode_ci';
ALTER TABLE `statistics_recordings_segments` CONVERT TO CHARSET utf8mb4 COLLATE 'utf8mb4_unicode_ci';
ALTER TABLE `strings` CONVERT TO CHARSET utf8mb4 COLLATE 'utf8mb4_unicode_ci';
ALTER TABLE `subscriptions` CONVERT TO CHARSET utf8mb4 COLLATE 'utf8mb4_unicode_ci';
ALTER TABLE `subtitles` CONVERT TO CHARSET utf8mb4 COLLATE 'utf8mb4_unicode_ci';
ALTER TABLE `uploads` CONVERT TO CHARSET utf8mb4 COLLATE 'utf8mb4_unicode_ci';
ALTER TABLE `usercontenthistory` CONVERT TO CHARSET utf8mb4 COLLATE 'utf8mb4_unicode_ci';
ALTER TABLE `usercontenthistory_categories` CONVERT TO CHARSET utf8mb4 COLLATE 'utf8mb4_unicode_ci';
ALTER TABLE `usercontenthistory_channels` CONVERT TO CHARSET utf8mb4 COLLATE 'utf8mb4_unicode_ci';
ALTER TABLE `usercontenthistory_contributors` CONVERT TO CHARSET utf8mb4 COLLATE 'utf8mb4_unicode_ci';
ALTER TABLE `usercontenthistory_genres` CONVERT TO CHARSET utf8mb4 COLLATE 'utf8mb4_unicode_ci';
ALTER TABLE `userroles` CONVERT TO CHARSET utf8mb4 COLLATE 'utf8mb4_unicode_ci';
ALTER TABLE `userroles_privileges` CONVERT TO CHARSET utf8mb4 COLLATE 'utf8mb4_unicode_ci';


-- index miatt nem tudjuk modositani a collationt (4byte-al szamol, kifutunk az
-- index meretbol), igy droppoljuk az indexet, modositjuk a collationt
-- az adott oszlop collationjet ascii-ra
-- vegul rekrealjuk az indexet
ALTER TABLE `users`
DROP INDEX `ix-externalid`;
ALTER TABLE `users`
DROP INDEX `ix_emailorganizationid`;

ALTER TABLE `users` CONVERT TO CHARSET utf8mb4 COLLATE 'utf8mb4_unicode_ci';

ALTER TABLE `users`
CHANGE `externalid` `externalid` varchar(255) COLLATE 'ascii_general_ci' NULL AFTER `id`;
ALTER TABLE `users`
CHANGE `email` `email` text COLLATE 'ascii_general_ci' NOT NULL AFTER `nickname`;

ALTER TABLE `users`
ADD INDEX `ix_externalid` (`externalid`);
ALTER TABLE `users`
ADD INDEX `ix_emailorganizationid` (`email`(250), `organizationid`);


ALTER TABLE `users_departments` CONVERT TO CHARSET utf8mb4 COLLATE 'utf8mb4_unicode_ci';
ALTER TABLE `users_invitations` CONVERT TO CHARSET utf8mb4 COLLATE 'utf8mb4_unicode_ci';
ALTER TABLE `view_statistics_live` CONVERT TO CHARSET utf8mb4 COLLATE 'utf8mb4_unicode_ci';
ALTER TABLE `view_statistics_ondemand` CONVERT TO CHARSET utf8mb4 COLLATE 'utf8mb4_unicode_ci';


REPAIR TABLE `access`;
REPAIR TABLE `anonymous_users`;
REPAIR TABLE `attached_documents`;
REPAIR TABLE `categories`;
REPAIR TABLE `cdn_client_networks`;
REPAIR TABLE `cdn_servers_networks`;
REPAIR TABLE `cdn_streaming_servers`;
REPAIR TABLE `cdn_streaming_stats`;
REPAIR TABLE `channels`;
REPAIR TABLE `channels_recordings`;
REPAIR TABLE `channel_types`;
REPAIR TABLE `comments`;
REPAIR TABLE `contents`;
REPAIR TABLE `contributors`;
REPAIR TABLE `contributors_jobs`;
REPAIR TABLE `contributors_roles`;
REPAIR TABLE `contributor_images`;
REPAIR TABLE `departments`;
REPAIR TABLE `encoding_groups`;
REPAIR TABLE `encoding_profiles`;
REPAIR TABLE `encoding_profiles_groups`;
REPAIR TABLE `genres`;
REPAIR TABLE `groups`;
REPAIR TABLE `groups_members`;
REPAIR TABLE `help_contents`;
REPAIR TABLE `infrastructure_nodes`;
REPAIR TABLE `invite_templates`;
REPAIR TABLE `languages`;
REPAIR TABLE `livefeeds`;
REPAIR TABLE `livefeed_chat`;
REPAIR TABLE `livefeed_recordings`;
REPAIR TABLE `livefeed_streams`;
REPAIR TABLE `livefeed_teacherinvites`;
REPAIR TABLE `livestream_groups`;
REPAIR TABLE `livestream_profiles`;
REPAIR TABLE `livestream_profiles_groups`;
REPAIR TABLE `livestream_transcoders`;
REPAIR TABLE `mailqueue`;
REPAIR TABLE `ocr_frames`;
REPAIR TABLE `organizations`;
REPAIR TABLE `organizations_authtypes`;
REPAIR TABLE `organizations_contracts`;
REPAIR TABLE `organizations_directories`;
REPAIR TABLE `organizations_news`;
REPAIR TABLE `privileges`;
REPAIR TABLE `recordings`;
REPAIR TABLE `recordings_categories`;
REPAIR TABLE `recordings_genres`;
REPAIR TABLE `recordings_versions`;
REPAIR TABLE `recording_links`;
REPAIR TABLE `recording_logs`;
REPAIR TABLE `recording_view_progress`;
REPAIR TABLE `recording_view_sessions`;
REPAIR TABLE `roles`;
REPAIR TABLE `springboardconfiguration`;
REPAIR TABLE `statistics_live_5min`;
REPAIR TABLE `statistics_live_daily`;
REPAIR TABLE `statistics_live_hourly`;
REPAIR TABLE `statistics_recordings_segments`;
REPAIR TABLE `strings`;
REPAIR TABLE `subscriptions`;
REPAIR TABLE `subtitles`;
REPAIR TABLE `uploads`;
REPAIR TABLE `usercontenthistory`;
REPAIR TABLE `usercontenthistory_categories`;
REPAIR TABLE `usercontenthistory_channels`;
REPAIR TABLE `usercontenthistory_contributors`;
REPAIR TABLE `usercontenthistory_genres`;
REPAIR TABLE `userroles`;
REPAIR TABLE `userroles_privileges`;
REPAIR TABLE `users`;
REPAIR TABLE `users_departments`;
REPAIR TABLE `users_invitations`;
REPAIR TABLE `view_statistics_live`;
REPAIR TABLE `view_statistics_ondemand`;
