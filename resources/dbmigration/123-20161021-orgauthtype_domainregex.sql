ALTER TABLE `organizations_authtypes`
CHANGE `domain` `domainregex` text COLLATE 'utf8mb4_unicode_ci' NOT NULL AFTER `type`;

ALTER TABLE `organizations_directories`
CHANGE `domains` `domainregex` mediumtext COLLATE 'utf8mb4_unicode_ci' NOT NULL AFTER `password`;
