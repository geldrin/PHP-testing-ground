ALTER TABLE  `channel_types` ADD  `iscourse` INT NOT NULL DEFAULT  '0';
UPDATE channels_recordings SET weight = id WHERE weight = 100;
ALTER TABLE  `organizations` ADD  `elearningcoursecriteria` INT NOT NULL DEFAULT  '90';
