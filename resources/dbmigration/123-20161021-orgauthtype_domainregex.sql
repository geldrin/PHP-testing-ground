ALTER TABLE `organizations_authtypes`
CHANGE `domain` `domainregex` text NOT NULL AFTER `type`;

ALTER TABLE `organizations_directories`
CHANGE `domains` `domainregex` text NOT NULL AFTER `password`;
