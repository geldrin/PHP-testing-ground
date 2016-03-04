ALTER TABLE `organizations`
ADD `frontpageblockorder` text;

ALTER TABLE `channels`
ADD `isfeatured` int(11) NOT NULL DEFAULT '0';
