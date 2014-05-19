ALTER TABLE users DROP COLUMN departmentid;
ALTER TABLE livefeeds ADD indexphotofilename TEXT NULL DEFAULT NULL AFTER introrecordingid;
