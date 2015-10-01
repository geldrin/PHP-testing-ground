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


