ALTER TABLE  `recordings` ADD  `approvalstatus` TEXT NULL AFTER  `ispublished`;
UPDATE `recordings` SET approvalstatus = 'pending' WHERE ispublished = '0';
UPDATE `recordings` SET approvalstatus = 'approved' WHERE ispublished = '1';
ALTER TABLE  `recordings` DROP  `ispublished`;
ALTER TABLE  `recordings` CHANGE  `approvalstatus`  `approvalstatus` TEXT NOT NULL;
