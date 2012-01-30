
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
   `isadmin` int(10) unsigned not null default '0',
   `isclientadmin` int(10) unsigned not null default '0',
   `iseditor` int(10) unsigned not null default '0',
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
   PRIMARY KEY (`id`),
   UNIQUE INDEX `uq_emailorganizationid` (`email`(50), `organizationid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;


CREATE TABLE `organizations` (
   `id` int(10) unsigned not null auto_increment,
   `parentid` int(10) unsigned,
   `addressid` int(10) unsigned,
   `postaladdressid` int(10) unsigned,
   `billingaddressid` int(10) unsigned,
   `nameoriginal` text,
   `nameenglish` text,
   `nameshortoriginal` text,
   `nameshortenglish` text,
   `url` text,
   `issubscriber` int(11) not null default '0',
   `domain` text,
   `registrationtype` text,
   `backgroundcolor` text,
   `logofilename` text,
   `logofilenameen` text,
   `disabled` int(11) not null default '0',
   PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

