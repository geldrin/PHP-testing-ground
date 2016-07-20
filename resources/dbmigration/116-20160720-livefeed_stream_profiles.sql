TRUNCATE TABLE `livestream_groups`;

TRUNCATE TABLE `livestream_profiles`;

TRUNCATE TABLE `livestream_profiles_groups`;

INSERT INTO `livestream_groups` (`id`, `timestamp`, `name`, `description`, `default`, `slideonright`, `accesstype`, `anonymousallowed`, `moderationtype`, `feedtype`, `isadaptive`, `istranscoderencoded`, `transcoderid`, `disabled`) VALUES
(1, '2015-09-23 00:00:00', 'Videosquare Classic SD+HD', 'Videosquare classic SD + HD streams, non-transcoded.', 1, 0, NULL, 0, NULL, 'live', 0, 0, NULL, 0),
(2, '2015-11-26 00:00:00', 'Adaptive transcoded 180p/270p/360p/480p/720p', 'Videosquare transcoded to 180p, 270p, 360p, 480p, 720p. Primarily for Epiphan recorders.', 0, 0, NULL, 0, NULL, 'live', 1, 1, 2, 0),
(3, '2016-04-14 00:00:00', 'VCR legacy', 'Videosquare old VCR (TCS) service recording/streaming live profile', 0, 0, NULL, 0, NULL, 'vcr', 0, 0, NULL, 0),
(4, '2016-05-11 00:00:00', 'Haivision Kulabyte ABR 720p', 'Adaptive bitrate non-transcoded with quality versions: 180p, 270p, 360p, 480p, 720p. No picture-in-picture.', 0, 0, NULL, 0, NULL, 'live', 1, 0, NULL, 0);

INSERT INTO `livestream_profiles` (`id`, `timestamp`, `qualitytag`, `type`, `streamidlength`, `streamid`, `streamsuffix`, `iscontentenabled`, `contentstreamidlength`, `contentstreamid`, `contentstreamsuffix`, `isdesktopcompatible`, `isandroidcompatible`, `isioscompatible`, `disabled`) VALUES
(1, '2015-09-23 00:00:00', 'SD', 'dynamic', 6, NULL, '', 1, 6, NULL, '', 1, 1, 1, 0),
(2, '2015-09-23 00:00:00', 'HD', 'dynamic', 6, NULL, '', 1, 6, NULL, '', 1, 0, 0, 0),
(3, '2015-11-26 00:00:00', '180p', 'groupdynamic', 6, NULL, '_180p', 1, 6, NULL, '_180p', 1, 0, 0, 1),
(4, '2015-11-26 00:00:00', '270p', 'groupdynamic', 6, NULL, '_270p', 1, 6, NULL, '_270p', 1, 0, 0, 0),
(5, '2015-11-26 00:00:00', '360p', 'groupdynamic', 6, NULL, '_360p', 1, 6, NULL, '_360p', 1, 0, 0, 0),
(6, '2015-11-26 00:00:00', '480p', 'groupdynamic', 6, NULL, '_480p', 1, 6, NULL, '_480p', 1, 0, 0, 0),
(7, '2015-11-26 00:00:00', '720p', 'groupdynamic', 6, NULL, '_720p', 1, 6, NULL, '_720p', 1, 0, 0, 0),
(8, '2016-04-14 00:00:00', 'VCR', 'dynamic', 6, NULL, '', 0, NULL, NULL, '', 1, 0, 0, 0),
(9, '2016-05-11 00:00:00', '180p', 'groupdynamic', 6, NULL, '_180p', 1, 6, NULL, '_180p', 1, 1, 1, 0),
(10, '2016-05-11 00:00:00', '270p', 'groupdynamic', 6, NULL, '_270p', 1, 6, NULL, '_270p', 1, 1, 1, 0),
(11, '2016-05-11 00:00:00', '360p', 'groupdynamic', 6, NULL, '_360p', 1, 6, NULL, '_360p', 1, 1, 1, 0),
(12, '2016-05-11 00:00:00', '480p', 'groupdynamic', 6, NULL, '_480p', 1, 6, NULL, '_480p', 1, 0, 0, 0),
(13, '2016-05-11 00:00:00', '720p', 'groupdynamic', 6, NULL, '_720p', 1, 6, NULL, '_720p', 1, 0, 0, 0),
(14, '2016-06-30 00:00:00', 'Mobile 270p', 'groupdynamic', 6, NULL, '_pip_270p', 0, NULL, NULL, '_pip_270p', 0, 1, 1, 0),
(15, '2016-06-30 00:00:00', 'Mobile 360p', 'groupdynamic', 6, NULL, '_pip_360p', 0, NULL, NULL, '_pip_360p', 0, 1, 1, 0),
(16, '2016-06-30 00:00:00', 'Mobile 480p', 'groupdynamic', 6, NULL, '_pip_480p', 0, NULL, NULL, '_pip_480p', 0, 1, 1, 0),
(17, '2016-06-30 00:00:00', 'Mobile 180p', 'groupdynamic', 6, NULL, '_pip_180p', 0, NULL, NULL, '_pip_180p', 0, 1, 1, 0);

INSERT INTO `livestream_profiles_groups` (`id`, `livestreamgroupid`, `livestreamprofileid`, `weight`) VALUES
(1, 1, 1, 10),
(2, 1, 2, 20),
(3, 2, 3, 10),
(4, 2, 4, 20),
(5, 2, 5, 30),
(6, 2, 6, 40),
(7, 2, 7, 50),
(8, 3, 8, 100),
(9, 4, 9, 10),
(10, 4, 10, 20),
(11, 4, 11, 30),
(12, 4, 12, 40),
(13, 4, 13, 50),
(14, 2, 17, 110),
(15, 2, 14, 120),
(16, 2, 15, 130),
(17, 2, 16, 140);
