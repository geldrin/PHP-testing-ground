CREATE TABLE `userroles` (
  `id` int unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` text NOT NULL
);

ALTER TABLE `userroles`
ADD UNIQUE `uq-name` (`name`(50));

CREATE TABLE `userroles_privileges` (
  `id` int unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `userroleid` int unsigned NOT NULL,
  `privilegeid` int unsigned NOT NULL
);

ALTER TABLE `userroles_privileges`
ADD INDEX `ix-userroleid` (`userroleid`);

CREATE TABLE `privileges` (
  `id` int unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` text NOT NULL,
  `comment` text NULL
);

ALTER TABLE `users`
ADD `userroleid` int NULL;

-- alap role-ok es privilegiumok, biztosan tobb lesz
INSERT INTO `userroles` (`id`, `name`) VALUES
(1,	'public'),
(2,	'member'),
(3,	'admin');

INSERT INTO `privileges` (`id`, `name`, `comment`) VALUES
(1,	'index_index',	'a fooldal'),
(2,	'users_login',	'belepes'),
(3,	'combine_css',	'css hozzáférés'),
(4,	'combine_js',	'js hozzáférés'),
(5,	'contents_all',	'minden content oldal');

INSERT INTO `userroles_privileges` (`id`, `userroleid`, `privilegeid`) VALUES
(1,	1,	3),
(2,	1,	4),
(3,	1,	5),
(4,	1,	1),
(5,	1,	2),
(6,	2,	3),
(7,	2,	4),
(8,	2,	5),
(9,	2,	1),
(10,	2,	2),
(11,	3,	3),
(12,	3,	4),
(13,	3,	5),
(14,	3,	1),
(15,	3,	2);
