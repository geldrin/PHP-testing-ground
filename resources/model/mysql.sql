
CREATE TABLE `mailqueue` (
   `id` int(11) not null auto_increment,
   `fromemail` text,
   `fromname` text,
   `fromencoded` text,
   `toemail` text,
   `toname` text,
   `toencoded` text,
   `headers` text,
   `subject` text,
   `subjectencoded` text,
   `body` text,
   `bodyencoded` longtext,
   `timestamp` datetime,
   `timetosend` datetime,
   `timesent` datetime,
   `status` varchar(15),
   `errormessage` text,
   `userid` int(11),
   `deleteafter` int(11) default '0',
   PRIMARY KEY (`id`),
   KEY `ix_status` (`status`),
   KEY `ix_timetosend` (`timetosend`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;


CREATE TABLE `users` (
   `id` int(10) unsigned not null auto_increment,
   `nickname` text not null,
   `email` text not null,
   `nameprefix` text,
   `namefirst` text not null,
   `namelast` text not null,
   `nameformat` text,
   `organizationid` int(10) unsigned not null default '0',
   `departmentid` int(10) unsigned not null default '0',
   `isadmin` int(10) unsigned not null default '0',
   `isclientadmin` int(10) unsigned not null default '0',
   `iseditor` int(10) unsigned not null default '0',
   `isnewseditor` int(10) unsigned not null default '0',
   `isuploader` int(10) unsigned not null default '0',
   `isliveadmin` int(10) unsigned not null default '0',
   `timestamp` datetime not null,
   `lastloggedin` datetime not null,
   `language` text not null, -- default 'hu',
   `newsletter` int(10) unsigned not null,
   `password` text,
   `browser` text not null,
   `validationcode` text not null,
   `disabled` int(11) not null default '0',
   `isapienabled` int(11) not null default '0',
   `issingleloginenforced` int(11) not null default '0',
   `sessionid` char(32),
   `sessionlastupdated` datetime,
   `apiaddresses` text,
   `avatarfilename` text,
   `avatarstatus` text,
   PRIMARY KEY (`id`),
   UNIQUE INDEX `uq_emailorganizationid` (`email`(50), `organizationid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;


CREATE TABLE `organizations` (
   `id` int(10) unsigned not null auto_increment,
   `parentid` int(10) unsigned not null default '0',
   `organizationid` int(10) unsigned,
   `addressid` int(10) unsigned,
   `postaladdressid` int(10) unsigned,
   `billingaddressid` int(10) unsigned,
   `languages` text,
   `name` text,
   `name_stringid` int(10) unsigned,
   `nameshort` text,
   `nameshort_stringid` int(10) unsigned,
   `introduction` text,
   `introduction_stringid` int(10) unsigned,
   `url` text,
   `issubscriber` int(11) not null default '0',
   `isvcrenabled` int(11) not null default '0',
   `issecurestreamingenabled` int(11) not null default '0',
   `islivestreamingenabled` int(11) not null default '0',
   `issessionvalidationenabled` int(11) not null default '0',
   `domain` text,
   `supportemail` text,
   `registrationtype` text,
   `backgroundcolor` text,
   `logofilename` text,
   `logofilenameen` text,
   `disabled` int(11) not null default '0',
   `fullnames` int(11) not null default '0',
   PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;


CREATE TABLE `organizations_news` (
  `id` int(10) unsigned not null auto_increment,
  `organizationid` int(10) unsigned not null default '0',
  `title` text,
  `title_stringid` int(10) unsigned,
  `lead` text,
  `lead_stringid` int(10) unsigned,
  `body` text,
  `body_stringid` int(10) unsigned,
  `starts` datetime,
  `ends` datetime,
  `timestamp` datetime not null,
  `weight` int(11) default '100',
  `disabled` int(11) not null default '0',
  PRIMARY KEY (`id`),
  KEY `organizationid` (`organizationid`),
  KEY `starts-ends` (`starts`, `ends`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;


CREATE TABLE `recordings` (
   `id` int(10) unsigned not null auto_increment,
   `userid` int(10) unsigned not null,
   `languageid` int(10) unsigned not null,
   `organizationid` int(10) unsigned not null,
   `locationid` int(10) unsigned,
   `intorecordingid` int(10) unsigned,
   `outrorecordingid` int(10) unsigned,
   `title` text,
   `subtitle` text,
   `description` text,
   `technicalnote` text,
   `keywords` text,
   `copyright` text,
   `slideonright` int(11) not null default '1',
   `timestamp` datetime not null,
   `recordedtimestamp` datetime not null,
   `metadataupdatedtimestamp` datetime not null,
   `mediatype` text not null,
   `accesstype` text not null,
   `ispublished` int(11) not null default '0',
   `isdownloadable` int(11) not null default '0',
   `isaudiodownloadable` int(11) not null default '1',
   `ismetadatashareable` int(11) not null default '1',
   `isembedable` int(11) not null default '1',
   `isfeatured` int(11) not null default '0',
   `isintrooutro` int(11) not null default '0',
   `status` text not null,
   `offsetstart` int(11),
   `offsetend` int(11),
   `contentoffsetstart` int(11),
   `contentoffsetend` int(11),
   `masterstatus` text,
   `contentstatus` text,
   `mobilestatus` text,
   `contentmasterstatus` text,
   `mastersourceip` text,
   `contentmastersourceip` text,
   `conversionpriority` int(10) unsigned not null default '100',
   `visiblefrom` date,
   `visibleuntil` date,
   `indexphotofilename` text,
   `numberofindexphotos` int unsigned default '0',
   `primarymetadatacache` longtext,
   `additionalcache` longtext,
   `numberofcomments` bigint(20) unsigned not null default '0',
   `numberofcommentsthisweek` bigint(20) unsigned not null default '0',
   `numberofcommentsthismonth` bigint(20) unsigned not null default '0',
   `numberofviews` bigint(20) unsigned not null default '0',
   `numberofviewsthisweek` bigint(20) unsigned not null default '0',
   `numberofviewsthismonth` bigint(20) unsigned not null default '0',
   `rating` decimal(3,2) not null default '0',
   `sumofrating` bigint(20) unsigned not null default '0',
   `numberofratings` bigint(20) unsigned not null default '0',
   `numberofratingsthisweek` bigint(20) unsigned not null default '0',
   `ratingthisweek` decimal(3,2) not null default '0',
   `sumofratingthisweek` bigint(20) unsigned not null default '0',
   `numberofratingsthismonth` bigint(20) unsigned not null default '0',
   `ratingthismonth` decimal(3,2) not null default '0',
   `sumofratingthismonth` bigint(20) unsigned not null default '0',
   `mastervideofilename` text,
   `mastervideoextension` text,
   `mastermediatype` text,
   `mastervideostreamselected` int default null,
   `mastervideoisinterlaced` int default 0,
   `mastervideocontainerformat` text,
   `mastervideofps` float unsigned default null,
   `masterlength` float unsigned default null,
   `mastervideocodec` text,
   `mastervideores` text,
   `mastervideodar` text,
   `mastervideobitrate` int(10) unsigned default null,
   `masteraudiostreamselected` int default null,
   `masteraudiocodec` text,
   `masteraudiochannels` text,
   `masteraudioquality` text,
   `masteraudiofreq` int(11) default null,
   `masteraudiobitrate` int(10) unsigned default null,
   `masteraudiobitratemode` text,
   `videoreslq` text,
   `videoreshq` text,
   `mobilevideoreslq` text,
   `mobilevideoreshq` text,
   `contentmastervideoisinterlaced` int default 0,
   `contentmastervideofilename` text,
   `contentmastervideoextension` text,
   `contentmastermediatype` text,
   `contentmastervideostreamselected` int default null,
   `contentmastervideocontainerformat` text,
   `contentmasterlength` float unsigned default null,
   `contentmastervideocodec` text,
   `contentmastervideores` text,
   `contentmastervideodar` text,
   `contentmastervideofps` float unsigned default null,
   `contentmastervideobitrate` int(10) unsigned default null,
   `contentmasteraudiostreamselected` int default null,
   `contentmasteraudiocodec` text,
   `contentmasteraudiobitratemode` text,
   `contentmasteraudiochannels` text,
   `contentmasteraudioquality` text,
   `contentmasteraudiofreq` int(11) default null,
   `contentmasteraudiobitrate` int(10) unsigned default null,
   `contentvideoreslq` text,
   `contentvideoreshq` text,
   `livefeedid` int(10) unsigned default null,
   PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;


CREATE TABLE `languages` (
   `id` int(10) unsigned not null auto_increment,
   `shortname` char(3) not null,
   `originalname` text not null,
   `name` text,
   `name_stringid` int(10) unsigned not null,
   `weight` int(11) not null default '100',
   PRIMARY KEY (`id`),
   UNIQUE KEY (`shortname`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;


CREATE TABLE `strings` (
   `id` int(10) unsigned not null auto_increment,
   `language` char(2) not null,
   `value` text not null,
   `translationof` int(10) unsigned, -- references strings(id),
   PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;


CREATE TABLE `contents` (
   `id` int(10) unsigned not null auto_increment,
   `shortname` text not null,
   `title` text,
   `title_stringid` int(10) unsigned,
   `body` text,
   `body_stringid` int(10) unsigned,
   PRIMARY KEY (`id`),
   UNIQUE KEY `uix_shortname` (`shortname`(80))
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;


CREATE TABLE `help_contents` (
   `id` int(10) unsigned not null auto_increment,
   `shortname` text not null,
   `title` text,
   `title_stringid` int(10) unsigned,
   `body` text,
   `body_stringid` int(10) unsigned,
   PRIMARY KEY (`id`),
   UNIQUE KEY `uix_shortname` (`shortname`(80))
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;


CREATE TABLE `genres` (
   `id` int(10) unsigned not null auto_increment,
   `parentid` int(10) unsigned not null default '0',
   `organizationid` int(10) unsigned,
   `name` text,
   `name_stringid` int(10) unsigned not null,
   `weight` int(10) unsigned not null default '100',
   `disabled` int(11) not null default '0',
   PRIMARY KEY (`id`),
   KEY `ix_organizationid` (`organizationid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;


CREATE TABLE `categories` (
   `id` int(10) unsigned not null auto_increment,
   `parentid` int(10) unsigned not null default '0',
   `organizationid` int(10) unsigned,
   `name` text,
   `name_stringid` int(10) unsigned not null,
   `namehyphenated` text,
   `namehyphenated_stringid` int(10) unsigned not null,
   `iconfilename` text,
   `weight` int(11) not null default '100',
   `disabled` int(11) not null default '0',
   `numberofrecordings` int(11) not null default '0',
   PRIMARY KEY (`id`),
   KEY `ix_organizationid` (`organizationid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;


CREATE TABLE `recordings_genres` (
   `id` int(10) unsigned not null auto_increment,
   `genreid` int(10) unsigned not null,
   `recordingid` int(10) unsigned not null,
   PRIMARY KEY (`id`),
   KEY `ix_recordingid` (`recordingid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;


CREATE TABLE `recordings_categories` (
   `id` int(10) unsigned not null auto_increment,
   `categoryid` int(10) unsigned not null,
   `recordingid` int(10) unsigned not null,
   PRIMARY KEY (`id`),
   KEY `ix_recordingid` (`recordingid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;


CREATE TABLE `groups` (
   `id` int(10) unsigned not null auto_increment,
   `name` text not null,
   `userid` int(10) unsigned not null,
   `timestamp` datetime not null,
   PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;


CREATE TABLE `groups_members` (
   `id` int(10) unsigned not null auto_increment,
   `groupid` int(10) unsigned not null,
   `userid` int(10) unsigned not null,
   `timestamp` datetime not null,
   PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE `group_invitations` (
   `id` int(10) unsigned not null auto_increment,
   `groupid` int(10) unsigned not null,
   `userid` int(10) unsigned not null,
   `email` text not null,
   `validationcode` text not null,
   PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;


CREATE TABLE `users_invitations` (
   `id` int(10) unsigned not null auto_increment,
   `permissions` text not null,
   `userid` int(10) unsigned not null,
   `email` text not null,
   `validationcode` text not null,
   PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;


CREATE TABLE `comments` (
   `id` int(10) unsigned not null auto_increment,
   `recordingid` int(10) unsigned not null,
   `userid` int(10) unsigned not null,
   `timestamp` datetime not null,
   `text` text not null,
   `ipaddress` text,
   `moderated` int(11) not null default '0',
   PRIMARY KEY (`id`),
   KEY `ix_recordingid_moderated` (`recordingid`, `moderated`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;


CREATE TABLE `channels` (
   `id` int(10) unsigned not null auto_increment,
   `parentid` int(10) unsigned not null default '0',
   `userid` int(10) unsigned not null default '0',
   `channeltypeid` int(10) unsigned not null, -- references channel_types(id),
   `locationid` int(10) unsigned not null default '0',
   `starttimestamp` datetime,
   `endtimestamp` datetime,
   `title` text,
   `subtitle` text,
   `ordinalnumber` text,
   `description` text,
   `url` text,
   `indexphotofilename` text,
   `ispublic` int(11) not null default '0',
   `numberofrecordings` int(11) not null default '0',
   `weight` int(11) not null default '100',
   `isliveevent` int(11) not null default '0',
   `livefeedid` int(10) unsigned not null default '0',
   `organizationid` int(10) unsigned not null,
   `accesstype` text not null,
   `isdeleted` int(10) unsigned not null default '0',
   `livestatsstatus` text,
   PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;


CREATE TABLE channel_types(
   `id` int(10) unsigned not null auto_increment,
   `parentid` int(10) unsigned not null default '0',
   `organizationid` int(10) unsigned,
   `name` text,
   `name_stringid` int(10) unsigned,
   `weight` int(10) unsigned not null default '100',
   `isfavorite` int(11) not null default '0',
   `ispersonal` int(11) not null default '0',
   PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;


CREATE TABLE `channels_recordings` (
   `id` int(10) unsigned not null auto_increment,
   `channelid` int(10) unsigned not null,
   `recordingid` int(10) unsigned not null,
   `userid` int(10) unsigned not null,
   `weight` int(11) not null default '100',
   PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;


CREATE TABLE `contributor_images` (
   `id` int(10) unsigned not null auto_increment,
   `contributorid` int(10) unsigned not null,
   `indexphotofilename` text not null,
   PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;


CREATE TABLE `contributors_roles` (
   `id` int(10) unsigned not null auto_increment,
   `organizationid` int(10) unsigned, -- references organizations(id),
   `contributorid` int(10) unsigned, -- references contributors(id),
   `recordingid` int(10) unsigned not null,
   `roleid` int(10) unsigned,
   `weight` int(10) unsigned not null default '100',
   PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;


CREATE TABLE `contributors_jobs` (
   `id` int(10) unsigned not null auto_increment,
   `contributorid` int(10) unsigned not null,
   `organizationid` int(10) unsigned,
   `userid` int(10) unsigned,
   `jobgroupid` int(10) unsigned not null default '1', -- mindenkeppen kell hogy legyen jobgroupid
   `job` text,
   PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;


CREATE TABLE `contributors` (
   `id` int(10) unsigned not null auto_increment,
   `createdby` int(10) unsigned not null,
   `timestamp` datetime not null,
   `userid` int(10) unsigned,
   `ownedby` int(10) unsigned,
   `nameprefix` text,
   `namefirst` text,
   `namelast` text not null,
   `namealias` text,
   `nameformat` text,
   `contributorimageid` int(10) unsigned not null default 0,
   `organizationid` int(10) unsigned,
   PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;


CREATE TABLE `roles` (
   `id` int(10) unsigned not null auto_increment,
   `name` text,
   `name_stringid` int(10) unsigned not null,
   `weight` int(10) unsigned not null default '100',
   `ispersonrelated` int default '0',
   `isorganizationrelated` int default '0',
   `organizationid` int(10) unsigned not null,
   PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;


CREATE TABLE `subtitles` (
   `id` int(10) unsigned not null auto_increment,
   `recordingid` int(10) unsigned not null,
   `languageid` int(10) unsigned not null,
   `subtitle` longtext not null,
   `isdefault` int default '0',
   PRIMARY KEY (`id`),
   KEY `ix_recordingid` (`recordingid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;


CREATE TABLE `livefeeds` (
   `id` int(10) unsigned not null auto_increment,
   `userid` int(10) unsigned not null,
   `organizationid` int(10) unsigned not null,
   `channelid` int(10) unsigned, /* live channel id - az az elo channel (isliveevent=1), ami alatti esemenyek ezt a feedet hasznalhatjak */
   `name` text,
   `slideonright` int(11) not null default '0',
   `accesstype` text not null,
   `issecurestreamingforced` int(11) not null default '0',
   `needrecording` int(11) not null default '1',
   `feedtype` text,
   PRIMARY KEY (`id`),
   KEY `channelid` (`channelid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;


CREATE TABLE `livefeed_streams` (
   `id` int(10) unsigned not null auto_increment,
   `livefeedid` int(10) unsigned not null,
   `name` text not null,
   `keycode` text,
   `contentkeycode` text,
   `isdesktopcompatible` int(11) not null default '0',
   `isandroidcompatible` int(11) not null default '0',
   `isioscompatible` int(11) not null default '0',
   `quality` int(11) not null default '0',
   `status` text,
   `recordinglinkid` int(11) default null,
   `vcrconferenceid` text,
   `moderationtype` text,
   `timestamp` datetime not null,
   PRIMARY KEY (`id`),
   KEY `livefeedid` (`livefeedid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;


CREATE TABLE `attached_documents` (
   `id` int(10) unsigned not null auto_increment,
   `recordingid` int(10) unsigned,
   `userid` int(10) unsigned not null,
   `timestamp` datetime not null,
   `title` text,
   `masterfilename` text not null,
   `masterextension` text,
   `type` text,
   `status` text not null,
   `indexingstatus` text,
   `documentcache` longtext,
   `ispdfgenerated` int(10) unsigned not null default '0',
   `sourceip` text not null,
   `isdownloadable` int(10) unsigned not null default '0',
   PRIMARY KEY (`id`),
   KEY `status-recordingid` (`status`(20), `recordingid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;


CREATE TABLE `recording_links` (
   `id` int(10) unsigned not null auto_increment,
   `timestamp` datetime default null,
   `organizationid` int(10) unsigned,
   `name` text not null,
   `calltype` text not null,
   `number` text not null,
   `password` text,
   `bitrate` int(11) not null comment 'Values: 64, 128, 192, 256, 384, 512, 768, 1024, 1280, 1536, 1920 and 2048 kbps (as well as 2560, 3072 and 4000 kbps for Content Servers equipped with the Premium Resolution option)',
   `alias` text not null,
   `aliassecure` text not null,
   `status` text not null,
   `conferenceid` text,
   `disabled` int(11) not null default '0',
   PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;


CREATE TABLE `access` (
   `id` int(10) unsigned not null auto_increment,
   `recordingid` int(10) unsigned,
   `livefeedid` int(10) unsigned,
   `channelid` int(10) unsigned,
   `departmentid` int(10) unsigned,
   `groupid` int(10) unsigned,
   PRIMARY KEY (`id`),
   KEY `ix_recordingid` (`recordingid`),
   KEY `ix_livefeedid` (`livefeedid`),
   KEY `ix_channelid` (`channelid`),
   KEY `ix_groupid` (`groupid`),
   KEY `ix_departmentid` (`departmentid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `recording_logs` (
  `id` int(10) unsigned not null auto_increment,
  `timestamp` datetime not null,
  `node` text not null,
  `recordingid` int(10) unsigned default null,
  `job` text not null,
  `action` text not null,
  `status` text not null,
  `command` text,
  `data` text,
  `duration` int(10) unsigned default null,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=3082 ;

CREATE TABLE `document_logs` (
   `id` int(10) unsigned not null auto_increment,
   `timestamp` datetime not null,
   `node` text not null,
   `attacheddocumentid` int(10) unsigned default null,
   `recordingid` int(10) unsigned default null,
   `job` text not null,
   `action` text not null,
   `status` text not null,
   `command` text not null,
   `data` text not null,
   `duration` int(10) unsigned default null,
   PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE `livefeed_chat` (
   `id` int(10) unsigned not null auto_increment,
   `livefeedid` int(10) unsigned not null,
   `userid` int(10) unsigned not null,
   `timestamp` datetime not null,
   `text` text not null,
   `ipaddress` text,
   `moderated` int(11) not null default '0',
   PRIMARY KEY (`id`),
   KEY `ix_livefeedid_moderated` (`livefeedid`, `moderated`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;


CREATE TABLE `departments` (
   `id` int(10) unsigned not null auto_increment,
   `parentid` int(10) unsigned not null default '0',
   `organizationid` int(10) unsigned,
   `name` text,
   `nameshort` text,
   `weight` int(10) unsigned not null default '100',
   PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;


CREATE TABLE uploads(
   `id` int(10) unsigned not null auto_increment,
   `userid` int(10) unsigned not null,
   `recordingid` int(10) unsigned not null default '0',
   `iscontent` int(11) not null default '0',
   `filename` text,
   `size` bigint(20) unsigned,
   `chunkcount` int(10) unsigned,
   `currentchunk` int(10) unsigned,
   `status` text,
   `timestamp` datetime not null,
   PRIMARY KEY (`id`),
   KEY `ix_userid_filename` (`userid`, `filename`(50)),
   KEY `ix_status` (`status`(15))
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;
