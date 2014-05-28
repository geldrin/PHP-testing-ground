ALTER TABLE  `access` ADD INDEX `ix_departmentid` (`departmentid`);
ALTER TABLE  `channels` CHANGE  `isdeleted`  `isdeleted` INT( 10 ) NOT NULL DEFAULT  '0';
ALTER TABLE  `document_logs` CHANGE  `duration`  `duration` INT( 10 ) UNSIGNED NULL DEFAULT NULL;
ALTER TABLE  `livefeed_streams` CHANGE  `recordinglinkid`  `recordinglinkid` INT( 10 ) UNSIGNED NULL DEFAULT NULL;

ALTER TABLE  `contributors_roles` DROP  `organizationid_temp`;
