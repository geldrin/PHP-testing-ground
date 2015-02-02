ALTER TABLE  `livefeeds`
ADD `numberofviews` bigint(20) unsigned not null default '0',
ADD `numberofviewsthisweek` bigint(20) unsigned not null default '0',
ADD `numberofviewsthismonth` bigint(20) unsigned not null default '0',
ADD  `isnumberofviewspublic` INT NOT NULL DEFAULT  '0'; 