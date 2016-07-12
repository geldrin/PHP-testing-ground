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
