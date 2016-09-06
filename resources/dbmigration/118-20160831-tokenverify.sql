ALTER TABLE `organizations`
ADD `istokenverifyenabled` int(11) NOT NULL DEFAULT '0',
ADD `tokenverifyurl` text NULL AFTER `istokenverifyenabled`;
