ALTER TABLE  `invite_templates`
ADD  `subject` TEXT NULL AFTER  `organizationid` ,
ADD  `title` TEXT NULL AFTER  `subject`;