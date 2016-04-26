ALTER TABLE statistics_recordings_segments_users DROP COLUMN userid;

RENAME TABLE `statistics_recordings_segments_users` TO `statistics_recordings_segments`;
