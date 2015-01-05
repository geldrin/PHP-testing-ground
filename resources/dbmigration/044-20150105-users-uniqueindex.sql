ALTER TABLE  `users` DROP INDEX  `uq_emailorganizationid` ,
ADD INDEX  `ix_emailorganizationid` (  `email` ( 50 ) ,  `organizationid` );
