CREATE TABLE `livestream_groups` (
 `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
 `timestamp` datetime NOT NULL,
 `name` text NOT NULL,
 `description` text,
 `slideonright` int(11) NOT NULL DEFAULT '0',
 `accesstype` text,
 `anonymousallowed` int(11) NOT NULL DEFAULT '0',
 `moderationtype` text,
 `feedtype` text NOT NULL,
 `istranscoderencoded` int(11) NOT NULL DEFAULT '0',
 `transcoderid` int(10) unsigned DEFAULT NULL,
 `disabled` int(10) NOT NULL DEFAULT '0',
 PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `livestream_profiles` (
 `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
 `timestamp` datetime NOT NULL,
 `qualitytag` text,
 `type` text NOT NULL,
 `isdynamic` int(11) NOT NULL DEFAULT '0',
 `streamid` text,
 `streamsuffix` text NOT NULL,
 `isdesktopcompatible` int(11) NOT NULL DEFAULT '0',
 `isandroidcompatible` int(11) NOT NULL DEFAULT '0',
 `isioscompatible` int(11) NOT NULL DEFAULT '0',
 `disabled` int(10) NOT NULL DEFAULT '0',
 PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;

CREATE TABLE `livestream_profiles_groups` (
 `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
 `livestreamprofilegroupid` int(10) unsigned NOT NULL,
 `livestreamprofileid` int(10) unsigned NOT NULL,
 `weight` int(11) NOT NULL,
 PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `livestream_transcoders` (
 `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
 `timestamp` datetime NOT NULL,
 `name` text NOT NULL,
 `description` text NOT NULL,
 `type` text NOT NULL,
 `server` text NOT NULL,
 `ingressurl` text NOT NULL,
 `disabled` int(10) NOT NULL DEFAULT '0',
 PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `livestream_groups` (`id`, `timestamp`, `name`, `description`, `slideonright`, `accesstype`, `anonymousallowed`, `moderationtype`, `feedtype`, `istranscoderencoded`, `transcoderid`, `disabled`) VALUES
(1, '2015-09-23 00:00:00', 'Videosquare SD + HD', 'SD + HD streams', 0, NULL, 0, NULL, 'live', 0, NULL, 0),
(2, '2015-09-23 00:00:00', 'Meeting room recorder', 'Epiphan + Crestron meeting room for live streaming and recording', 0, NULL, 0, NULL, 'live', 0, NULL, 0),
(3, '2015-09-23 00:00:00', 'Transcode ABR 360p', 'Transcoding 140p, 240p, 360p', 0, NULL, 0, NULL, 'live', 1, 1, 0);

INSERT INTO `livestream_profiles` (`id`, `timestamp`, `qualitytag`, `type`, `isdynamic`, `streamid`, `streamsuffix`, `isdesktopcompatible`, `isandroidcompatible`, `isioscompatible`, `disabled`) VALUES
(3, '2015-09-23 00:00:00', 'SD', 'video', 1, NULL, '', 1, 1, 1, 0),
(4, '2015-09-23 00:00:00', 'SD', 'content', 1, NULL, '', 1, 1, 1, 0),
(5, '2015-09-23 00:00:00', 'HD', 'video', 1, NULL, '', 1, 0, 0, 0),
(6, '2015-09-23 00:00:00', 'HD content', 'content', 1, NULL, '', 1, 0, 0, 0),
(7, '2015-09-23 00:00:00', '360p', 'video', 0, '111111', '_360p', 1, 1, 1, 0),
(8, '2015-09-23 00:00:00', '480p', 'video', 0, '111111', '_480p', 1, 1, 1, 0),
(9, '2015-09-23 00:00:00', '720p', 'video', 0, '111111', '_720p', 1, 1, 1, 0),
(10, '2015-09-23 00:00:00', '360p', 'content', 0, '222222', '_360p', 1, 1, 1, 0),
(11, '2015-09-23 00:00:00', '480p', 'content', 0, '222222', '_480p', 1, 1, 1, 0),
(12, '2015-09-23 00:00:00', '720p', 'content', 0, '222222', '_720p', 1, 1, 1, 0),
(13, '2015-09-23 00:00:00', '180p', 'video', 1, NULL, '_180p', 1, 1, 1, 0),
(14, '2015-09-23 00:00:00', '240p', 'video', 1, NULL, '_240p', 1, 1, 1, 0),
(15, '2015-09-23 00:00:00', '360p', 'video', 1, NULL, '_360p', 1, 1, 1, 0),
(16, '2015-09-23 00:00:00', '180p', 'content', 1, NULL, '_180p', 1, 1, 1, 0),
(17, '2015-09-23 00:00:00', '240p', 'content', 1, NULL, '_240p', 1, 1, 1, 0),
(18, '2015-09-23 00:00:00', '360p', 'content', 1, NULL, '_360p', 1, 1, 1, 0);

INSERT INTO `livestream_profiles_groups` (`id`, `livestreamprofilegroupid`, `livestreamprofileid`, `weight`) VALUES
(1, 1, 3, 10),
(2, 1, 4, 20),
(3, 1, 5, 30),
(4, 1, 6, 40),
(5, 2, 7, 10),
(6, 2, 8, 20),
(7, 2, 9, 30),
(8, 2, 10, 40),
(9, 2, 11, 50),
(10, 2, 12, 60),
(11, 3, 13, 10),
(12, 3, 14, 20),
(13, 3, 15, 30),
(14, 3, 16, 40),
(15, 3, 17, 50),
(16, 3, 17, 60);

INSERT INTO `livestream_transcoders` (`id`, `timestamp`, `name`, `description`, `type`, `server`, `ingressurl`, `disabled`) VALUES
(1, '2015-09-23 00:00:00', 'NGINX transcoder', '', 'nginx', 'stream3.videosquare.eu', 'rtmp://stream3.videosquare.eu:1935/live', 0);

