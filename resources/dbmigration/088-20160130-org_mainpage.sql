ALTER TABLE `organizations`
ADD `frontpageblockorder` text;

ALTER TABLE `livefeeds`
ADD `isfeatured` int(11) NOT NULL DEFAULT '0';
