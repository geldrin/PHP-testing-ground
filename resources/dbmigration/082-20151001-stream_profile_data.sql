ALTER TABLE  `livestream_groups` ADD  `default` INT( 10 ) UNSIGNED NOT NULL DEFAULT  '0' AFTER  `description`;

INSERT INTO `livestream_groups` (`id`, `timestamp`, `name`, `description`, `default`, `slideonright`, `accesstype`, `anonymousallowed`, `moderationtype`, `feedtype`, `istranscoderencoded`, `transcoderid`, `disabled`) VALUES
(1, '2015-09-23 00:00:00', 'Videosquare SD + HD', 'SD + HD streams', 1, 0, NULL, 0, NULL, 'live', 0, NULL, 0);

INSERT INTO `livestream_profiles` (`id`, `timestamp`, `qualitytag`, `type`, `streamidlength`, `streamid`, `streamsuffix`, `iscontentenabled`, `contentstreamidlength`, `contentstreamid`, `contentstreamsuffix`, `isdesktopcompatible`, `isandroidcompatible`, `isioscompatible`, `disabled`) VALUES
(1, '2015-09-23 00:00:00', 'SD', 'dynamic', 6, NULL, '', 1, 6, NULL, '', 1, 1, 1, 0),
(2, '2015-09-23 00:00:00', 'HD', 'dynamic', 6, NULL, '', 1, 6, NULL, '', 1, 0, 0, 0);

INSERT INTO `livestream_profiles_groups` (`id`, `livestreamgroupid`, `livestreamprofileid`, `weight`) VALUES
(1, 1, 1, 10),
(2, 1, 2, 20);
