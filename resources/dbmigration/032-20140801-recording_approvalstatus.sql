ALTER TABLE  `recordings` ADD  `approvalstatus` TEXT NULL AFTER  `ispublished`;
UPDATE `recordings` SET approvalstatus = 'draft' WHERE ispublished = '0';
UPDATE `recordings` SET approvalstatus = 'approved' WHERE ispublished = '1';
ALTER TABLE  `recordings` DROP  `ispublished`;
ALTER TABLE  `recordings` CHANGE  `approvalstatus`  `approvalstatus` TEXT NOT NULL;

ALTER TABLE  `users` ADD  `ismoderateduploader` INT NOT NULL DEFAULT  '0' AFTER  `isliveadmin`;
