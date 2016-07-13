ALTER TABLE `organizations_authtypes`
ADD `isuserinitiated` int(10) NOT NULL DEFAULT '0' AFTER `organizationid`;
